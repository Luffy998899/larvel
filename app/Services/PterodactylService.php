<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;
use Throwable;

class PterodactylService
{
    public function listNodes(int $perPage = 100): array
    {
        return $this->request('get', '/api/application/nodes?per_page='.$perPage);
    }

    public function createServer(array $payload): array
    {
        return $this->request('post', '/api/application/servers', $payload);
    }

    public function suspendServer(int $serverId): array
    {
        return $this->request('post', "/api/application/servers/{$serverId}/suspend");
    }

    public function unsuspendServer(int $serverId): array
    {
        return $this->request('post', "/api/application/servers/{$serverId}/unsuspend");
    }

    public function deleteServer(int $serverId): array
    {
        return $this->request('delete', "/api/application/servers/{$serverId}");
    }

    public function getNodeResources(int $nodeId): array
    {
        return $this->request('get', "/api/application/nodes/{$nodeId}");
    }

    private function request(string $method, string $uri, array $payload = []): array
    {
        try {
            $response = Http::acceptJson()
                ->withHeaders([
                    'Authorization' => 'Bearer '.config('services.pterodactyl.api_key'),
                    'Content-Type' => 'application/json',
                ])
                ->baseUrl(rtrim((string) config('services.pterodactyl.url'), '/'))
                ->timeout(15)
                ->send(strtoupper($method), $uri, $payload ? ['json' => $payload] : []);

            if ($response->failed()) {
                return [
                    'ok' => false,
                    'status' => $response->status(),
                    'message' => $response->json('errors.0.detail') ?? 'Pterodactyl API request failed.',
                    'data' => $response->json(),
                ];
            }

            return [
                'ok' => true,
                'status' => $response->status(),
                'message' => 'OK',
                'data' => $response->json(),
            ];
        } catch (Throwable $exception) {
            report($exception);

            return [
                'ok' => false,
                'status' => 500,
                'message' => $exception->getMessage(),
                'data' => null,
            ];
        }
    }
}
