<?php

namespace App\Console\Commands;

use App\Services\PterodactylNodeService;
use App\Services\PterodactylService;
use Illuminate\Console\Command;

class PterodactylSmokeTestCommand extends Command
{
    protected $signature = 'ptero:smoke-test
                            {--node= : Override node id for the check}
                            {--force : Allow running in production}';

    protected $description = 'Run a safe live connectivity check against Pterodactyl Application API.';

    public function handle(PterodactylService $pterodactylService, PterodactylNodeService $pterodactylNodeService): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Refusing to run in production without --force.');
            return self::FAILURE;
        }

        $baseUrl = (string) config('services.pterodactyl.url');
        $apiKey = (string) config('services.pterodactyl.api_key');
        $nodeId = (int) ($this->option('node') ?: config('services.pterodactyl.node_id', 1));

        if ($baseUrl === '' || $apiKey === '') {
            $this->error('Missing Pterodactyl credentials. Set PTERO_URL and PTERO_API_KEY in .env');
            return self::FAILURE;
        }

        $this->line('Running Pterodactyl smoke check...');
        $this->line('URL: '.$baseUrl);
        $this->line('API Key: '.$this->maskApiKey($apiKey));
        $this->line('Node ID: '.$nodeId);

        $nodesResult = $pterodactylService->listNodes();
        if (! $nodesResult['ok']) {
            $this->error('Unable to list panel nodes.');
            $this->error('Status: '.(string) $nodesResult['status']);
            $this->error('Message: '.(string) $nodesResult['message']);
            return self::FAILURE;
        }

        $nodes = collect((array) data_get($nodesResult, 'data.data', []))
            ->map(fn (array $node) => [
                'id' => (int) data_get($node, 'attributes.id', 0),
                'name' => (string) data_get($node, 'attributes.name', 'N/A'),
                'fqdn' => (string) data_get($node, 'attributes.fqdn', 'N/A'),
                'ip_address' => $this->resolveIp((string) data_get($node, 'attributes.fqdn', '')),
            ])
            ->values();

        $pterodactylNodeService->syncAvailableNodes($nodes->all());

        $this->line('Detected Nodes: '.$nodes->count());
        foreach ($nodes as $node) {
            $this->line('- #'.$node['id'].' '.$node['name'].' ('.$node['fqdn'].') IP: '.($node['ip_address'] ?: 'N/A'));
        }

        if (! $nodes->contains(fn (array $node) => $node['id'] === $nodeId)) {
            $this->error('Configured node id '.$nodeId.' was not found in panel nodes list.');
            return self::FAILURE;
        }

        $result = $pterodactylService->getNodeResources($nodeId);

        if (! $result['ok']) {
            $this->error('Pterodactyl connectivity check failed.');
            $this->error('Status: '.(string) $result['status']);
            $this->error('Message: '.(string) $result['message']);
            return self::FAILURE;
        }

        $this->info('Pterodactyl connectivity check passed.');
        $this->line('HTTP Status: '.(string) $result['status']);
        $this->line('Node Name: '.(string) data_get($result, 'data.attributes.name', 'N/A'));
        $this->line('Node list synced to database table: pterodactyl_nodes');

        return self::SUCCESS;
    }

    private function resolveIp(string $fqdn): ?string
    {
        if ($fqdn === '') {
            return null;
        }

        if (filter_var($fqdn, FILTER_VALIDATE_IP)) {
            return $fqdn;
        }

        $resolved = gethostbyname($fqdn);

        if ($resolved === $fqdn || ! filter_var($resolved, FILTER_VALIDATE_IP)) {
            return null;
        }

        return $resolved;
    }

    private function maskApiKey(string $apiKey): string
    {
        if (strlen($apiKey) <= 8) {
            return str_repeat('*', strlen($apiKey));
        }

        return substr($apiKey, 0, 4).str_repeat('*', max(strlen($apiKey) - 8, 4)).substr($apiKey, -4);
    }
}
