<?php

namespace App\Services;

use App\Models\PterodactylNode;
use Illuminate\Support\Facades\DB;

class PterodactylNodeService
{
    public function syncAvailableNodes(array $nodes): void
    {
        DB::transaction(function () use ($nodes) {
            PterodactylNode::query()->update(['is_available' => false]);

            $payload = collect($nodes)
                ->map(fn (array $node) => [
                    'node_id' => (int) ($node['id'] ?? 0),
                    'name' => (string) ($node['name'] ?? 'N/A'),
                    'fqdn' => (string) ($node['fqdn'] ?? 'N/A'),
                    'ip_address' => isset($node['ip_address']) ? (string) $node['ip_address'] : null,
                    'is_available' => true,
                    'last_seen_at' => now(),
                    'updated_at' => now(),
                    'created_at' => now(),
                ])
                ->filter(fn (array $node) => $node['node_id'] > 0)
                ->values()
                ->all();

            if (! empty($payload)) {
                PterodactylNode::query()->upsert(
                    $payload,
                    ['node_id'],
                    ['name', 'fqdn', 'ip_address', 'is_available', 'last_seen_at', 'updated_at']
                );
            }
        });
    }
}
