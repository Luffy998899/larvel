<?php

namespace Database\Seeders;

use App\Models\Credit;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class AdminUserSeeder extends Seeder
{
    public function run(): void
    {
        $email = (string) env('INITIAL_ADMIN_EMAIL', 'admin@example.com');
        $password = (string) env('INITIAL_ADMIN_PASSWORD', 'ChangeMe123!');

        $admin = User::query()->updateOrCreate(
            ['email' => $email],
            [
                'name' => 'Administrator',
                'password' => Hash::make($password),
                'register_ip' => '127.0.0.1',
                'is_admin' => true,
                'email_verified_at' => now(),
            ]
        );

        Credit::query()->firstOrCreate(
            ['user_id' => $admin->id],
            ['balance' => 0, 'total_earned' => 0, 'total_spent' => 0]
        );
    }
}
