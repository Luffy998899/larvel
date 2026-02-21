<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class RedeemLog extends Model
{
    protected $table = 'redeem_logs';

    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'code_id',
        'created_at',
    ];
}
