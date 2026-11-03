<?php

use App\Models\InventoryAdjustment;
use App\Models\StandaloneAccountingJournal;
use App\Models\StockOpname;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows stock opname variance as a read-only standalone accounting view', function () {
    $user = User::factory()->create([
        'employee_code' => 'SO-VARIANCE-OWNER',
        'role' => 'owner',
    ]);
    $warehouse = Warehouse::create([
        'code' => 'VAR-WH-001',
        'name' => 'Variance warehouse',
        'type' => 'internal',
        'active' => true,
    ]);
    $opname = StockOpname::create([
        'code' => 'SO-VARIANCE-001',
        'date' => '2026-10-28',
        'snapshot_at' => '2026-10-28 08:30:00',
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_PERIODIC,
        'status' => StockOpname::STATUS_FINALIZED,
        'created_by' => $user->id,
    ]);
    $adjustment = InventoryAdjustment::create([
        'code' => 'ADJ-VARIANCE-001',
        'date' => '2026-10-28',
        'warehouse_id' => $warehouse->id,
        'source_type' => StockOpname::class,
        'source_id' => $opname->id,
        'status' => InventoryAdjustment::STATUS_APPROVED,
        'created_by' => $user->id,
    ]);
    $journal = StandaloneAccountingJournal::create([
        'date' => '2026-10-28',
        'reference' => 'SA-INV-ADJ-'.$adjustment->id,
        'source_type' => 'inventory_adjustment',
        'source_id' => $adjustment->id,
        'description' => 'Mirror variance test',
        'posted_at' => now(),
    ]);

    $response = $this->actingAs($user)
        ->get(route('standalone-accounting.stock-opname-variance.index'));

    $response->assertOk()
        ->assertSee('Stock Opname &amp; Variance', false)
        ->assertSee('SO-VARIANCE-001')
        ->assertSee('VAR-WH-001')
        ->assertSee('28-10-2026 08:30')
        ->assertSee(route('standalone-accounting.journals.show', $journal), false)
        ->assertSee('Approval dan perubahan stok tetap dilakukan di modul Inventory.', false);
});

it('keeps the stock opname variance view behind standalone accounting access', function () {
    $response = $this->get(route('standalone-accounting.stock-opname-variance.index'));

    $response->assertRedirect();
});
