<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\AdRewardLog;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Models\UserServer;
use App\Services\CreditService;
use App\Services\PterodactylService;
use Illuminate\Http\Request;

class AdminDashboardController extends Controller
{
    public function index()
    {
        $activeCount = UserServer::query()->where('status', 'active')->count();
        $suspendedCount = UserServer::query()->where('status', 'suspended')->count();
        $dailyRevenueEstimate = UserServer::query()->where('status', 'active')->sum('cost_per_day');

        return view('admin.index', [
            'users' => User::query()->latest()->limit(20)->get(),
            'servers' => UserServer::query()->latest('id')->limit(20)->with('user')->get(),
            'adStatsToday' => AdRewardLog::query()->whereDate('created_at', now()->toDateString())->count(),
            'transactions' => CreditTransaction::query()->latest('id')->limit(30)->get(),
            'activeCount' => $activeCount,
            'suspendedCount' => $suspendedCount,
            'dailyRevenueEstimate' => $dailyRevenueEstimate,
        ]);
    }

    public function adjustCredits(Request $request, CreditService $creditService)
    {
        $data = $request->validate([
            'user_id' => ['required', 'exists:users,id'],
            'amount' => ['required', 'integer', 'not_in:0'],
            'description' => ['nullable', 'string', 'max:255'],
        ]);

        if ((int) $data['amount'] > 0) {
            $creditService->award((int) $data['user_id'], (int) $data['amount'], CreditTransaction::TYPE_ADMIN_ADJUST, (string) ($data['description'] ?? 'Admin credit grant'), $request->ip());
        } else {
            $creditService->charge((int) $data['user_id'], abs((int) $data['amount']), CreditTransaction::TYPE_ADMIN_ADJUST, (string) ($data['description'] ?? 'Admin credit deduct'), $request->ip());
        }

        return redirect()->route('admin.dashboard')->with('status', 'Credits adjusted.');
    }

    public function forceSuspend(UserServer $userServer, PterodactylService $pterodactylService)
    {
        $pterodactylService->suspendServer((int) $userServer->pterodactyl_server_id);
        $userServer->status = 'suspended';
        $userServer->save();

        return redirect()->route('admin.dashboard')->with('status', 'Server suspended.');
    }

    public function forceDelete(UserServer $userServer, PterodactylService $pterodactylService)
    {
        $pterodactylService->deleteServer((int) $userServer->pterodactyl_server_id);
        $userServer->delete();

        return redirect()->route('admin.dashboard')->with('status', 'Server deleted.');
    }
}
