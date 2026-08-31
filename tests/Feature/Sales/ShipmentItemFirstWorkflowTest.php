<?php

namespace Tests\Feature\Sales;

use App\Models\Channel;
use App\Models\Item;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\ShipmentOrderScan;
use App\Models\Store;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentItemFirstWorkflowTest extends TestCase
{
    use RefreshDatabase;

    public function test_item_first_allocation_is_persisted_per_order_and_keeps_batch_qty(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-ITEM-FIRST-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $orderA = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-A',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        $orderB = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-B',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 5,
            'allocated_qty' => 5,
        ]);

        $this->actingAs($user)
            ->putJson(route('sales.shipments.rekon_update_scan', [$shipment, $orderA->order_no]), [
                'decision' => 'fulfill',
                'allocations' => [['item_id' => $item->id, 'qty' => 2]],
            ])
            ->assertOk();

        $this->actingAs($user)
            ->putJson(route('sales.shipments.rekon_update_scan', [$shipment, $orderB->order_no]), [
                'decision' => 'fulfill',
                'allocations' => [['item_id' => $item->id, 'qty' => 3]],
            ])
            ->assertOk();

        $this->assertDatabaseCount('shipment_lines', 2);
        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $orderA->id,
            'item_id' => $item->id,
            'qty_scanned' => 2,
            'allocated_qty' => 2,
        ]);
        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $orderB->id,
            'item_id' => $item->id,
            'qty_scanned' => 3,
            'allocated_qty' => 3,
        ]);
        $this->assertSame(5, (int) ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned'));
    }

    public function test_item_first_confirmation_shows_mapping_error_when_item_is_unassigned(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-ITEM-FIRST-002',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-UNMAPPED',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-SECOND',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.rekon', $shipment))
            ->assertOk()
            ->assertSee('Alokasikan Item ke Order');

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Shipment belum siap dikirim')
            ->assertSee('belum terhubung ke order');
    }

    public function test_daily_shipment_creates_wave_and_blocks_legacy_submit(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-DAILY-WAVE-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'dispatch_mode' => Shipment::DISPATCH_DAILY,
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $line = ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 2,
            'allocated_qty' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.edit', $shipment))
            ->assertOk()
            ->assertSee('Siang');

        $this->assertDatabaseHas('shipment_waves', [
            'shipment_id' => $shipment->id,
            'sequence' => 1,
            'label' => 'Siang',
            'status' => 'open',
        ]);
        $this->assertNotNull($line->fresh()->shipment_wave_id);

        $this->actingAs($user)
            ->post(route('sales.shipments.submit', $shipment))
            ->assertRedirect(route('sales.shipments.confirm_orders', $shipment))
            ->assertSessionHas('message');

        $this->assertDatabaseHas('shipments', [
            'id' => $shipment->id,
            'status' => 'draft',
        ]);
    }

    public function test_unknown_marketplace_order_keeps_the_scanned_number(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-UNKNOWN-ORDER-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $unknownOrderNo = 'MANUAL-NOT-IN-MARKETPLACE-001';
        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $unknownOrderNo,
            ])
            ->assertOk()
            ->assertJsonPath('order.code', $unknownOrderNo)
            ->assertJsonPath('order.order_no', $unknownOrderNo);

        $this->assertDatabaseHas('shipment_order_scans', [
            'shipment_id' => $shipment->id,
            'order_no' => $unknownOrderNo,
            'fulfillment_id' => null,
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => 'undefined',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Nomor order belum diisi.');
    }

    private function shipmentContext(): array
    {
        $user = User::factory()->create([
            'employee_code' => 'SIF-' . strtoupper(substr(uniqid(), -8)),
            'role' => 'owner',
        ]);
        $channel = Channel::create(['code' => 'shopee', 'name' => 'Shopee']);
        $store = Store::create([
            'channel_id' => $channel->id,
            'code' => 'SHP-ITEM-FIRST',
            'name' => 'Shipment Test Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $item = Item::create([
            'code' => 'ITEM-FIRST-001',
            'name' => 'Item First Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);

        return [$user, $store, $item];
    }
}
