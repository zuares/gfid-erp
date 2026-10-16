<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Models\WhatsAppTemplate;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_whatsapp_center_and_see_default_templates(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-WA-CENTER-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('whatsapp.index'))
            ->assertOk()
            ->assertSee('WhatsApp Center')
            ->assertSee('Purchase Order ke Supplier')
            ->assertSee('Riwayat pesan keluar');
    }

    public function test_admin_can_create_and_update_whatsapp_template(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-WA-TEMPLATE-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->post(route('whatsapp.templates.store'), [
                'key' => 'shipment_customer_update',
                'name' => 'Update Pengiriman',
                'body' => 'Halo {customer_name}, pesanan {order_code} sedang dikirim.',
                'description' => 'Update status pengiriman customer.',
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $template = WhatsAppTemplate::where('key', 'shipment_customer_update')->firstOrFail();
        $this->assertTrue($template->is_active);

        $this->actingAs($admin)
            ->put(route('whatsapp.templates.update', $template), [
                'name' => 'Update Pengiriman Customer',
                'body' => 'Pesanan {order_code} sudah dikirim.',
                'description' => 'Versi singkat.',
                'is_active' => 0,
            ])
            ->assertRedirect()
            ->assertSessionHas('success');

        $this->assertFalse($template->fresh()->is_active);
        $this->assertSame('Update Pengiriman Customer', $template->fresh()->name);
    }

    public function test_admin_can_open_broadcast_page(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-WA-BROADCAST-'.uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('whatsapp.broadcast'))
            ->assertOk()
            ->assertSee('Broadcast WhatsApp')
            ->assertSee('Nomor tujuan');
    }

    public function test_admin_can_send_broadcast_to_unique_normalized_numbers(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-WA-BROADCAST-SEND-'.uniqid(),
        ]);
        $service = Mockery::mock(WhatsAppMessageService::class);
        $service->shouldReceive('sendBroadcast')
            ->once()
            ->with(
                ['628123456789', '628987654321'],
                'Pesan broadcast',
                Mockery::type('array'),
                null,
            )
            ->andReturn([
                new WhatsAppMessage(['status' => 'sent']),
                new WhatsAppMessage(['status' => 'sent']),
            ]);
        $this->app->instance(WhatsAppMessageService::class, $service);

        $this->actingAs($admin)
            ->post(route('whatsapp.broadcast.send'), [
                'recipients' => "08123456789\n628123456789, 08987654321",
                'message' => 'Pesan broadcast',
            ])
            ->assertRedirect(route('whatsapp.index'))
            ->assertSessionHas('success', 'Broadcast selesai: 2 terkirim, 0 gagal dari 2 nomor.');
    }
}
