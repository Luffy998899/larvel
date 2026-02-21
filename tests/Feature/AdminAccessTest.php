<?php

namespace Tests\Feature;

use App\Models\Credit;
use App\Models\RedeemCode;
use App\Models\User;
use App\Models\UserServer;
use App\Services\PterodactylService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Mockery;
use Tests\TestCase;

class AdminAccessTest extends TestCase
{
    use RefreshDatabase;

    private function createAdmin(bool $verified = true): User
    {
        return User::query()->create([
            'name' => 'Admin User',
            'email' => 'admin'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'register_ip' => '127.0.0.1',
            'is_admin' => true,
            'email_verified_at' => $verified ? now() : null,
        ]);
    }

    private function createRegularUser(bool $verified = true): User
    {
        return User::query()->create([
            'name' => 'Regular User',
            'email' => 'user'.uniqid().'@example.com',
            'password' => Hash::make('password123'),
            'register_ip' => '127.0.0.1',
            'is_admin' => false,
            'email_verified_at' => $verified ? now() : null,
        ]);
    }

    public function test_guest_is_redirected_from_admin_dashboard(): void
    {
        $this->get('/admin')->assertRedirect('/login');
    }

    public function test_verified_non_admin_cannot_access_admin_dashboard(): void
    {
        $user = $this->createRegularUser(true);

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_unverified_admin_cannot_access_admin_dashboard(): void
    {
        $user = $this->createAdmin(false);

        $this->actingAs($user)
            ->get('/admin')
            ->assertStatus(403);
    }

    public function test_verified_admin_can_access_admin_dashboard(): void
    {
        $user = $this->createAdmin(true);

        $this->actingAs($user)
            ->get('/admin')
            ->assertOk();
    }

    public function test_verified_admin_can_view_and_update_settings(): void
    {
        $admin = $this->createAdmin(true);

        $this->actingAs($admin)->get('/admin/settings')->assertOk();

        $this->actingAs($admin)->post('/admin/settings', [
            'settings' => [
                'credits_per_ad' => 22,
                'max_ads_per_day' => 8,
                'unknown_key' => 777,
            ],
        ])->assertRedirect(route('admin.settings'));

        $this->assertDatabaseHas('system_settings', ['key' => 'credits_per_ad', 'value' => '22']);
        $this->assertDatabaseHas('system_settings', ['key' => 'max_ads_per_day', 'value' => '8']);
        $this->assertDatabaseMissing('system_settings', ['key' => 'unknown_key']);
    }

    public function test_verified_admin_can_create_update_and_delete_redeem_code(): void
    {
        $admin = $this->createAdmin(true);

        $this->actingAs($admin)->post('/admin/redeem-codes', [
            'code' => 'SPRING2026',
            'reward_value' => 30,
            'max_uses' => 100,
            'per_user_limit' => 1,
            'is_active' => 1,
        ])->assertRedirect(route('admin.redeem-codes'));

        $code = RedeemCode::query()->where('code', 'SPRING2026')->firstOrFail();
        $this->assertDatabaseHas('redeem_codes', ['id' => $code->id, 'reward_value' => 30]);

        $this->actingAs($admin)->put('/admin/redeem-codes/'.$code->id, [
            'reward_value' => 40,
            'max_uses' => 200,
            'per_user_limit' => 2,
            'is_active' => 0,
        ])->assertRedirect(route('admin.redeem-codes'));

        $this->assertDatabaseHas('redeem_codes', ['id' => $code->id, 'reward_value' => 40, 'is_active' => 0]);

        $this->actingAs($admin)->delete('/admin/redeem-codes/'.$code->id)
            ->assertRedirect(route('admin.redeem-codes'));

        $this->assertDatabaseMissing('redeem_codes', ['id' => $code->id]);
    }

    public function test_verified_admin_can_adjust_user_credits(): void
    {
        $admin = $this->createAdmin(true);
        $target = $this->createRegularUser(true);

        Credit::query()->create([
            'user_id' => $target->id,
            'balance' => 100,
            'total_earned' => 100,
            'total_spent' => 0,
        ]);

        $this->actingAs($admin)->post('/admin/credits/adjust', [
            'user_id' => $target->id,
            'amount' => 25,
            'description' => 'Manual top up',
        ])->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('credits', ['user_id' => $target->id, 'balance' => 125]);
        $this->assertDatabaseHas('credit_transactions', [
            'user_id' => $target->id,
            'type' => 'admin_adjust',
            'amount' => 25,
        ]);
    }

    public function test_verified_admin_can_force_suspend_and_delete_server(): void
    {
        $admin = $this->createAdmin(true);
        $target = $this->createRegularUser(true);

        $server = UserServer::query()->create([
            'user_id' => $target->id,
            'pterodactyl_server_id' => 99901,
            'ram_allocated' => 3072,
            'cpu_allocated' => 30,
            'disk_allocated' => 10000,
            'cost_per_day' => 40,
            'next_billing_at' => now()->addDay(),
            'status' => 'active',
            'created_at' => now(),
        ]);

        $mock = Mockery::mock(PterodactylService::class);
        $mock->shouldReceive('suspendServer')->once()->with(99901)->andReturn(['ok' => true]);
        $mock->shouldReceive('deleteServer')->once()->with(99901)->andReturn(['ok' => true]);
        $this->app->instance(PterodactylService::class, $mock);

        $this->actingAs($admin)->post('/admin/servers/'.$server->id.'/suspend')
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseHas('user_servers', ['id' => $server->id, 'status' => 'suspended']);

        $this->actingAs($admin)->delete('/admin/servers/'.$server->id)
            ->assertRedirect(route('admin.dashboard'));

        $this->assertDatabaseMissing('user_servers', ['id' => $server->id]);
    }

    public function test_verified_non_admin_cannot_call_admin_management_endpoints(): void
    {
        $user = $this->createRegularUser(true);

        $this->actingAs($user)->post('/admin/settings', ['settings' => ['credits_per_ad' => 12]])->assertStatus(403);
        $this->actingAs($user)->post('/admin/redeem-codes', [
            'code' => 'NOPE',
            'reward_value' => 10,
            'max_uses' => 1,
            'per_user_limit' => 1,
            'is_active' => 1,
        ])->assertStatus(403);
        $this->actingAs($user)->post('/admin/credits/adjust', ['user_id' => $user->id, 'amount' => 1])->assertStatus(403);
    }
}
