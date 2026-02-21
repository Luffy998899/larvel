<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('pterodactyl_nodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('node_id')->unique();
            $table->string('name');
            $table->string('fqdn');
            $table->string('ip_address', 45)->nullable();
            $table->boolean('is_available')->default(true)->index();
            $table->timestamp('last_seen_at')->nullable()->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pterodactyl_nodes');
    }
};
