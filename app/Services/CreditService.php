<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Repositories\CreditRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class CreditService
{
    public function __construct(private readonly CreditRepository $creditRepository)
    {
    }

    public function award(int $userId, int $amount, string $type, string $description, ?string $ipAddress): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Award amount must be positive.');
        }

        DB::transaction(function () use ($userId, $amount, $type, $description, $ipAddress) {
            $this->creditRepository->firstOrCreateForUser($userId);
            $credit = $this->creditRepository->lockByUserId($userId);
            $credit->balance += $amount;
            $credit->total_earned += $amount;
            $credit->save();

            CreditTransaction::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'amount' => $amount,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);
        });
    }

    public function charge(int $userId, int $amount, string $type, string $description, ?string $ipAddress): void
    {
        if ($amount <= 0) {
            throw new RuntimeException('Charge amount must be positive.');
        }

        DB::transaction(function () use ($userId, $amount, $type, $description, $ipAddress) {
            $credit = $this->creditRepository->lockByUserId($userId);
            if ($credit->balance < $amount) {
                throw new RuntimeException('Insufficient credits.');
            }

            $credit->balance -= $amount;
            $credit->total_spent += $amount;
            $credit->save();

            CreditTransaction::query()->create([
                'user_id' => $userId,
                'type' => $type,
                'amount' => -$amount,
                'description' => $description,
                'ip_address' => $ipAddress,
                'created_at' => now(),
            ]);
        });
    }
}
