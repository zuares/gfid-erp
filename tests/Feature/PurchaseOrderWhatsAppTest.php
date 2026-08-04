<?php

namespace Tests\Feature;

use App\Models\PurchaseOrder;
use App\Models\Supplier;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class PurchaseOrderWhatsAppTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_send_purchase_order_summary_to_supplier(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-WA-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-WA-' . uniqid(),
            'name' => 'Supplier Test',
            'phone' => '08123456789',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-WA-' . uniqid(),
            'date' => '2026-08-04',
            'supplier_id' => $supplier->id,
            'grand_total' => 125000,
            'status' => 'draft',
        ]);

        $service = Mockery::mock(WhatsAppMessageService::class);
        $service->shouldReceive('sendText')
            ->once()
            ->with('08123456789', Mockery::on(function (string $message) use ($order): bool {
                return str_contains($message, $order->code)
                    && str_contains($message, 'Supplier Test')
                    && str_contains($message, 'Rp125.000');
            }), Mockery::type('array'), 'Supplier Test', 'purchase_order_supplier')
            ->andReturnUsing(function () {
                $message = Mockery::mock(WhatsAppMessage::class);
                $message->shouldReceive('isSent')->andReturn(true);
                return $message;
            });
        $this->app->instance(WhatsAppMessageService::class, $service);

        $this->actingAs($owner)
            ->post(route('purchasing.purchase_orders.whatsapp', $order))
            ->assertRedirect()
            ->assertSessionHas('success');
    }

    public function test_owner_can_open_purchase_order_whatsapp_review_page(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-PO-WA-REVIEW-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-WA-REVIEW-' . uniqid(),
            'name' => 'Supplier Review',
            'phone' => '08123456789',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-WA-REVIEW-' . uniqid(),
            'date' => '2026-08-04',
            'supplier_id' => $supplier->id,
            'grand_total' => 250000,
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->get(route('whatsapp.messages.compose.purchase_order', $order))
            ->assertOk()
            ->assertSee('Review Pesan WhatsApp')
            ->assertSee($order->code)
            ->assertSee('Supplier Review');
    }

    public function test_purchase_order_whatsapp_requires_supplier_phone(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-PO-WA-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-NO-WA-' . uniqid(),
            'name' => 'Supplier Tanpa Nomor',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-NO-WA-' . uniqid(),
            'date' => '2026-08-04',
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        $service = Mockery::mock(WhatsAppMessageService::class);
        $service->shouldNotReceive('sendText');
        $this->app->instance(WhatsAppMessageService::class, $service);

        $this->actingAs($admin)
            ->post(route('purchasing.purchase_orders.whatsapp', $order))
            ->assertRedirect()
            ->assertSessionHas('error', 'Nomor WhatsApp supplier belum diisi.');
    }

    public function test_operating_user_cannot_send_purchase_order_whatsapp(): void
    {
        $operating = User::factory()->create([
            'role' => 'operating',
            'employee_code' => 'OPERATING-PO-WA-' . uniqid(),
        ]);
        $supplier = Supplier::create([
            'code' => 'SUP-PO-FORBIDDEN-' . uniqid(),
            'name' => 'Supplier Forbidden',
            'phone' => '08123456789',
        ]);
        $order = PurchaseOrder::create([
            'code' => 'PO-FORBIDDEN-' . uniqid(),
            'date' => '2026-08-04',
            'supplier_id' => $supplier->id,
            'status' => 'draft',
        ]);

        $this->actingAs($operating)
            ->post(route('purchasing.purchase_orders.whatsapp', $order))
            ->assertForbidden();
    }
}
