<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CreditTransaction extends Model
{
    public const TYPE_AD = 'ad';
    public const TYPE_REDEEM = 'redeem';
    public const TYPE_DAILY_LOGIN = 'daily_login';
    public const TYPE_SERVER_CHARGE = 'server_charge';
    public const TYPE_ADMIN_ADJUST = 'admin_adjust';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'type',
        'amount',
        'description',
        'ip_address',
        'created_at',
    ];

    protected $casts = [
        'amount' => 'integer',
        'created_at' => 'datetime',
    ];
}
