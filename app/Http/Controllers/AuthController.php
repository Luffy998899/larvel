<?php

namespace App\Http\Controllers;

use App\Models\Credit;
use App\Models\User;
use App\Repositories\SystemSettingRepository;
use App\Services\CreditService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;

class AuthController extends Controller
{
    public function showRegister()
    {
        return view('auth.register');
    }

    public function register(Request $request, SystemSettingRepository $settingRepository, CreditService $creditService)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ]);

        $ip = (string) $request->ip();
        $count = User::query()->where('register_ip', $ip)->count();
        if ($count >= 2) {
            throw ValidationException::withMessages(['email' => 'Maximum 2 accounts per IP address.']);
        }

        $user = DB::transaction(function () use ($request, $ip) {
            $user = User::query()->create([
                'name' => (string) $request->input('name'),
                'email' => (string) $request->input('email'),
                'password' => Hash::make((string) $request->input('password')),
                'register_ip' => $ip,
            ]);

            Credit::query()->create([
                'user_id' => $user->id,
                'balance' => 0,
                'total_earned' => 0,
                'total_spent' => 0,
            ]);

            return $user;
        });

        $initial = $settingRepository->getInt('initial_signup_credits', 20);
        $creditService->award($user->id, $initial, 'admin_adjust', 'Initial signup credits', $ip);

        Auth::login($user);
        $user->sendEmailVerificationNotification();

        return redirect()->route('dashboard')->with('status', 'Registered. Verify your email to continue.');
    }

    public function showLogin()
    {
        return view('auth.login');
    }

    public function login(Request $request)
    {
        $credentials = $request->validate([
            'email' => ['required', 'email'],
            'password' => ['required'],
        ]);

        if (! Auth::attempt($credentials, (bool) $request->boolean('remember'))) {
            throw ValidationException::withMessages(['email' => 'Invalid credentials.']);
        }

        $request->session()->regenerate();
        return redirect()->intended(route('dashboard'));
    }

    public function logout(Request $request)
    {
        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();
        return redirect()->route('login.form');
    }
}
