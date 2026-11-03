<?php

use App\Models\CuttingBundleCorrection;
use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Journal;
use App\Models\Lot;
use App\Models\PurchaseRequest;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('allows only developer to reset accounting inventory and production ledgers', function () {
    $developer = User::factory()->create([
        'employee_code' => 'DEV-RESET',
        'role' => 'admin',
        'is_developer' => true,
    ]);
    $item = Item::create([
        'code' => 'RESET-MASTER-ITEM',
        'name' => 'Master item survives reset',
        'unit' => 'pcs',
        'type' => 'material',
        'hpp' => 100,
        'active' => true,
    ]);
    $warehouse = Warehouse::create([
        'code' => 'RESET-WH-001',
        'name' => 'Reset warehouse',
        'type' => 'internal',
        'active' => true,
    ]);
    $lot = Lot::create([
        'code' => 'RESET-LOT-001',
        'item_id' => $item->id,
        'initial_qty' => 1,
        'initial_cost' => 100,
        'qty_onhand' => 1,
        'total_cost' => 100,
        'avg_cost' => 100,
        'status' => 'open',
    ]);
    $cuttingJob = CuttingJob::create([
        'code' => 'RESET-CUT-001',
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'lot_id' => $lot->id,
        'fabric_item_id' => $item->id,
        'total_bundles' => 1,
        'total_qty_pcs' => 1,
        'status' => 'cut',
        'created_by' => $developer->id,
    ]);
    $bundle = CuttingJobBundle::create([
        'cutting_job_id' => $cuttingJob->id,
        'bundle_code' => 'RESET-BUNDLE-001',
        'bundle_no' => 1,
        'lot_id' => $lot->id,
        'finished_item_id' => $item->id,
        'qty_pcs' => 1,
        'qty_used_fabric' => 1,
        'status' => 'cut',
    ]);
    CuttingBundleCorrection::create([
        'cutting_job_id' => $cuttingJob->id,
        'cutting_job_bundle_id' => $bundle->id,
        'original_item_id' => $item->id,
        'replacement_item_id' => $item->id,
        'lot_id' => $lot->id,
        'warehouse_id' => $warehouse->id,
        'return_fabric_qty' => 1,
        'unit_cost' => 100,
        'return_cost' => 100,
        'status' => 'returned',
        'reason' => 'Reset test',
        'corrected_by' => $developer->id,
        'corrected_at' => now(),
    ]);

    Journal::create([
        'date' => now()->toDateString(),
        'description' => 'Reset test',
        'source_type' => 'reset_test',
        'source_id' => 1,
        'posted_at' => now(),
    ]);
    StandaloneAccountingJournal::create([
        'date' => now()->toDateString(),
        'reference' => 'RESET-TEST-1',
        'source_type' => 'reset_test',
        'source_id' => 1,
        'description' => 'Reset test',
        'posted_at' => now(),
    ]);
    $adjustment = InventoryAdjustment::create([
        'code' => 'RESET-ADJ-001',
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'status' => InventoryAdjustment::STATUS_APPROVED,
        'created_by' => $developer->id,
    ]);
    InventoryMutation::create([
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 2,
        'direction' => 'in',
        'source_type' => InventoryAdjustment::class,
        'source_id' => $adjustment->id,
        'unit_cost' => 100,
        'total_cost' => 200,
    ]);
    InventoryStock::create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty' => 2,
    ]);
    PurchaseRequest::create([
        'code' => 'RESET-PR-001',
        'date' => now()->toDateString(),
        'requested_by' => $developer->id,
        'status' => 'draft',
    ]);

    $this->actingAs($developer)
        ->post(route('developer.reset-ledgers'), ['confirmation' => 'RESET SALDO'])
        ->assertRedirect()
        ->assertSessionHas('status', 'success');

    expect(Journal::count())->toBe(0)
        ->and(StandaloneAccountingJournal::count())->toBe(0)
        ->and(InventoryAdjustment::count())->toBe(0)
        ->and(InventoryMutation::count())->toBe(0)
        ->and(InventoryStock::count())->toBe(0)
        ->and(CuttingBundleCorrection::count())->toBe(0)
        ->and(CuttingJob::count())->toBe(0)
        ->and(CuttingJobBundle::count())->toBe(0)
        ->and(PurchaseRequest::count())->toBe(0)
        ->and(Item::whereKey($item)->exists())->toBeTrue()
        ->and(Warehouse::whereKey($warehouse)->exists())->toBeTrue();
});

it('rejects the reset for non-developer users', function () {
    $user = User::factory()->create([
        'employee_code' => 'OWNER-RESET-TEST',
        'role' => 'owner',
        'is_developer' => false,
    ]);

    $this->actingAs($user)
        ->post(route('developer.reset-ledgers'), ['confirmation' => 'RESET SALDO'])
        ->assertForbidden();
});
