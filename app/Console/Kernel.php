<?php

namespace App\Console;

use App\Console\Commands\PterodactylSmokeTestCommand;
use App\Console\Commands\ProcessBillingCommand;
use Illuminate\Console\Scheduling\Schedule;
use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected $commands = [
        ProcessBillingCommand::class,
        PterodactylSmokeTestCommand::class,
    ];

    protected function schedule(Schedule $schedule): void
    {
        $schedule->command('billing:process')->hourly()->withoutOverlapping()->onOneServer();
    }

    protected function commands(): void
    {
        $this->load(__DIR__.'/Commands');
        require base_path('routes/console.php');
    }
}
