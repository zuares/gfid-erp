<?php

namespace Tests\Feature\Sales;

use App\Models\Channel;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentDeletionRequest;
use App\Models\ShipmentLine;
use App\Models\ShipmentOrderScan;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentDeletionApprovalTest extends TestCase
{
    use RefreshDatabase;

    public function test_admin_deletion_waits_for_owner_approval_before_deleting_order(): void
    {
        [$shipment, $item] = $this->shipmentContext();
        $admin = $this->user('admin');
        $owner = $this->user('owner');
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-APPROVAL-001',
            'status' => 'pending',
            'source' => 'scanner',
        ]);
        $line = ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 2,
            'allocated_qty' => 2,
        ]);

        $this->actingAs($admin)
            ->postJson(route('sales.shipments.delete_order_scan', $shipment), ['order_no' => $scan->order_no])
            ->assertStatus(202)
            ->assertJsonPath('status', 'pending');

        $this->assertDatabaseHas('shipment_order_scans', ['id' => $scan->id]);
        $this->assertDatabaseHas('shipment_lines', ['id' => $line->id]);
        $this->assertDatabaseHas('shipment_deletion_requests', [
            'shipment_id' => $shipment->id,
            'target_type' => ShipmentDeletionRequest::TARGET_ORDER,
            'status' => ShipmentDeletionRequest::STATUS_PENDING,
        ]);

        $this->actingAs($owner)
            ->get(route('sales.shipments.deletion_requests.index'))
            ->assertOk()
            ->assertSee('ORDER-APPROVAL-001')
            ->assertSee('Menunggu approval hapus');

        $request = ShipmentDeletionRequest::firstOrFail();
        $this->actingAs($owner)
            ->post(route('sales.shipments.deletion_requests.approve', $request))
            ->assertRedirect();

        $this->assertDatabaseMissing('shipment_order_scans', ['id' => $scan->id]);
        $this->assertDatabaseMissing('shipment_lines', ['id' => $line->id]);
        $this->assertDatabaseHas('shipment_deletion_requests', [
            'id' => $request->id,
            'status' => ShipmentDeletionRequest::STATUS_APPROVED,
            'reviewed_by' => $owner->id,
        ]);
    }

    public function test_owner_can_reject_admin_item_deletion_and_item_remains(): void
    {
        [$shipment, $item] = $this->shipmentContext();
        $admin = $this->user('admin');
        $owner = $this->user('owner');
        $line = ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($admin)
            ->deleteJson(route('sales.shipments.destroy_line', $line))
            ->assertStatus(202)
            ->assertJsonPath('status', 'pending');

        $request = ShipmentDeletionRequest::firstOrFail();
        $this->actingAs($owner)
            ->post(route('sales.shipments.deletion_requests.reject', $request), ['review_note' => 'Cek ulang qty dulu'])
            ->assertRedirect();

        $this->assertDatabaseHas('shipment_lines', ['id' => $line->id]);
        $this->assertDatabaseHas('shipment_deletion_requests', [
            'id' => $request->id,
            'status' => ShipmentDeletionRequest::STATUS_REJECTED,
            'review_note' => 'Cek ulang qty dulu',
        ]);
    }

    public function test_admin_sees_clear_guidance_for_deletion_request_flow(): void
    {
        [$shipment, $item] = $this->shipmentContext();
        $admin = $this->user('admin');
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-UX-001',
            'status' => 'pending',
            'source' => 'scanner',
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('sales.shipments.scan_order', $shipment))
            ->assertOk()
            ->assertSee('Penghapusan perlu persetujuan owner')
            ->assertSee('Ajukan hapus');

        $this->actingAs($admin)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Penghapusan perlu persetujuan owner')
            ->assertSee('Ajukan hapus');
    }

    private function shipmentContext(): array
    {
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHP-DELETE-TEST',
            'name' => 'Delete Approval Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $item = Item::create([
            'code' => 'DELETE-TEST-001',
            'name' => 'Delete Approval Item',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-DELETE-APPROVAL-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        return [$shipment, $item];
    }

    private function user(string $role): User
    {
        return User::factory()->create([
            'employee_code' => strtoupper($role).'-'.uniqid(),
            'role' => $role,
        ]);
    }
}
