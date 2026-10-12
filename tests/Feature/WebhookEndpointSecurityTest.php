<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WebhookLog;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WebhookEndpointSecurityTest extends TestCase
{
    use RefreshDatabase;

    public function test_webhook_logs_detail_and_simulate_require_web_authentication(): void
    {
        $log = WebhookLog::create([
            'provider' => 'shopee',
            'event_type' => 'verify',
            'signature_verified' => false,
            'payload' => ['code' => 0],
            'ip_address' => '127.0.0.1',
        ]);

        $this->getJson('/api/webhooks/logs')->assertUnauthorized();
        $this->getJson('/api/webhooks/logs/'.$log->id)->assertUnauthorized();
        $this->postJson('/api/webhooks/simulate', ['provider' => 'shopee'])->assertUnauthorized();
    }

    public function test_shopee_callback_remains_public(): void
    {
        $this->postJson('/api/webhooks/shopee', ['code' => 0])
            ->assertOk()
            ->assertSeeText('OK');

        $this->assertDatabaseHas('webhook_logs', [
            'provider' => 'shopee',
            'event_type' => 'verify',
        ]);
    }

    public function test_simulate_remains_available_to_marketplace_owner(): void
    {
        $this->actingAs(User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'WEBHOOK-OWNER',
        ]));

        $this->postJson('/api/webhooks/simulate', [
            'provider' => 'unsupported-test-provider',
        ])->assertStatus(400);
    }
}
