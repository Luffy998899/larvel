<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\RedeemCode;
use App\Models\RedeemLog;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class RedeemCodeService
{
    public function __construct(private readonly CreditService $creditService)
    {
    }

    public function redeem(User $user, string $code, ?string $ipAddress): void
    {
        DB::transaction(function () use ($user, $code, $ipAddress) {
            $redeemCode = RedeemCode::query()->where('code', strtoupper(trim($code)))->lockForUpdate()->first();

            if (! $redeemCode || ! $redeemCode->is_active) {
                throw new RuntimeException('Code is invalid or inactive.');
            }

            if ($redeemCode->expires_at && now()->greaterThan($redeemCode->expires_at)) {
                throw new RuntimeException('Code has expired.');
            }

            if ($redeemCode->used_count >= $redeemCode->max_uses) {
                throw new RuntimeException('Code max uses reached.');
            }

            $userUses = RedeemLog::query()->where('user_id', $user->id)->where('code_id', $redeemCode->id)->lockForUpdate()->count();
            if ($userUses >= $redeemCode->per_user_limit) {
                throw new RuntimeException('Per-user redemption limit reached.');
            }

            $redeemCode->used_count += 1;
            $redeemCode->save();

            RedeemLog::query()->create([
                'user_id' => $user->id,
                'code_id' => $redeemCode->id,
                'created_at' => now(),
            ]);

            $this->creditService->award(
                $user->id,
                $redeemCode->reward_value,
                CreditTransaction::TYPE_REDEEM,
                'Redeem code: '.$redeemCode->code,
                $ipAddress
            );
        });
    }
}
