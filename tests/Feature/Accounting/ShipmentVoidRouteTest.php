<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Journal;
use App\Models\Shipment;
use App\Models\ShipmentLine;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ShipmentVoidRouteTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        config(['accounting.standalone_dual_posting_production_shipment_enabled' => true]);
        $this->ensureProductionMapping('5101', '5-110');
        $this->ensureProductionMapping('1203', '1-152');
    }

    public function test_cancel_form_and_void_require_owner(): void
    {
        $shipment = $this->postedShipmentWithAccounting();
        $admin = User::factory()->create(['role' => 'admin', 'employee_code' => 'ADM-VOID-1']);

        $this->actingAs($admin)
            ->get(route('sales.shipments.cancel_form', $shipment))
            ->assertForbidden();

        $this->actingAs($admin)
            ->post(route('sales.shipments.cancel', $shipment), ['cancel_reason' => 'Tidak berwenang'])
            ->assertForbidden();

        $this->assertNull($shipment->fresh()->cancelled_at);
        $this->assertSame(0, InventoryMutation::query()
            ->where('source_type', 'shipment_cancel')
            ->where('source_id', $shipment->id)
            ->count());
    }

    public function test_developer_user_can_access_shipment_void_form(): void
    {
        $shipment = $this->postedShipmentWithAccounting();
        $developer = User::factory()->create([
            'role' => 'admin',
            'is_developer' => true,
            'employee_code' => 'DEV-VOID-1',
        ]);

        $this->actingAs($developer)
            ->get(route('sales.shipments.cancel_form', $shipment))
            ->assertOk();
    }

    public function test_owner_submit_route_posts_legacy_and_standalone_journals(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWN-SUBMIT-1']);
        $shipment = $this->draftShipmentReadyForSubmit();

        $response = $this->actingAs($owner)
            ->post(route('sales.shipments.submit', $shipment));

        $response->assertRedirect(route('sales.shipments.index'));
        $response->assertSessionHas('status', 'success');

        $this->assertSame('posted', $shipment->fresh()->status);
        $this->assertSame(0.0, (float) InventoryStock::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('item_id', $this->item->id)
            ->value('qty'));
        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', 'shipment')
            ->where('source_id', $shipment->id)
            ->count());
        $this->assertSame(1, Journal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
            ->where('source_id', $shipment->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(1, StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
            ->where('source_id', $shipment->id)
            ->whereNull('voided_at')
            ->count());
    }

    public function test_owner_void_reverses_inventory_both_ledgers_and_is_idempotent(): void
    {
        $owner = User::factory()->create(['role' => 'owner', 'employee_code' => 'OWN-VOID-1']);
        $shipment = $this->postedShipmentWithAccounting($owner);

        $response = $this->actingAs($owner)
            ->post(route('sales.shipments.cancel', $shipment), [
                'cancel_reason' => 'Void pilot shipment',
            ]);

        $response->assertRedirect(route('sales.shipments.show', $shipment));
        $response->assertSessionHas('status', 'success');

        $this->assertSame(1.0, (float) InventoryStock::query()
            ->where('warehouse_id', $this->warehouse->id)
            ->where('item_id', $this->item->id)
            ->value('qty'));

        $this->assertDatabaseHas('inventory_mutations', [
            'source_type' => 'shipment_cancel',
            'source_id' => $shipment->id,
            'item_id' => $this->item->id,
            'qty_change' => 1,
            'total_cost' => 50000,
        ]);
        $this->assertSame(0.0, (float) InventoryMutation::query()
            ->whereIn('source_type', ['shipment', 'shipment_cancel'])
            ->where('source_id', $shipment->id)
            ->sum('qty_change'));
        $this->assertSame(0.0, (float) InventoryMutation::query()
            ->whereIn('source_type', ['shipment', 'shipment_cancel'])
            ->where('source_id', $shipment->id)
            ->sum('total_cost'));

        $this->assertSame(0, Journal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
            ->where('source_id', $shipment->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, Journal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
            ->where('source_id', $shipment->id)
            ->count());
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $shipment->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', $shipment->id)
            ->count());

        $this->actingAs($owner)
            ->post(route('sales.shipments.cancel', $shipment), [
                'cancel_reason' => 'Void kedua',
            ])
            ->assertRedirect(route('sales.shipments.show', $shipment));

        $this->assertSame(1, InventoryMutation::query()
            ->where('source_type', 'shipment_cancel')
            ->where('source_id', $shipment->id)
            ->count());
        $this->assertSame(2, Journal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
            ->where('source_id', $shipment->id)
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', $shipment->id)
            ->count());
        $this->assertNotNull($shipment->fresh()->cancelled_at);
    }

    private Warehouse $warehouse;

    private Item $item;

    private function postedShipmentWithAccounting(?User $poster = null): Shipment
    {
        $this->warehouse = Warehouse::create([
            'code' => 'WH-RTS',
            'name' => 'Gudang RTS',
            'type' => 'ready_to_sell',
            'active' => true,
        ]);
        $this->item = Item::create([
            'code' => 'PILOT-FG-VOID',
            'name' => 'Pilot FG Void',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'hpp' => 50000,
            'last_purchase_price' => 50000,
            'active' => true,
        ]);
        $this->legacyAccount('5101', 'Test HPP');
        $this->legacyAccount('1203', 'Test FG');
        InventoryStock::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'qty' => 0,
            'allocated_qty' => 0,
        ]);

        $shipment = Shipment::create([
            'code' => 'SHP-VOID-ROUTE-'.uniqid(),
            'shipment_type' => Shipment::TYPE_MANUAL,
            'dispatch_mode' => Shipment::DISPATCH_SINGLE,
            'date' => '2026-10-27',
            'status' => 'posted',
            'total_qty' => 1,
            'posted_at' => now(),
            'posted_by' => $poster?->id,
        ]);
        $line = ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $this->item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 0,
        ]);

        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'qty_change' => -1,
            'direction' => 'out',
            'source_type' => 'shipment',
            'source_id' => $shipment->id,
            'source_line_id' => $line->id,
            'unit_cost' => 50000,
            'total_cost' => -50000,
            'notes' => 'Pilot shipment OUT',
        ]);

        $journal = Journal::create([
            'date' => '2026-10-27',
            'description' => 'COGS Shipment '.$shipment->code,
            'source_type' => StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS,
            'source_id' => $shipment->id,
            'posted_at' => now(),
        ]);
        $journal->lines()->createMany([
            ['account_id' => $this->legacyAccount('5101', 'Test HPP')->id, 'debit' => 50000, 'credit' => 0],
            ['account_id' => $this->legacyAccount('1203', 'Test FG')->id, 'debit' => 0, 'credit' => 50000],
        ]);

        app(StandaloneProductionPostingService::class)->mirrorShipment($shipment->id);

        return $shipment;
    }

    private function draftShipmentReadyForSubmit(): Shipment
    {
        $this->warehouse = Warehouse::create([
            'code' => 'WH-RTS',
            'name' => 'Gudang RTS',
            'type' => 'ready_to_sell',
            'active' => true,
        ]);
        $this->item = Item::create([
            'code' => 'PILOT-FG-SUBMIT',
            'name' => 'Pilot FG Submit',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'hpp' => 50000,
            'last_purchase_price' => 50000,
            'active' => true,
        ]);
        $this->legacyAccount('5101', 'Test HPP');
        $this->legacyAccount('1203', 'Test FG');
        InventoryStock::create([
            'warehouse_id' => $this->warehouse->id,
            'item_id' => $this->item->id,
            'qty' => 0,
            'allocated_qty' => 0,
        ]);

        $shipment = Shipment::create([
            'code' => 'SHP-SUBMIT-ROUTE-'.uniqid(),
            'shipment_type' => Shipment::TYPE_MANUAL,
            'dispatch_mode' => Shipment::DISPATCH_SINGLE,
            'date' => '2026-10-27',
            'status' => 'draft',
            'total_qty' => 1,
        ]);
        $line = ShipmentLine::create([
            'shipment_id' => $shipment->id,
            'item_id' => $this->item->id,
            'qty_scanned' => 1,
            'allocated_qty' => 0,
        ]);

        app(\App\Services\Inventory\InventoryService::class)->stockIn(
            warehouseId: $this->warehouse->id,
            itemId: $this->item->id,
            qty: 1,
            date: $shipment->date,
            sourceType: 'shipment_test_opening',
            sourceId: $shipment->id,
            notes: 'Opening stock for Shipment submit route test',
            unitCost: 50000,
            affectLotCost: false,
        );

        return $shipment;
    }

    private function legacyAccount(string $code, string $name): Account
    {
        return Account::query()->updateOrCreate(
            ['code' => $code],
            ['name' => $name, 'type' => str_starts_with($code, '5') ? 'expense' : 'asset', 'is_active' => true],
        );
    }

    private function ensureProductionMapping(string $legacyCode, string $standaloneCode): void
    {
        StandaloneAccountingAccount::query()->updateOrCreate(
            ['code' => $standaloneCode],
            ['name' => 'Test '.$standaloneCode, 'type' => str_starts_with($standaloneCode, '5-') ? 'expense' : 'asset', 'is_active' => true],
        );
        StandaloneAccountingAccountMapping::query()->updateOrCreate(
            ['legacy_account_code' => $legacyCode],
            ['standalone_account_code' => $standaloneCode, 'purpose' => 'Test mapping', 'is_active' => true],
        );
    }
}
