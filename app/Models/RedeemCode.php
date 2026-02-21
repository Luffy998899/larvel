<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemCode extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'code',
        'reward_type',
        'reward_value',
        'max_uses',
        'used_count',
        'per_user_limit',
        'expires_at',
        'is_active',
        'created_by_admin',
        'created_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
        'is_active' => 'boolean',
    ];
}
