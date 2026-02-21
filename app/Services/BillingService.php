<?php

namespace App\Services;

use App\Models\CreditTransaction;
use App\Models\UserServer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class BillingService
{
    public function __construct(
        private readonly CreditService $creditService,
        private readonly PterodactylService $pterodactylService,
    ) {
    }

    public function process(): array
    {
        $charged = 0;
        $suspended = 0;
        $unsuspended = 0;

        UserServer::query()
            ->where('status', 'active')
            ->where('next_billing_at', '<=', now())
            ->with('user.credit')
            ->chunkById(100, function ($servers) use (&$charged, &$suspended) {
                foreach ($servers as $server) {
                    DB::transaction(function () use ($server, &$charged, &$suspended) {
                        $server = UserServer::query()->whereKey($server->id)->lockForUpdate()->firstOrFail();
                        $credit = $server->user->credit()->lockForUpdate()->first();

                        if (! $credit || $credit->balance < $server->cost_per_day) {
                            $this->pterodactylService->suspendServer((int) $server->pterodactyl_server_id);
                            $server->status = 'suspended';
                            $server->save();
                            $suspended++;
                            return;
                        }

                        $this->creditService->charge(
                            $server->user_id,
                            $server->cost_per_day,
                            CreditTransaction::TYPE_SERVER_CHARGE,
                            'Daily server charge #'.$server->id,
                            null
                        );

                        $server->next_billing_at = $server->next_billing_at->addDay();
                        $server->save();
                        $charged++;
                    }, 3);
                }
            });

        UserServer::query()
            ->where('status', 'suspended')
            ->with('user.credit')
            ->chunkById(100, function ($servers) use (&$unsuspended) {
                foreach ($servers as $server) {
                    $credit = $server->user->credit;
                    if ($credit && $credit->balance >= $server->cost_per_day) {
                        DB::transaction(function () use ($server, &$unsuspended) {
                            $server = UserServer::query()->whereKey($server->id)->lockForUpdate()->firstOrFail();
                            $credit = $server->user->credit()->lockForUpdate()->first();

                            if (! $credit || $credit->balance < $server->cost_per_day) {
                                return;
                            }

                            $this->creditService->charge(
                                $server->user_id,
                                $server->cost_per_day,
                                CreditTransaction::TYPE_SERVER_CHARGE,
                                'Auto-unsuspend daily charge #'.$server->id,
                                null
                            );

                            $api = $this->pterodactylService->unsuspendServer((int) $server->pterodactyl_server_id);
                            if (! $api['ok']) {
                                Log::warning('Unable to unsuspend Pterodactyl server', ['server_id' => $server->id, 'message' => $api['message']]);
                                return;
                            }

                            $server->status = 'active';
                            $server->next_billing_at = now()->addDay();
                            $server->save();
                            $unsuspended++;
                        }, 3);
                    }
                }
            });

        return [
            'charged' => $charged,
            'suspended' => $suspended,
            'unsuspended' => $unsuspended,
        ];
    }
}
