<?php

namespace App\Services;

use App\Models\User;
use App\Models\UserServer;
use App\Repositories\SystemSettingRepository;
use Illuminate\Support\Facades\DB;
use RuntimeException;

class ServerProvisionService
{
    public function __construct(
        private readonly PterodactylService $pterodactylService,
        private readonly SystemSettingRepository $settingRepository,
    ) {
    }

    public function claimFreeServer(User $user): UserServer
    {
        return DB::transaction(function () use ($user) {
            $maxServers = $this->settingRepository->getInt('max_free_servers_per_user', 1);
            $existingCount = UserServer::query()->where('user_id', $user->id)->lockForUpdate()->count();
            if ($existingCount >= $maxServers) {
                throw new RuntimeException('You reached max free servers limit.');
            }

            $ram = $this->settingRepository->getInt('free_server_ram', 3072);
            $cpu = $this->settingRepository->getInt('free_server_cpu', 30);
            $disk = $this->settingRepository->getInt('free_server_disk', 10000);
            $costPerDay = $this->settingRepository->getInt('server_cost_per_day', 40);

            $api = $this->pterodactylService->createServer([
                'name' => 'Free-'.$user->id.'-'.now()->timestamp,
                'user' => $user->id,
                'egg' => (int) config('services.pterodactyl.egg_id'),
                'docker_image' => (string) config('services.pterodactyl.docker_image'),
                'startup' => 'java -Xms128M -Xmx{{SERVER_MEMORY}}M -jar server.jar',
                'environment' => [
                    'SERVER_JARFILE' => 'server.jar',
                    'MINECRAFT_VERSION' => 'latest',
                ],
                'limits' => [
                    'memory' => $ram,
                    'swap' => 0,
                    'disk' => $disk,
                    'io' => 500,
                    'cpu' => $cpu,
                ],
                'feature_limits' => [
                    'databases' => 1,
                    'allocations' => 1,
                    'backups' => 1,
                ],
                'allocation' => [
                    'default' => (int) config('services.pterodactyl.node_id'),
                ],
            ]);

            if (! $api['ok']) {
                throw new RuntimeException($api['message']);
            }

            $externalId = data_get($api, 'data.attributes.id');
            if (! $externalId) {
                throw new RuntimeException('Unexpected API response when creating server.');
            }

            return UserServer::query()->create([
                'user_id' => $user->id,
                'pterodactyl_server_id' => (int) $externalId,
                'ram_allocated' => $ram,
                'cpu_allocated' => $cpu,
                'disk_allocated' => $disk,
                'cost_per_day' => $costPerDay,
                'next_billing_at' => now()->addDay(),
                'status' => 'active',
                'created_at' => now(),
            ]);
        });
    }
}
