<?php

namespace Tests\Unit;

use App\Services\PterodactylService;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class PterodactylServiceTest extends TestCase
{
    protected function setUp(): void
    {
        parent::setUp();

        config()->set('services.pterodactyl.url', 'https://panel.example.com');
        config()->set('services.pterodactyl.api_key', 'ptla_test_key');
    }

    public function test_create_server_success_response_is_structured(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/servers' => Http::response([
                'attributes' => ['id' => 101],
            ], 201),
        ]);

        $service = app(PterodactylService::class);
        $result = $service->createServer(['name' => 'Test']);

        $this->assertTrue($result['ok']);
        $this->assertSame(201, $result['status']);
        $this->assertSame(101, data_get($result, 'data.attributes.id'));

        Http::assertSent(function ($request) {
            return $request->url() === 'https://panel.example.com/api/application/servers'
                && $request->method() === 'POST'
                && $request->hasHeader('Authorization', 'Bearer ptla_test_key');
        });
    }

    public function test_suspend_unsuspend_delete_and_node_resources_use_correct_endpoints(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/servers/55/suspend' => Http::response([], 204),
            'https://panel.example.com/api/application/servers/55/unsuspend' => Http::response([], 204),
            'https://panel.example.com/api/application/servers/55' => Http::response([], 204),
            'https://panel.example.com/api/application/nodes/9' => Http::response(['attributes' => ['id' => 9]], 200),
        ]);

        $service = app(PterodactylService::class);

        $suspend = $service->suspendServer(55);
        $unsuspend = $service->unsuspendServer(55);
        $delete = $service->deleteServer(55);
        $node = $service->getNodeResources(9);

        $this->assertTrue($suspend['ok']);
        $this->assertSame(204, $suspend['status']);
        $this->assertTrue($unsuspend['ok']);
        $this->assertSame(204, $unsuspend['status']);
        $this->assertTrue($delete['ok']);
        $this->assertSame(204, $delete['status']);
        $this->assertTrue($node['ok']);
        $this->assertSame(200, $node['status']);
        $this->assertSame(9, data_get($node, 'data.attributes.id'));

        Http::assertSentCount(4);
    }

    public function test_api_failure_returns_clean_error_structure(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/servers/77/suspend' => Http::response([
                'errors' => [
                    ['detail' => 'Server not found'],
                ],
            ], 404),
        ]);

        $service = app(PterodactylService::class);
        $result = $service->suspendServer(77);

        $this->assertFalse($result['ok']);
        $this->assertSame(404, $result['status']);
        $this->assertSame('Server not found', $result['message']);
    }

    public function test_list_nodes_calls_application_nodes_endpoint(): void
    {
        Http::fake([
            'https://panel.example.com/api/application/nodes?per_page=100' => Http::response([
                'data' => [
                    ['attributes' => ['id' => 1, 'name' => 'Eu1']],
                    ['attributes' => ['id' => 2, 'name' => 'Us1']],
                ],
            ], 200),
        ]);

        $service = app(PterodactylService::class);
        $result = $service->listNodes();

        $this->assertTrue($result['ok']);
        $this->assertSame(200, $result['status']);
        $this->assertCount(2, (array) data_get($result, 'data.data'));
    }
}
