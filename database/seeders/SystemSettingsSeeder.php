<?php

namespace Database\Seeders;

use App\Models\SystemSetting;
use Illuminate\Database\Seeder;

class SystemSettingsSeeder extends Seeder
{
    public function run(): void
    {
        $defaults = [
            'free_server_ram' => '3072',
            'free_server_cpu' => '30',
            'free_server_disk' => '10000',
            'server_cost_per_day' => '40',
            'credits_per_ad' => '10',
            'max_ads_per_day' => '5',
            'daily_login_credits' => '5',
            'initial_signup_credits' => '20',
            'max_free_servers_per_user' => '1',
        ];

        foreach ($defaults as $key => $value) {
            SystemSetting::query()->updateOrCreate(['key' => $key], ['value' => $value]);
        }
    }
}
