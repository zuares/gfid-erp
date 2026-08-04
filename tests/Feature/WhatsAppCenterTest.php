<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\WhatsAppTemplate;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class WhatsAppCenterTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_can_open_whatsapp_center_and_see_default_templates(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-WA-CENTER-' . uniqid(),
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
            'employee_code' => 'ADMIN-WA-TEMPLATE-' . uniqid(),
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
}
