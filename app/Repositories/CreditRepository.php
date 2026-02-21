<?php

namespace App\Repositories;

use App\Models\Credit;

class CreditRepository
{
    public function lockByUserId(int $userId): Credit
    {
        return Credit::query()->where('user_id', $userId)->lockForUpdate()->firstOrFail();
    }

    public function firstOrCreateForUser(int $userId): Credit
    {
        return Credit::query()->firstOrCreate(
            ['user_id' => $userId],
            ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
        );
    }
}
