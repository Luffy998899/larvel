<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Repositories\SystemSettingRepository;
use Illuminate\Http\Request;

class SettingsController extends Controller
{
    private const ALLOWED_KEYS = [
        'free_server_ram',
        'free_server_cpu',
        'free_server_disk',
        'server_cost_per_day',
        'credits_per_ad',
        'max_ads_per_day',
        'daily_login_credits',
        'initial_signup_credits',
        'max_free_servers_per_user',
    ];

    public function index(SystemSettingRepository $repository)
    {
        return view('admin.settings', ['settings' => $repository->allKeyValue()]);
    }

    public function update(Request $request, SystemSettingRepository $repository)
    {
        $validated = $request->validate([
            'settings' => ['required', 'array'],
            'settings.*' => ['required', 'integer', 'min:0'],
        ]);

        foreach ($validated['settings'] as $key => $value) {
            if (in_array($key, self::ALLOWED_KEYS, true)) {
                $repository->set($key, (string) $value);
            }
        }

        return redirect()->route('admin.settings')->with('status', 'Settings updated.');
    }
}
