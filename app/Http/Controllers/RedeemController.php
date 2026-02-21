<?php

namespace App\Http\Controllers;

use App\Services\RedeemCodeService;
use Illuminate\Http\Request;
use Throwable;

class RedeemController extends Controller
{
    public function redeem(Request $request, RedeemCodeService $redeemCodeService)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:64'],
        ]);

        try {
            $redeemCodeService->redeem($request->user(), (string) $data['code'], $request->ip());
            return redirect()->route('dashboard')->with('status', 'Redeem successful.');
        } catch (Throwable $exception) {
            report($exception);
            return redirect()->route('dashboard')->withErrors(['redeem' => $exception->getMessage()]);
        }
    }
}
