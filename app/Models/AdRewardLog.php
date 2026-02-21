<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AdRewardLog extends Model
{
    protected $table = 'ad_rewards_log';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'provider',
        'reward_amount',
        'verified',
        'provider_transaction_id',
        'ip_address',
        'user_agent',
        'created_at',
    ];

    protected $casts = [
        'verified' => 'boolean',
        'created_at' => 'datetime',
    ];
}
