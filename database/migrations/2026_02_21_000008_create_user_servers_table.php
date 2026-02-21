<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration {
    public function up(): void
    {
        Schema::create('user_servers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete()->index();
            $table->unsignedBigInteger('pterodactyl_server_id')->unique();
            $table->unsignedInteger('ram_allocated');
            $table->unsignedInteger('cpu_allocated');
            $table->unsignedInteger('disk_allocated');
            $table->unsignedInteger('cost_per_day');
            $table->timestamp('next_billing_at')->index();
            $table->enum('status', ['active', 'suspended'])->default('active')->index();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('user_servers');
    }
};
