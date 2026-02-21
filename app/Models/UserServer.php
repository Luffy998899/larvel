<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class UserServer extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'user_id',
        'pterodactyl_server_id',
        'ram_allocated',
        'cpu_allocated',
        'disk_allocated',
        'cost_per_day',
        'next_billing_at',
        'status',
        'created_at',
    ];

    protected $casts = [
        'next_billing_at' => 'datetime',
        'created_at' => 'datetime',
    ];

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
