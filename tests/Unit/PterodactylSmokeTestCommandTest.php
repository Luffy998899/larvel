<?php

namespace Tests\Unit;

use App\Models\PterodactylNode;
use App\Services\PterodactylService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PterodactylSmokeTestCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_command_fails_when_credentials_are_missing(): void
    {
        config()->set('services.pterodactyl.url', null);
        config()->set('services.pterodactyl.api_key', null);

        $this->artisan('ptero:smoke-test')
            ->expectsOutput('Missing Pterodactyl credentials. Set PTERO_URL and PTERO_API_KEY in .env')
            ->assertExitCode(1);
    }

    public function test_command_passes_when_node_resource_check_is_successful(): void
    {
        config()->set('services.pterodactyl.url', 'https://panel.example.com');
        config()->set('services.pterodactyl.api_key', 'ptla_live_example_key_1234');
        config()->set('services.pterodactyl.node_id', 1);

        $mock = Mockery::mock(PterodactylService::class);
        $mock->shouldReceive('listNodes')
            ->once()
            ->withNoArgs()
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => 'OK',
                'data' => [
                    'data' => [
                        ['attributes' => ['id' => 1, 'name' => 'Eu1', 'fqdn' => '1.1.1.1']],
                        ['attributes' => ['id' => 2, 'name' => 'Us1', 'fqdn' => '8.8.8.8']],
                    ],
                ],
            ]);
        $mock->shouldReceive('getNodeResources')
            ->once()
            ->with(1)
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => 'OK',
                'data' => ['attributes' => ['name' => 'Node-1']],
            ]);

        $this->app->instance(PterodactylService::class, $mock);

        $this->artisan('ptero:smoke-test')
            ->expectsOutput('Running Pterodactyl smoke check...')
            ->expectsOutput('URL: https://panel.example.com')
            ->expectsOutput('Node ID: 1')
            ->expectsOutput('Detected Nodes: 2')
            ->expectsOutput('- #1 Eu1 (1.1.1.1) IP: 1.1.1.1')
            ->expectsOutput('- #2 Us1 (8.8.8.8) IP: 8.8.8.8')
            ->expectsOutput('Pterodactyl connectivity check passed.')
            ->assertExitCode(0);

        $this->assertDatabaseHas('pterodactyl_nodes', [
            'node_id' => 1,
            'name' => 'Eu1',
            'fqdn' => '1.1.1.1',
            'ip_address' => '1.1.1.1',
            'is_available' => 1,
        ]);
        $this->assertDatabaseHas('pterodactyl_nodes', [
            'node_id' => 2,
            'name' => 'Us1',
            'fqdn' => '8.8.8.8',
            'ip_address' => '8.8.8.8',
            'is_available' => 1,
        ]);
    }

    public function test_command_fails_when_configured_node_is_not_present(): void
    {
        config()->set('services.pterodactyl.url', 'https://panel.example.com');
        config()->set('services.pterodactyl.api_key', 'ptla_live_example_key_1234');
        config()->set('services.pterodactyl.node_id', 9);

        $mock = Mockery::mock(PterodactylService::class);
        $mock->shouldReceive('listNodes')
            ->once()
            ->andReturn([
                'ok' => true,
                'status' => 200,
                'message' => 'OK',
                'data' => [
                    'data' => [
                        ['attributes' => ['id' => 1, 'name' => 'Eu1', 'fqdn' => '1.1.1.1']],
                        ['attributes' => ['id' => 2, 'name' => 'Us1', 'fqdn' => '8.8.8.8']],
                    ],
                ],
            ]);

        $this->app->instance(PterodactylService::class, $mock);

        $this->artisan('ptero:smoke-test')
            ->expectsOutput('Configured node id 9 was not found in panel nodes list.')
            ->assertExitCode(1);
    }
}
