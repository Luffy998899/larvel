<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\AdRewardService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Throwable;

class AdWebhookController extends Controller
{
    public function reward(Request $request, AdRewardService $adRewardService): JsonResponse
    {
        $data = $request->validate([
            'provider' => ['required', 'string', 'max:50'],
            'transaction_id' => ['required', 'string', 'max:120'],
            'user_id' => ['required', 'integer'],
            'signature' => ['required', 'string'],
        ]);

        try {
            $adRewardService->reward($data, (string) $request->ip(), $request->userAgent());

            return response()->json(['ok' => true, 'message' => 'Reward applied.']);
        } catch (Throwable $exception) {
            report($exception);

            return response()->json([
                'ok' => false,
                'message' => $exception->getMessage(),
            ], 422);
        }
    }
}
