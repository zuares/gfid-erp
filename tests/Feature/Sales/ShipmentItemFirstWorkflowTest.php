<?php

namespace Tests\Feature\Sales;

use App\Models\Channel;
use App\Models\Item;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\OrderFulfillment;
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
            ->assertSee('belum terhubung ke order')
            ->assertSee('Rekonsiliasi dari scan sebelumnya')
            ->assertSee('Tidak perlu scan ulang.')
            ->assertSee('Belum Tertaut')
            ->assertSee('sd-tab-unlinked');
    }

    public function test_confirmation_reconciles_order_numbers_scanned_in_previous_step(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-CONFIRM-REKON-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 2,
            'allocated_qty' => 2,
        ]);

        foreach (['ORDER-PREVIOUS-A', 'ORDER-PREVIOUS-B'] as $orderNo) {
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => $orderNo,
                'channel_order_id' => $orderNo,
                'order_status' => 'SHIPPED',
                'order_date' => now(),
                'ordered_at' => now(),
            ]);
            MarketplaceOrderItem::create([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'item_id' => $item->id,
                'internal_item_id' => $item->id,
                'item_name' => $item->name,
                'item_sku' => $item->code,
                'model_sku' => $item->code,
                'qty' => 1,
            ]);
            ShipmentOrderScan::create([
                'shipment_id' => $shipment->id,
                'order_no' => $orderNo,
                'status' => 'pending',
                'source' => 'scanner',
                'raw_payload' => ['mode' => 'record_only'],
            ]);
        }

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk();

        $scans = ShipmentOrderScan::where('shipment_id', $shipment->id)->orderBy('id')->get();
        $this->assertCount(2, $scans);
        $this->assertTrue($scans->every(fn ($scan) => $scan->fulfillment_id !== null));
        $this->assertSame(2, ShipmentLine::where('shipment_id', $shipment->id)->sum('qty_scanned'));
        $this->assertSame(1, ShipmentLine::where('shipment_id', $shipment->id)->where('shipment_order_scan_id', $scans[0]->id)->sum('qty_scanned'));
        $this->assertSame(1, ShipmentLine::where('shipment_id', $shipment->id)->where('shipment_order_scan_id', $scans[1]->id)->sum('qty_scanned'));
    }

    public function test_confirmation_retries_a_saved_scan_with_empty_allocations(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-CONFIRM-REKON-002',
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
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-EMPTY-ALLOCATION',
            'channel_order_id' => 'ORDER-EMPTY-ALLOCATION',
            'order_status' => 'SHIPPED',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'item_id' => $item->id,
            'internal_item_id' => $item->id,
            'item_name' => $item->name,
            'item_sku' => $item->code,
            'model_sku' => $item->code,
            'qty' => 1,
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'ORDER-EMPTY-ALLOCATION',
            'status' => 'pending',
            'source' => 'marketplace',
            'raw_payload' => [
                'mode' => 'auto_link',
                'allocations' => [],
            ],
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk();

        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
        ]);
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

    public function test_scanned_order_is_available_on_the_reconciliation_page(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-SCAN-REKON-001',
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

        $this->actingAs($user)
            ->get(route('sales.shipments.scan_order', $shipment))
            ->assertOk()
            ->assertSee(route('sales.shipments.rekon', $shipment), false)
            ->assertSee('Rekonsiliasi');

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => 'ORDER-TO-REKON-001',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get(route('sales.shipments.rekon', $shipment))
            ->assertOk()
            ->assertSee('ORDER-TO-REKON-001')
            ->assertSee('ORDER-TO-REKON-001', false);
    }

    public function test_auto_link_reconciles_an_existing_awb_and_persists_the_match(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-AUTO-LINK-001',
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

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-AWB-001',
            'channel_order_id' => 'ORDER-AWB-001',
            'shipping_awb_no' => 'SPXID062189160888',
            'order_status' => 'SHIPPED',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'item_id' => $item->id,
            'internal_item_id' => $item->id,
            'item_name' => $item->name,
            'item_sku' => $item->code,
            'model_sku' => $item->code,
            'qty' => 1,
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'SPXID062189160888',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.rekon_auto_link', $shipment))
            ->assertOk()
            ->assertJsonPath('result.linked', 1)
            ->assertJsonPath('result.unmatched', 0);

        $this->assertDatabaseHas('shipment_order_scans', [
            'id' => $scan->id,
            'fulfillment_id' => OrderFulfillment::where('marketplace_order_id', $order->id)->value('id'),
            'match_method' => 'awb',
            'match_reason' => null,
        ]);
        $this->assertDatabaseHas('order_fulfillments', [
            'marketplace_order_id' => $order->id,
        ]);
    }

    public function test_auto_link_marks_an_unknown_scan_with_a_reason(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-AUTO-LINK-002',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'UNKNOWN-AWB-001',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.rekon_auto_link', $shipment))
            ->assertOk()
            ->assertJsonPath('result.linked', 0)
            ->assertJsonPath('result.unmatched', 1);

        $this->assertDatabaseHas('shipment_order_scans', [
            'id' => $scan->id,
            'fulfillment_id' => null,
            'match_reason' => 'order_not_found',
        ]);
    }

    public function test_new_awb_scan_is_linked_immediately_without_deducting_stock(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-AUTO-LINK-003',
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
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-AWB-002',
            'channel_order_id' => 'ORDER-AWB-002',
            'shipping_awb_no' => 'SPXID062189160889',
            'order_status' => 'SHIPPED',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'item_id' => $item->id,
            'internal_item_id' => $item->id,
            'item_name' => $item->name,
            'item_sku' => $item->code,
            'model_sku' => $item->code,
            'qty' => 1,
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => 'SPXID062189160889',
            ])
            ->assertOk()
            ->assertJsonPath('order.label', 'Tertaut otomatis');

        $matchResponse = $this->actingAs($user)
            ->postJson(route('sales.shipments.rekon_match', $shipment), [
                'order_no' => 'SPXID062189160889',
                'pool_used' => '{}',
            ])
            ->assertOk();

        $fulfillment = OrderFulfillment::where('marketplace_order_id', $order->id)->firstOrFail();
        $matchResponse->assertJsonPath('order.lines.0.qty_alloc', 1);
        $this->assertDatabaseHas('shipment_order_scans', [
            'shipment_id' => $shipment->id,
            'order_no' => 'SPXID062189160889',
            'fulfillment_id' => $fulfillment->id,
            'match_method' => 'awb',
        ]);
        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => ShipmentOrderScan::where('shipment_id', $shipment->id)
                ->where('order_no', 'SPXID062189160889')
                ->value('id'),
            'item_id' => $item->id,
            'qty_scanned' => 1,
        ]);
        $this->assertSame(OrderFulfillment::STATUS_PENDING_REVIEW, $fulfillment->status);
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
