<?php

namespace Tests\Feature;

use App\Models\WhatsAppMessage;
use App\Models\User;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppBroadcastTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_send_broadcast_to_manual_numbers(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-BROADCAST-' . uniqid(),
        ]);
        $service = Mockery::mock(WhatsAppMessageService::class);
        $service->shouldReceive('sendText')
            ->once()
            ->with('628123456789', 'Halo Test Recipient', Mockery::type('array'), 'Test Recipient', null)
            ->andReturnUsing(function () {
                $message = Mockery::mock(WhatsAppMessage::class);
                $message->shouldReceive('isSent')->andReturn(true);
                return $message;
            });
        $this->app->instance(WhatsAppMessageService::class, $service);

        $this->actingAs($owner)
            ->post(route('whatsapp.broadcast.send'), [
                'manual_phones' => ['08123456789'],
                'message' => 'Halo {customer_name}',
            ])
            ->assertRedirect(route('whatsapp.broadcast'))
            ->assertSessionHas('success', 'Broadcast selesai: 1 terkirim.');
    }

    public function test_owner_can_send_broadcast_to_imported_order_recipient(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-IMPORT-BROADCAST-' . uniqid(),
        ]);
        $service = Mockery::mock(WhatsAppMessageService::class);
        $service->shouldReceive('sendText')
            ->once()
            ->with('628987654321', 'Halo Buyer Import', Mockery::type('array'), 'Buyer Import', null)
            ->andReturnUsing(function () {
                $message = Mockery::mock(WhatsAppMessage::class);
                $message->shouldReceive('isSent')->andReturn(true);
                return $message;
            });
        $this->app->instance(WhatsAppMessageService::class, $service);

        $this->actingAs($owner)
            ->post(route('whatsapp.broadcast.send'), [
                'recipient_phones' => ['08987654321'],
                'recipient_names' => ['Buyer Import'],
                'message' => 'Halo {customer_name}',
            ])
            ->assertRedirect(route('whatsapp.broadcast'))
            ->assertSessionHas('success', 'Broadcast selesai: 1 terkirim.');
    }
}
