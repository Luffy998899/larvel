<?php

namespace App\Http\Controllers;

use App\Models\AdRewardLog;
use App\Models\CreditTransaction;
use App\Models\UserServer;
use App\Repositories\SystemSettingRepository;
use App\Services\CreditService;
use App\Services\ServerProvisionService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function index(Request $request, SystemSettingRepository $settings)
    {
        $user = $request->user()->load('credit');

        $data = [
            'credit' => $user->credit,
            'credits_per_ad' => $settings->getInt('credits_per_ad', 10),
            'max_ads_per_day' => $settings->getInt('max_ads_per_day', 5),
            'daily_login_credits' => $settings->getInt('daily_login_credits', 5),
            'servers' => UserServer::query()->where('user_id', $user->id)->orderByDesc('id')->get(),
            'today_ad_count' => AdRewardLog::query()->where('user_id', $user->id)->whereDate('created_at', now()->toDateString())->count(),
        ];

        return view('dashboard.index', $data);
    }

    public function claimServer(Request $request, ServerProvisionService $provisionService)
    {
        $provisionService->claimFreeServer($request->user());

        return redirect()->route('dashboard')->with('status', 'Server claimed successfully.');
    }

    public function claimDailyLogin(Request $request, SystemSettingRepository $settings, CreditService $creditService)
    {
        $alreadyClaimed = CreditTransaction::query()
            ->where('user_id', $request->user()->id)
            ->where('type', CreditTransaction::TYPE_DAILY_LOGIN)
            ->whereDate('created_at', now()->toDateString())
            ->exists();

        if ($alreadyClaimed) {
            return redirect()->route('dashboard')->withErrors(['daily' => 'Daily login already claimed today.']);
        }

        $creditService->award(
            $request->user()->id,
            $settings->getInt('daily_login_credits', 5),
            CreditTransaction::TYPE_DAILY_LOGIN,
            'Daily login bonus',
            $request->ip()
        );

        return redirect()->route('dashboard')->with('status', 'Daily login credits claimed.');
    }
}
