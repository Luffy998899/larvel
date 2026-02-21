<?php

namespace App\Services;

use App\Models\AdRewardLog;
use App\Models\CreditTransaction;
use App\Models\User;
use App\Repositories\SystemSettingRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class AdRewardService
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly SystemSettingRepository $systemSettingRepository,
    ) {
    }

    public function reward(array $payload, string $ipAddress, ?string $userAgent): void
    {
        $this->assertValidSignature($payload);

        $userId = (int) ($payload['user_id'] ?? 0);
        $providerTxnId = (string) ($payload['transaction_id'] ?? '');
        $provider = (string) ($payload['provider'] ?? 'unknown');

        if ($userId <= 0 || $providerTxnId === '') {
            throw new RuntimeException('Invalid webhook payload.');
        }

        $user = User::query()->findOrFail($userId);
        $creditsPerAd = $this->systemSettingRepository->getInt('credits_per_ad', 10);
        $maxAdsPerDay = $this->systemSettingRepository->getInt('max_ads_per_day', 5);

        DB::transaction(function () use ($user, $providerTxnId, $provider, $creditsPerAd, $maxAdsPerDay, $ipAddress, $userAgent) {
            $duplicate = AdRewardLog::query()->where('provider_transaction_id', $providerTxnId)->lockForUpdate()->exists();
            if ($duplicate) {
                throw new RuntimeException('Duplicate ad reward transaction.');
            }

            $todayCount = AdRewardLog::query()
                ->where('user_id', $user->id)
                ->where('verified', true)
                ->whereDate('created_at', now()->toDateString())
                ->lockForUpdate()
                ->count();

            if ($todayCount >= $maxAdsPerDay) {
                throw new RuntimeException('Daily ad reward limit reached.');
            }

            AdRewardLog::query()->create([
                'user_id' => $user->id,
                'provider' => $provider,
                'reward_amount' => $creditsPerAd,
                'verified' => true,
                'provider_transaction_id' => $providerTxnId,
                'ip_address' => $ipAddress,
                'user_agent' => $userAgent,
                'created_at' => now(),
            ]);

            $this->creditService->award(
                $user->id,
                $creditsPerAd,
                CreditTransaction::TYPE_AD,
                'Ad reward credited',
                $ipAddress
            );
        });
    }

    private function assertValidSignature(array $payload): void
    {
        $provided = (string) ($payload['signature'] ?? '');
        $transactionId = (string) ($payload['transaction_id'] ?? '');
        $userId = (string) ($payload['user_id'] ?? '');
        $secret = (string) config('services.ad_provider.webhook_secret');

        $expected = hash_hmac('sha256', $transactionId.'|'.$userId, $secret);

        if ($provided === '' || !hash_equals($expected, $provided)) {
            throw new RuntimeException('Invalid signature.');
        }
    }
}
