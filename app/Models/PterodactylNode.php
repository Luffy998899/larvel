<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PterodactylNode extends Model
{
    protected $fillable = [
        'node_id',
        'name',
        'fqdn',
        'ip_address',
        'is_available',
        'last_seen_at',
    ];

    protected $casts = [
        'is_available' => 'boolean',
        'last_seen_at' => 'datetime',
    ];
}
