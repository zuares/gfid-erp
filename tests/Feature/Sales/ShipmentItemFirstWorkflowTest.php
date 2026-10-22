<?php

namespace Tests\Feature\Sales;

use App\Models\Channel;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\MarketplaceProduct;
use App\Models\MarketplaceProductModel;
use App\Models\OrderFulfillment;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\ShipmentOrderScan;
use App\Models\SkuMapping;
use App\Models\Store;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\MarketplaceSyncService;
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

    public function test_confirmation_uses_stock_position_on_shipment_date(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $warehouse = Warehouse::create([
            'code' => 'WH-RTS',
            'name' => 'Gudang RTS',
            'type' => 'finished_goods',
            'active' => true,
        ]);
        $shipmentDate = now()->subDay()->toDateString();

        $shipment = Shipment::create([
            'code' => 'SHP-STOCK-DATE-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => $shipmentDate,
            'status' => 'draft',
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 2,
            'allocated_qty' => 2,
        ]);

        InventoryMutation::create([
            'date' => $shipmentDate,
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'qty_change' => 1,
            'direction' => 'in',
            'source_type' => 'test',
        ]);
        InventoryMutation::create([
            'date' => now()->toDateString(),
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'qty_change' => 1,
            'direction' => 'in',
            'source_type' => 'inventory_adjustment_manual',
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $item->id,
            'qty' => 2,
            'allocated_qty' => 102,
        ]);

        $futureShipment = Shipment::create([
            'code' => 'SHP-STOCK-DATE-002',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->addDay()->toDateString(),
            'status' => 'draft',
        ]);
        ShipmentLine::create([
            'shipment_id' => $futureShipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 100,
            'allocated_qty' => 100,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertDontSee('Stok WH-RTS belum cukup untuk shipment ini');
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
            ->assertRedirect(route('sales.shipments.confirm_orders', $shipment));

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Shipment belum siap dikirim')
            ->assertSee('belum terhubung ke order')
            ->assertSee('Order & Item')
            ->assertSee('Item First Test')
            ->assertSee('Finance &amp; akuntansi', false)
            ->assertDontSee('data-order-marketplace-tab="anomaly"', false)
            ->assertDontSee('data-order-marketplace-tab="shopee"', false)
            ->assertDontSee('data-order-marketplace-tab="tiktok"', false)
            ->assertDontSee('data-sd-tab="rekon"', false)
            ->assertDontSee('sd-tab-rekon', false)
            ->assertDontSee('sd-tab-unlinked', false);
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
            ->assertOk()
            ->assertSee('data-copy-value="ORDER-PREVIOUS-A" data-copy-label="No. pesanan"', false);

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

    public function test_confirmation_does_not_call_shopee_for_an_unlinked_booking(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-CONFIRM-LOCAL-ONLY',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000099A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-LOCAL-ONLY',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
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

        $this->mock(MarketplaceSyncService::class)
            ->shouldNotReceive('promoteBookingToOrder');

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Booking ditemukan');

        $this->assertNull($scan->fresh()->fulfillment_id);
    }

    public function test_confirmation_finds_a_booking_with_legacy_whitespace_and_case(): void
    {
        [$user, $store] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-CONFIRM-LEGACY-BOOKING',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-LEGACY',
            'tracking_number' => ' spxid00000000098a ',
            'booking_status' => 'SHIPPED',
        ]);
        ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => 'SPXID00000000098A',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Booking ditemukan');
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

    public function test_order_first_rejects_item_scanned_as_order(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-ORDER-FIRST-GUARD-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $item->code,
            ])
            ->assertStatus(422)
            ->assertJsonPath('type', 'item_scan_guard')
            ->assertJsonPath('message', "Kode {$item->code} terdeteksi sebagai item. Scan nomor order, bukan barcode item.");

        $this->assertDatabaseMissing('shipment_order_scans', [
            'shipment_id' => $shipment->id,
            'order_no' => $item->code,
        ]);
    }

    public function test_duplicate_order_scan_is_marked_with_a_badge(): void
    {
        [$user, $store] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'AFF-DUPLICATE-ORDER-001',
            'shipment_type' => Shipment::TYPE_AFFILIATE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $orderNo = 'AFF-DUPLICATE-001';

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $orderNo,
            ])
            ->assertOk()
            ->assertJsonPath('duplicate', false)
            ->assertJsonPath('duplicate_count', 0);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $orderNo,
            ])
            ->assertOk()
            ->assertJsonPath('duplicate', true)
            ->assertJsonPath('duplicate_count', 1);

        $this->actingAs($user)
            ->get(route('sales.shipments.scan_order', $shipment))
            ->assertOk()
            ->assertSee('sr-duplicate-badge', false)
            ->assertSee('sr-missing-item-badge', false)
            ->assertSee('data-order-marketplace-tab="problem"', false)
            ->assertSee('Perlu Diperiksa')
            ->assertSee('Duplikat');

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('sd-duplicate-badge', false)
            ->assertSee('sd-missing-item-badge', false)
            ->assertSee('data-order-marketplace-tab="problem"', false)
            ->assertSee('data-order-link-tab="linked"', false)
            ->assertSee('data-order-link-tab="unlinked"', false)
            ->assertSee('Belum Tertaut')
            ->assertSee('Perlu Diperiksa')
            ->assertSee('Duplikat');
    }

    public function test_admin_can_edit_item_qty_on_order_first_scan_page(): void
    {
        [$owner, $store, $item] = $this->shipmentContext();
        $admin = User::factory()->create([
            'employee_code' => 'SIF-ADMIN-' . strtoupper(substr(uniqid(), -8)),
            'role' => 'admin',
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-ADMIN-QTY-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $line = ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($admin)
            ->get(route('sales.shipments.scan_order', $shipment))
            ->assertOk()
            ->assertSee('Klik qty item untuk mengubah jumlah.')
            ->assertSee('Toko / channel')
            ->assertSee('Finance &amp; akuntansi', false)
            ->assertDontSee('data-order-marketplace-tab="anomaly"', false);

        $this->actingAs($admin)
            ->patchJson(route('sales.shipments.update_line_qty', $line), ['qty' => 3])
            ->assertOk()
            ->assertJsonPath('qty', 3);

        $this->assertDatabaseHas('shipment_lines', [
            'id' => $line->id,
            'qty_scanned' => 3,
            'allocated_qty' => 3,
        ]);
    }

    public function test_owner_uses_compact_index_columns_with_extended_actions(): void
    {
        [$owner, $store] = $this->shipmentContext();
        Shipment::create([
            'code' => 'SHP-OWNER-INDEX-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($owner)
            ->get(route('sales.shipments.index'))
            ->assertOk()
            ->assertSee('Dibuat')
            ->assertSee('Pengiriman')
            ->assertSee('Detail shipment')
            ->assertSee('Batalkan shipment')
            ->assertDontSee('<th style="width: 170px;">Waktu</th>', false);
    }

    public function test_admin_create_defaults_to_marketplace_and_order_first(): void
    {
        [$owner] = $this->shipmentContext();
        $admin = User::factory()->create([
            'employee_code' => 'SIF-ADMIN-CREATE-' . strtoupper(substr(uniqid(), -8)),
            'role' => 'admin',
        ]);

        $this->actingAs($admin)
            ->get(route('sales.shipments.create'))
            ->assertOk()
            ->assertSee('name="shipment_type" id="shipmentTypeInput" value="marketplace"', false)
            ->assertSee('name="scan_mode" id="scanModeInput" value="order_first"', false)
            ->assertSee('data-shipment-type="marketplace"', false)
            ->assertSee('data-scan-mode="order_first"', false)
            ->assertDontSee('Dinonaktifkan sementara untuk Admin', false);
    }

    public function test_new_marketplace_shipment_uses_single_dispatch_until_waves_are_enabled(): void
    {
        [$user, $store] = $this->shipmentContext();

        $this->actingAs($user)
            ->post(route('sales.shipments.store'), [
                'shipment_type' => Shipment::TYPE_MARKETPLACE,
                'store_id' => $store->id,
                'date' => now()->toDateString(),
                'scan_mode' => 'item_first',
            ])
            ->assertRedirect();

        $shipment = Shipment::query()->latest('id')->firstOrFail();

        $this->assertSame(Shipment::DISPATCH_SINGLE, $shipment->dispatch_mode);
        $this->assertDatabaseCount('shipment_waves', 0);
    }

    public function test_single_marketplace_shipment_without_order_scan_is_not_blocked_on_confirmation(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-ITEM-ONLY-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'dispatch_mode' => Shipment::DISPATCH_SINGLE,
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
            ->assertSee('aria-disabled="false"', false)
            ->assertSee('Shipment tetap dapat dilanjutkan sebagai item-only.');

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertDontSee('Shipment belum siap dikirim')
            ->assertDontSee('Shipment marketplace wajib memiliki minimal satu nomor order.');
    }

    public function test_unknown_item_is_rejected_before_creating_daily_wave(): void
    {
        [$user, $store] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-UNKNOWN-ITEM-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'item_first',
            'dispatch_mode' => Shipment::DISPATCH_DAILY,
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_item', $shipment), [
                'scan_code' => 'ITEM-NOT-IN-MASTER-001',
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Item dengan kode/barcode ITEM-NOT-IN-MASTER-001 tidak ditemukan atau bukan finished_good.');

        $this->assertDatabaseCount('shipment_waves', 0);
    }

    public function test_owner_can_delete_order_and_its_scanned_items(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'AFF-DELETE-ORDER-001',
            'shipment_type' => Shipment::TYPE_AFFILIATE,
            'scan_mode' => 'order_first',
            'dispatch_mode' => Shipment::DISPATCH_SINGLE,
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $orderNo = 'AFF-DELETE-REF-001';
        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $orderNo,
            ])
            ->assertOk();

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_item', $shipment), [
                'scan_code' => $item->code,
                'order_no' => $orderNo,
            ])
            ->assertOk();

        $this->assertDatabaseHas('shipment_order_scans', [
            'shipment_id' => $shipment->id,
            'order_no' => $orderNo,
        ]);
        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.delete_order_scan', $shipment), [
                'order_no' => $orderNo,
            ])
            ->assertOk()
            ->assertJsonPath('status', 'ok');

        $this->assertDatabaseMissing('shipment_order_scans', [
            'shipment_id' => $shipment->id,
            'order_no' => $orderNo,
        ]);
        $this->assertDatabaseMissing('shipment_lines', [
            'shipment_id' => $shipment->id,
            'item_id' => $item->id,
        ]);
    }

    public function test_scanned_order_is_available_on_the_shipment_check_page(): void
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
            ->assertSee(route('sales.shipments.confirm_orders', $shipment), false)
            ->assertSee('Cek Shipment');

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => 'ORDER-TO-REKON-001',
            ])
            ->assertOk();

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee(route('sales.shipments.scan_order', $shipment), false)
            ->assertSee('Scan Order')
            ->assertSee('Sync Resi')
            ->assertSee('ORDER-TO-REKON-001')
            ->assertSee('ORDER-TO-REKON-001', false);
    }

    public function test_confirmation_marks_per_order_item_and_quantity_differences_for_review(): void
    {
        [$user, $store, $scannedItem] = $this->shipmentContext();
        $scannedItem->update(['code' => 'S3RDM']);
        $differentItem = Item::create([
            'code' => 'ITEM-FIRST-DIFFERENT',
            'name' => 'Different marketplace item',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $packSkuItem = Item::create([
            'code' => 'S3RDM-6',
            'name' => 'Shot Boxer Brief S3RDM-6',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-ORDER-DIFF-CHECK-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        foreach ([
            ['number' => 'ORDER-ITEM-DIFF-001', 'source_item' => $differentItem, 'source_qty' => 1, 'shipment_qty' => 1],
            ['number' => 'ORDER-QTY-DIFF-001', 'source_item' => $scannedItem, 'source_qty' => 3, 'shipment_qty' => 1],
            ['number' => 'ORDER-PACK-SKU-6', 'source_item' => $packSkuItem, 'source_qty' => 1, 'source_sku' => 'S3RDM-6', 'shipment_qty' => 6],
        ] as $case) {
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => $case['number'],
                'channel_order_id' => $case['number'],
                'order_status' => 'SHIPPED',
                'order_date' => now(),
                'ordered_at' => now(),
            ]);
            MarketplaceOrderItem::create([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'item_id' => $case['source_item']->id,
                'internal_item_id' => $case['source_item']->id,
                'item_name' => $case['source_item']->name,
                'item_sku' => $case['source_sku'] ?? $case['source_item']->code,
                'model_sku' => $case['source_sku'] ?? $case['source_item']->code,
                'qty' => $case['source_qty'],
            ]);
            $fulfillment = OrderFulfillment::create([
                'marketplace_order_id' => $order->id,
                'status' => OrderFulfillment::STATUS_DRAFT,
            ]);
            $scan = ShipmentOrderScan::create([
                'shipment_id' => $shipment->id,
                'order_no' => $case['number'],
                'fulfillment_id' => $fulfillment->id,
                'status' => 'pending',
                'source' => 'scanner',
                'raw_payload' => ['mode' => 'auto_link'],
            ]);
            ShipmentLine::create([
                'shipment_id' => $shipment->id,
                'shipment_order_scan_id' => $scan->id,
                'item_id' => $scannedItem->id,
                'qty_scanned' => $case['shipment_qty'],
                'allocated_qty' => $case['shipment_qty'],
            ]);
        }

        $response = $this->actingAs($user)->get(route('sales.shipments.confirm_orders', $shipment));
        $response->assertOk()
            ->assertSee('Selisih item')
            ->assertSee('Selisih qty')
            ->assertSee('data-order-problem="true"', false)
            ->assertSee('data-order-problem="false"', false)
            ->assertSee('S3RDM-6')
            ->assertSee('Shipment Test Store');

        $html = $response->getContent();
        $storePosition = strpos($html, '<span class="sd-store-name" title="Toko order">Shipment Test Store</span>');
        $linkedStatusPosition = strpos($html, '<span class="sd-badge linked">Tertaut</span>');
        $this->assertNotFalse($storePosition);
        $this->assertNotFalse($linkedStatusPosition);
        $this->assertLessThan($linkedStatusPosition, $storePosition);
    }

    public function test_kilat_booking_is_shown_as_linked_on_the_shipment_check_page(): void
    {
        [$user, $store] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-KILAT-LINK-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $bookingNo = 'KILAT-BOOKING-001';

        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => $bookingNo,
            'booking_status' => 'READY_TO_SHIP',
        ]);
        ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $bookingNo,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('sd-badge linked', false)
            ->assertSee('Tertaut');
    }

    public function test_spx_booking_appears_in_linked_tab_without_claiming_an_order_link(): void
    {
        [$user, $store] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-SPX-BOOKING-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000001A';
        $booking = MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-SPX-001',
            'order_sn' => 'ORDER-SPX-001',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
        ]);
        ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'marketplace',
            'raw_payload' => ['linked_source' => 'marketplace_order'],
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Booking ditemukan')
            ->assertSee('>BOOKING-SPX-001</span>', false)
            ->assertSee('>ORDER-SPX-001</span>', false)
            ->assertSee('data-copy-value="BOOKING-SPX-001" data-copy-label="No. booking"', false)
            ->assertSee('class="sd-copy-value sd-copy-scan" data-copy-value="' . $awb . '" data-copy-label="No. resi"', false)
            ->assertSee('<span>' . $awb . '</span><span class="sd-copy-action" data-copy-action>', false)
            ->assertSee('data-copy-value="ORDER-SPX-001" data-copy-label="No. pesanan"', false)
            ->assertSee('data-copy-feedback hidden', false)
            ->assertSee('SKU booking belum tersedia.')
            ->assertSee('data-order-link-state="linked"', false)
            ->assertSee('sd-badge pending', false)
            ->assertSee('<span class="sd-booking-status" title="Status booking Shopee">SHIPPED</span>', false)
            ->assertDontSee('sd-badge linked', false);

        $booking->update(['booking_status' => 'COMPLETED']);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('<span class="sd-booking-status" title="Status booking Shopee">COMPLETED</span>', false);
    }

    public function test_spx_booking_shows_its_real_marketplace_sku_next_to_one_scanned_item(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-SPX-BOOKING-SKU-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000004A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-SPX-SKU-001',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
            'items' => [['model_sku' => 'K5BLK-1', 'item_sku' => 'K5BLK', 'quantity' => 2]],
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 2,
            'allocated_qty' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Booking ditemukan')
            ->assertSee('<span>No</span><span>SKU Marketplace</span><span>Order</span><span>Ship</span><span>Diff</span><span>Scan Item Gudang</span><span>COGS</span><span>Subtotal COGS</span>', false)
            ->assertSee('>Periksa</div>', false)
            ->assertSee('class="sd-marketplace-sku-value">K5BLK-1</span>', false)
            ->assertDontSee('SKU booking belum tersedia.');

        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-SPX-BOOKING-SKU-001',
            'channel_order_id' => 'ORDER-SPX-BOOKING-SKU-001',
            'shipping_awb_no' => $awb,
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
            'model_sku' => 'K5BLK-1',
            'qty' => 2,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('sd-badge linked', false)
            ->assertDontSee('Booking ditemukan')
            ->assertSee('sd-marketplace-detail', false);
    }

    public function test_spx_booking_verifies_a_mapped_item_and_shows_quantity_difference(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $item->update(['code' => 'K7BLK', 'name' => 'Jogger Pendek Basic K7 Hitam']);
        SkuMapping::create([
            'marketplace_sku' => 'K7BLK-1',
            'channel_code' => 'shopee',
            'item_id' => $item->id,
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-BOOKING-VERIFY-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000041A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-VERIFY-001',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
            'items' => [['model_sku' => 'K7BLK-1', 'model_name' => 'Hitam / Ukuran 1', 'quantity' => 1]],
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Booking ditemukan')
            ->assertSee('sd-order-item-diff sd-marketplace-ok', false)
            ->assertSee('>0</div>', false)
            ->assertSee('K7BLK-1')
            ->assertSee('Hitam / Ukuran 1')
            ->assertDontSee('>Periksa</div>', false);

        ShipmentLine::where('shipment_order_scan_id', $scan->id)->update(['qty_scanned' => 2]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('sd-order-item-diff sd-marketplace-short', false)
            ->assertSee('>+1</div>', false)
            ->assertDontSee('>Periksa</div>', false);
    }

    public function test_spx_booking_keeps_item_review_when_sku_maps_to_a_different_item(): void
    {
        [$user, $store, $scannedItem] = $this->shipmentContext();
        $otherItem = Item::create([
            'code' => 'K7BLK',
            'name' => 'Jogger Pendek Basic K7 Hitam',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        SkuMapping::create([
            'marketplace_sku' => 'K7BLK-1',
            'channel_code' => 'shopee',
            'item_id' => $otherItem->id,
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-BOOKING-VERIFY-002',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000042A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-VERIFY-002',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
            'items' => [['model_sku' => 'K7BLK-1', 'quantity' => 1]],
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $scannedItem->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('>Periksa</div>', false)
            ->assertDontSee('sd-order-item-diff sd-marketplace-ok', false);
    }

    public function test_spx_booking_verifies_multiple_mapped_items_individually(): void
    {
        [$user, $store, $firstItem] = $this->shipmentContext();
        $firstItem->update(['code' => 'K7BLK']);
        $secondItem = Item::create([
            'code' => 'K5BLK',
            'name' => 'Jogger Pendek Basic K5 Hitam',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        SkuMapping::create(['marketplace_sku' => 'K7BLK-1', 'channel_code' => 'shopee', 'item_id' => $firstItem->id]);
        SkuMapping::create(['marketplace_sku' => 'K5BLK-1', 'channel_code' => null, 'item_id' => $secondItem->id]);
        $shipment = Shipment::create([
            'code' => 'SHP-BOOKING-VERIFY-MULTI',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000043A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-VERIFY-MULTI',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
            'items' => [
                ['model_sku' => 'K7BLK-1', 'quantity' => 1],
                ['model_sku' => 'K5BLK-1', 'quantity' => 2],
            ],
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        foreach ([[$firstItem, 1], [$secondItem, 2]] as [$item, $qty]) {
            ShipmentLine::create([
                'shipment_id' => $shipment->id,
                'shipment_order_scan_id' => $scan->id,
                'item_id' => $item->id,
                'qty_scanned' => $qty,
                'allocated_qty' => $qty,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('class="sd-marketplace-sku-value">K7BLK-1</span>', false)
            ->assertSee('class="sd-marketplace-sku-value">K5BLK-1</span>', false)
            ->assertDontSee('>Periksa</div>', false);

        $this->assertSame(2, substr_count($response->getContent(), 'sd-order-item-diff sd-marketplace-ok'));
    }

    public function test_spx_booking_uses_same_store_product_model_sku_when_booking_sku_is_empty(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-SPX-BOOKING-SKU-002',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $product = MarketplaceProduct::create([
            'store_id' => $store->id,
            'item_id' => 'SHOPEE-ITEM-101',
            'item_sku' => 'BASE-SKU',
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $product->id,
            'model_id' => 'MODEL-22',
            'model_sku' => 'MODEL-SKU-22',
        ]);
        $otherStore = Store::create([
            'channel_id' => $store->channel_id,
            'code' => 'SHP-OTHER-STORE',
            'name' => 'Other Shopee Store',
            'status' => 'active',
            'is_active' => true,
        ]);
        $otherProduct = MarketplaceProduct::create([
            'store_id' => $otherStore->id,
            'item_id' => 'SHOPEE-ITEM-101',
            'item_sku' => 'WRONG-STORE-SKU',
        ]);
        MarketplaceProductModel::create([
            'marketplace_product_id' => $otherProduct->id,
            'model_id' => 'MODEL-22',
            'model_sku' => 'WRONG-STORE-MODEL',
        ]);
        $awb = 'SPXID00000000005A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-SPX-SKU-002',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
            'items' => [['item_id' => 'SHOPEE-ITEM-101', 'model_id' => 'MODEL-22', 'item_sku' => 'BASE-SKU']],
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('class="sd-marketplace-sku-value">MODEL-SKU-22</span>', false)
            ->assertDontSee('class="sd-marketplace-sku-value">BASE-SKU</span>', false)
            ->assertDontSee('WRONG-STORE-MODEL');
    }

    public function test_spx_booking_with_multiple_items_lists_skus_without_guessing_scanned_item_mapping(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-SPX-BOOKING-SKU-003',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000006A';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => 'BOOKING-SPX-SKU-003',
            'tracking_number' => $awb,
            'booking_status' => 'SHIPPED',
            'items' => [
                ['model_sku' => 'MARKETPLACE-A'],
                ['model_sku' => 'MARKETPLACE-B'],
            ],
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('SKU booking: MARKETPLACE-A, MARKETPLACE-B')
            ->assertDontSee('>MARKETPLACE-A</div>', false);
    }

    public function test_spx_confirmation_shows_order_sku_and_flags_a_different_scanned_item(): void
    {
        [$user, $store, $scannedItem] = $this->shipmentContext();
        $marketplaceItem = Item::create([
            'code' => 'TTD-BLK-M',
            'name' => 'Tracktop Marketplace M',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-SPX-SKU-MISMATCH-001',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000002A';
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-SPX-SKU-MISMATCH',
            'channel_order_id' => 'ORDER-SPX-SKU-MISMATCH',
            'booking_sn' => 'BOOKING-SPX-SKU-MISMATCH',
            'shipping_awb_no' => $awb,
            'order_status' => 'SHIPPED',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'item_id' => $marketplaceItem->id,
            'internal_item_id' => $marketplaceItem->id,
            'item_name' => $marketplaceItem->name,
            'item_sku' => 'TTD-BLK',
            'model_sku' => null,
            'marketplace_sku' => 'TTD-BLK-M',
            'variant_name' => 'Hitam / Ukuran M',
            'qty' => 1,
        ]);
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scan->id,
            'item_id' => $scannedItem->id,
            'qty_scanned' => 1,
            'allocated_qty' => 1,
        ]);

        $response = $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('<span>No</span><span>SKU Marketplace</span><span>Order</span><span>Ship</span><span>Diff</span><span>Scan Item Gudang</span>', false)
            ->assertSee('TTD-BLK-M')
            ->assertSee('Hitam / Ukuran M')
            ->assertSee('>BOOKING-SPX-SKU-MISMATCH</span>', false)
            ->assertSee('>ORDER-SPX-SKU-MISMATCH</span>', false)
            ->assertSee('Item berbeda')
            ->assertSee('sd-order-item-diff sd-marketplace-short', false)
            ->assertSee('sd-badge linked', false);

        $this->assertMatchesRegularExpression(
            '/<div class="sd-order-item-diff[^\"]*">\s*Periksa\s*<span class="sd-sku-mismatch">Item berbeda<\/span>\s*<\/div>/',
            $response->getContent(),
        );
    }

    public function test_spx_confirmation_lists_unmatched_order_skus_without_pairing_multiple_items(): void
    {
        [$user, $store, $scannedItem] = $this->shipmentContext();
        $secondScannedItem = Item::create([
            'code' => 'SCAN-SECOND', 'name' => 'Second scanned item', 'unit' => 'pcs',
            'type' => 'finished_good', 'active' => true,
        ]);
        $shipment = Shipment::create([
            'code' => 'SHP-SPX-SKU-MISMATCH-002',
            'shipment_type' => Shipment::TYPE_MARKETPLACE,
            'scan_mode' => 'order_first',
            'store_id' => $store->id,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);
        $awb = 'SPXID00000000003A';
        $order = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-SPX-MULTI-MISMATCH',
            'channel_order_id' => 'ORDER-SPX-MULTI-MISMATCH',
            'shipping_awb_no' => $awb,
            'order_status' => 'SHIPPED',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        foreach ([
            ['sku' => 'MARKETPLACE-SKU-A', 'title' => 'Kaos Olahraga Premium Hitam', 'variant' => 'Varian Hitam M'],
            ['sku' => 'MARKETPLACE-SKU-B', 'title' => 'Celana Jogger Training Navy', 'variant' => 'Varian Navy L'],
        ] as $marketplaceLine) {
            $sku = $marketplaceLine['sku'];
            $marketplaceItem = Item::create([
                'code' => $sku, 'name' => $sku, 'unit' => 'pcs',
                'type' => 'finished_good', 'active' => true,
            ]);
            MarketplaceOrderItem::create([
                'order_id' => $order->id,
                'marketplace_order_id' => $order->id,
                'item_id' => $marketplaceItem->id,
                'internal_item_id' => $marketplaceItem->id,
                'item_name' => $marketplaceLine['title'],
                'item_name_snapshot' => $marketplaceLine['title'],
                'model_sku' => $sku,
                'variant_name' => $marketplaceLine['variant'],
                'qty' => 1,
            ]);
        }
        $scan = ShipmentOrderScan::create([
            'shipment_id' => $shipment->id,
            'order_no' => $awb,
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);
        foreach ([$scannedItem, $secondScannedItem] as $item) {
            ShipmentLine::create([
                'shipment_id' => $shipment->id,
                'shipment_order_scan_id' => $scan->id,
                'item_id' => $item->id,
                'qty_scanned' => 1,
                'allocated_qty' => 1,
            ]);
        }

        $response = $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('Item order belum cocok: MARKETPLACE-SKU-A, MARKETPLACE-SKU-B')
            ->assertSee('SKU order belum dipasangkan')
            ->assertSee('class="sd-marketplace-sku-value" title="SKU marketplace: MARKETPLACE-SKU-A">MARKETPLACE-SKU-A</span>', false)
            ->assertSee('class="sd-marketplace-product-name" title="Kaos Olahraga Premium Hitam">Kaos Olahraga Premium Hitam</span>', false)
            ->assertSee('Varian Hitam M')
            ->assertSee('class="sd-marketplace-sku-value" title="SKU marketplace: MARKETPLACE-SKU-B">MARKETPLACE-SKU-B</span>', false)
            ->assertSee('class="sd-marketplace-product-name" title="Celana Jogger Training Navy">Celana Jogger Training Navy</span>', false)
            ->assertSee('Varian Navy L')
            ->assertSee('Periksa');

        $html = $response->getContent();
        preg_match_all('/<div class="sd-order-item [^\"]*sd-qty-breakdown">/', $html, $rows);
        $this->assertCount(4, $rows[0], 'Two unmatched marketplace item rows should be shown alongside the two scanned rows.');
        $this->assertStringNotContainsString('<details class="sd-item-review">', $html);
        $this->assertSame(2, preg_match_all('/class="sd-order-item-diff[^\"]*">\s*Periksa/', $html));
        $this->assertDoesNotMatchRegularExpression('/<div class="sd-order-item-code">[^<]*<\/div>\s*<div class="sd-order-item-name">/', $html);
    }

    public function test_ready_to_ship_and_processed_kilat_orders_are_linked_when_scanned(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-STATUS-LINK-001',
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

        $readyOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-READY-001',
            'channel_order_id' => 'ORDER-READY-001',
            'order_status' => 'READY_TO_SHIP',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $readyOrder->id,
            'marketplace_order_id' => $readyOrder->id,
            'item_id' => $item->id,
            'internal_item_id' => $item->id,
            'item_name' => $item->name,
            'item_sku' => $item->code,
            'model_sku' => $item->code,
            'qty' => 1,
        ]);

        $kilatBookingNo = 'KILAT-PROCESSED-001';
        MarketplaceBooking::create([
            'store_id' => $store->id,
            'booking_sn' => $kilatBookingNo,
            'order_sn' => 'ORDER-KILAT-001',
            'booking_status' => 'PROCESSED',
        ]);
        $kilatOrder = MarketplaceOrder::create([
            'store_id' => $store->id,
            'external_order_id' => 'ORDER-KILAT-001',
            'channel_order_id' => 'ORDER-KILAT-001',
            'booking_sn' => $kilatBookingNo,
            'order_status' => null,
            'status' => 'PROCESS',
            'order_date' => now(),
            'ordered_at' => now(),
        ]);
        MarketplaceOrderItem::create([
            'order_id' => $kilatOrder->id,
            'marketplace_order_id' => $kilatOrder->id,
            'item_id' => $item->id,
            'internal_item_id' => $item->id,
            'item_name' => $item->name,
            'item_sku' => $item->code,
            'model_sku' => $item->code,
            'qty' => 1,
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => 'ORDER-READY-001',
            ])
            ->assertOk()
            ->assertJsonPath('order.label', 'Tertaut otomatis');

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $kilatBookingNo,
            ])
            ->assertOk()
            ->assertJsonPath('order.label', 'Tertaut otomatis');
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

    public function test_sync_resi_only_checks_recent_completed_orders(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-AUTO-LINK-RECENT-001',
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

        foreach ([
            ['code' => 'RECENT', 'date' => now()->subDay()->setTime(10, 0)],
            ['code' => 'OLD', 'date' => now()->subDays(3)],
        ] as $case) {
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => 'ORDER-COMPLETED-' . $case['code'],
                'channel_order_id' => 'ORDER-COMPLETED-' . $case['code'],
                'shipping_awb_no' => 'SPX-COMPLETED-' . $case['code'],
                'order_status' => 'COMPLETED',
                'order_date' => $case['date'],
                'ordered_at' => $case['date'],
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
                'order_no' => 'SPX-COMPLETED-' . $case['code'],
                'status' => 'pending',
                'source' => 'scanner',
                'raw_payload' => ['mode' => 'record_only'],
            ]);
        }

        $response = $this->actingAs($user)
            ->postJson(route('sales.shipments.rekon_auto_link', $shipment), [
                'completed_window' => 'recent',
            ])
            ->assertOk();

        $response->assertJsonPath('result.linked', 1)
            ->assertJsonPath('result.unmatched', 1);
        $this->assertDatabaseCount('order_fulfillments', 1);
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

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk()
            ->assertSee('sd-badge linked', false)
            ->assertSee('sd-qty-breakdown', false)
            ->assertSee('<span>Order</span>', false)
            ->assertSee('<span>Ship</span>', false)
            ->assertSee('<span>Diff</span>', false)
            ->assertSee('Tertaut');
    }

    public function test_completed_shopee_statuses_are_eligible_for_awb_reconciliation(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-AUTO-LINK-STATUS-001',
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

        foreach (['TO_CONFIRM_RECEIVE', 'COMPLETED'] as $index => $status) {
            $awb = "SPX-STATUS-{$index}";
            $order = MarketplaceOrder::create([
                'store_id' => $store->id,
                'external_order_id' => "ORDER-STATUS-{$index}",
                'channel_order_id' => "ORDER-STATUS-{$index}",
                'shipping_awb_no' => $awb,
                'order_status' => $status,
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
                    'order_no' => $awb,
                ])
                ->assertOk()
                ->assertJsonPath('order.label', 'Tertaut otomatis');
        }
    }

    public function test_confirm_loading_skips_completed_orders_until_explicit_match(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'SHP-AUTO-LINK-STATUS-002',
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
            'external_order_id' => 'ORDER-COMPLETED-CONFIRM',
            'channel_order_id' => 'ORDER-COMPLETED-CONFIRM',
            'shipping_awb_no' => 'SPX-COMPLETED-CONFIRM',
            'order_status' => 'COMPLETED',
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
            'order_no' => 'SPX-COMPLETED-CONFIRM',
            'status' => 'pending',
            'source' => 'scanner',
            'raw_payload' => ['mode' => 'record_only'],
        ]);

        $this->actingAs($user)
            ->get(route('sales.shipments.confirm_orders', $shipment))
            ->assertOk();

        $this->assertDatabaseMissing('order_fulfillments', [
            'marketplace_order_id' => $order->id,
        ]);

        $this->actingAs($user)
            ->postJson(route('sales.shipments.rekon_match', $shipment), [
                'order_no' => 'SPX-COMPLETED-CONFIRM',
                'pool_used' => '{}',
            ])
            ->assertOk()
            ->assertJsonPath('order.order_no', 'ORDER-COMPLETED-CONFIRM');

        $this->assertDatabaseHas('order_fulfillments', [
            'marketplace_order_id' => $order->id,
        ]);
    }

    public function test_affiliate_shipment_defaults_to_order_first(): void
    {
        [$user, $store] = $this->shipmentContext();

        $response = $this->actingAs($user)
            ->postJson(route('sales.shipments.store'), [
                'shipment_type' => Shipment::TYPE_AFFILIATE,
                'date' => now()->toDateString(),
            ]);

        $response->assertOk()
            ->assertJsonPath('shipment.scan_mode', 'order_first');
        $this->assertStringStartsWith('AFF-', (string) $response->json('shipment.code'));

        $this->assertDatabaseHas('shipments', [
            'shipment_type' => Shipment::TYPE_AFFILIATE,
            'scan_mode' => 'order_first',
            'store_id' => null,
        ]);
    }

    public function test_affiliate_scans_order_before_item_without_marketplace_linking(): void
    {
        [$user, $store, $item] = $this->shipmentContext();
        $shipment = Shipment::create([
            'code' => 'AFF-ORDER-FIRST-001',
            'shipment_type' => Shipment::TYPE_AFFILIATE,
            'scan_mode' => 'order_first',
            'dispatch_mode' => Shipment::DISPATCH_SINGLE,
            'date' => now()->toDateString(),
            'status' => 'draft',
        ]);

        $orderNo = 'AFF-REF-001';
        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_order_store', $shipment), [
                'order_no' => $orderNo,
            ])
            ->assertOk()
            ->assertJsonPath('order.code', $orderNo)
            ->assertJsonPath('order.linked_source', null);

        $scanId = ShipmentOrderScan::where('shipment_id', $shipment->id)
            ->where('order_no', $orderNo)
            ->value('id');

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_item', $shipment), [
                'scan_code' => $item->code,
            ])
            ->assertStatus(422)
            ->assertJsonPath('message', 'Scan order afiliasi terlebih dahulu sebelum scan item.');

        $this->actingAs($user)
            ->postJson(route('sales.shipments.scan_item', $shipment), [
                'scan_code' => $item->code,
                'order_no' => $orderNo,
            ])
            ->assertOk();

        $this->assertDatabaseHas('shipment_lines', [
            'shipment_id' => $shipment->id,
            'shipment_order_scan_id' => $scanId,
            'item_id' => $item->id,
            'qty_scanned' => 1,
        ]);
        $this->assertDatabaseHas('shipment_order_scans', [
            'id' => $scanId,
            'fulfillment_id' => null,
        ]);
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
