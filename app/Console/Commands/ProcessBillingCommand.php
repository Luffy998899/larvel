<?php

namespace App\Console\Commands;

use App\Services\BillingService;
use Illuminate\Console\Command;

class ProcessBillingCommand extends Command
{
    protected $signature = 'billing:process';

    protected $description = 'Process daily billing, suspensions, and auto-unsuspensions for user servers.';

    public function handle(BillingService $billingService): int
    {
        $result = $billingService->process();
        $this->info('Billing processed. charged='.$result['charged'].' suspended='.$result['suspended'].' unsuspended='.$result['unsuspended']);

        return self::SUCCESS;
    }
}
