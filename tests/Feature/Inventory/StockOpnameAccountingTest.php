<?php

use App\Models\Account;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\StockOpname;
use App\Models\StockOpnameLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\StandaloneInventoryAdjustmentPostingService;
use App\Services\Inventory\StockOpnameService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

uses(RefreshDatabase::class);

function stockOpnameTestItem(string $code = 'TEST-ITEM'): Item
{
    return Item::create([
        'code' => $code,
        'name' => 'Test item',
        'unit' => 'pcs',
        'type' => 'material',
        'hpp' => 100,
        'active' => true,
    ]);
}

function stockOpnameTestWarehouse(): Warehouse
{
    return Warehouse::create([
        'code' => 'TEST-WH-'.fake()->unique()->numerify('###'),
        'name' => 'Test warehouse',
        'type' => 'internal',
        'active' => true,
    ]);
}

it('generates stock opname quantities from the snapshot cutoff', function () {
    $warehouse = stockOpnameTestWarehouse();
    $item = stockOpnameTestItem();
    $user = User::factory()->create(['employee_code' => 'SO-OWNER-1', 'role' => 'owner']);
    $snapshotAt = now()->subMinute();

    $opname = StockOpname::create([
        'code' => 'SO-TEST-CUTOFF',
        'date' => $snapshotAt->toDateString(),
        'snapshot_at' => $snapshotAt,
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_PERIODIC,
        'status' => StockOpname::STATUS_COUNTING,
        'created_by' => $user->id,
    ]);

    DB::table('inventory_mutations')->insert([
        'date' => $snapshotAt->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 5,
        'direction' => 'in',
        'source_type' => 'test_before_snapshot',
        'source_id' => 1,
        'created_at' => $snapshotAt->copy()->subSecond(),
        'updated_at' => $snapshotAt->copy()->subSecond(),
    ]);
    DB::table('inventory_mutations')->insert([
        'date' => $snapshotAt->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 9,
        'direction' => 'in',
        'source_type' => 'test_after_snapshot',
        'source_id' => 2,
        'created_at' => $snapshotAt->copy()->addSecond(),
        'updated_at' => $snapshotAt->copy()->addSecond(),
    ]);

    app(StockOpnameService::class)->generateLinesFromWarehouse($opname, $warehouse->id, false);

    expect((float) $opname->lines()->sole()->system_qty)->toBe(5.0);
});

it('posts opening stock to owner equity and mirrors it into standalone accounting once', function () {
    $warehouse = stockOpnameTestWarehouse();
    $item = stockOpnameTestItem('TEST-OPENING-ITEM');
    $user = User::factory()->create(['employee_code' => 'SO-OPENING-OWNER', 'role' => 'owner']);

    $legacyAccounts = [
        '1201' => Account::updateOrCreate(
            ['code' => '1201'],
            ['name' => 'Persediaan Bahan Baku', 'type' => 'asset', 'is_active' => true],
        ),
        '3101' => Account::updateOrCreate(
            ['code' => '3101'],
            ['name' => 'Modal Pemilik', 'type' => 'equity', 'is_active' => true],
        ),
    ];
    $standaloneAccounts = [
        '1-150' => StandaloneAccountingAccount::updateOrCreate(
            ['code' => '1-150'],
            ['name' => 'Persediaan Bahan dan Material', 'type' => 'asset', 'is_active' => true],
        ),
        '3-110' => StandaloneAccountingAccount::updateOrCreate(
            ['code' => '3-110'],
            ['name' => 'Modal Disetor', 'type' => 'equity', 'is_active' => true],
        ),
    ];
    StandaloneAccountingAccountMapping::updateOrCreate(
        ['legacy_account_code' => '1201'],
        ['standalone_account_code' => '1-150', 'purpose' => 'Opening inventory', 'is_active' => true],
    );
    StandaloneAccountingAccountMapping::updateOrCreate(
        ['legacy_account_code' => '3101'],
        ['standalone_account_code' => '3-110', 'purpose' => 'Opening equity', 'is_active' => true],
    );

    $opname = StockOpname::create([
        'code' => 'SO-TEST-OPENING-JOURNAL',
        'date' => now()->toDateString(),
        'snapshot_at' => now(),
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_OPENING,
        'status' => StockOpname::STATUS_REVIEWED,
        'created_by' => $user->id,
    ]);
    StockOpnameLine::create([
        'stock_opname_id' => $opname->id,
        'item_id' => $item->id,
        'system_qty' => 0,
        'physical_qty' => 5,
        'difference_qty' => 5,
        'is_counted' => true,
        'unit_cost' => 100,
    ]);

    $this->actingAs($user);

    $adjustment = app(StockOpnameService::class)->finalize($opname);

    $journal = Journal::query()
        ->where('source_type', 'inventory_adjustment')
        ->where('source_id', $adjustment->id)
        ->with('lines.account')
        ->sole();
    $legacyLines = $journal->lines->keyBy(fn ($line) => $line->account->code);

    expect($journal->description)->toContain('Saldo Awal Persediaan')
        ->and((float) $legacyLines['1201']->debit)->toBe(500.0)
        ->and((float) $legacyLines['3101']->credit)->toBe(500.0)
        ->and(InventoryMutation::where('source_id', $adjustment->id)->count())->toBe(1)
        ->and((float) InventoryStock::where('warehouse_id', $warehouse->id)->where('item_id', $item->id)->value('qty'))->toBe(5.0);

    $standalone = StandaloneAccountingJournal::query()
        ->where('source_type', 'inventory_adjustment')
        ->where('source_id', $adjustment->id)
        ->with('lines.account')
        ->sole();
    $standaloneLines = $standalone->lines->keyBy(fn ($line) => $line->account->code);

    expect((float) $standaloneLines['1-150']->debit)->toBe(500.0)
        ->and((float) $standaloneLines['3-110']->credit)->toBe(500.0)
        ->and(app(JournalService::class)->postInventoryAdjustment($adjustment)->id)->toBe($journal->id)
        ->and(app(StandaloneInventoryAdjustmentPostingService::class)->post($adjustment)->id)->toBe($standalone->id)
        ->and(StandaloneAccountingJournal::where('source_type', 'inventory_adjustment')->where('source_id', $adjustment->id)->count())->toBe(1);

    $this->get(route('inventory.stock_opnames.show', $opname))
        ->assertOk()
        ->assertSee(route('standalone-accounting.journals.show', $standalone), false);
});

it('refuses to finalize when a counted item changed after the snapshot', function () {
    $warehouse = stockOpnameTestWarehouse();
    $item = stockOpnameTestItem('TEST-ITEM-2');
    $user = User::factory()->create(['employee_code' => 'SO-OWNER-2', 'role' => 'owner']);
    $snapshotAt = now()->subMinute();

    $opname = StockOpname::create([
        'code' => 'SO-TEST-STALE',
        'date' => $snapshotAt->toDateString(),
        'snapshot_at' => $snapshotAt,
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_PERIODIC,
        'status' => StockOpname::STATUS_REVIEWED,
        'created_by' => $user->id,
    ]);
    StockOpnameLine::create([
        'stock_opname_id' => $opname->id,
        'item_id' => $item->id,
        'system_qty' => 5,
        'physical_qty' => 4,
        'difference_qty' => -1,
        'is_counted' => true,
    ]);
    DB::table('inventory_mutations')->insert([
        'date' => $snapshotAt->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 1,
        'direction' => 'in',
        'source_type' => 'test_after_snapshot',
        'source_id' => 3,
        'created_at' => $snapshotAt->copy()->addSecond(),
        'updated_at' => $snapshotAt->copy()->addSecond(),
    ]);

    $this->actingAs($user);

    expect(fn () => app(StockOpnameService::class)->finalize($opname))
        ->toThrow(RuntimeException::class, 'setelah snapshot');

    expect(InventoryAdjustment::count())->toBe(0)
        ->and($opname->fresh()->status)->toBe(StockOpname::STATUS_REVIEWED);
});

it('rolls back finalize when the standalone accounting mapping is missing', function () {
    $warehouse = stockOpnameTestWarehouse();
    $item = stockOpnameTestItem('TEST-ITEM-MAPPING');
    $user = User::factory()->create(['employee_code' => 'SO-OWNER-MAPPING', 'role' => 'owner']);
    $snapshotAt = now()->subMinute();

    Account::updateOrCreate(
        ['code' => '1201'],
        ['name' => 'Persediaan Test', 'type' => 'asset', 'is_active' => true],
    );
    Account::updateOrCreate(
        ['code' => '6115'],
        ['name' => 'Selisih Stock Opname Test', 'type' => 'expense', 'is_active' => true],
    );
    StandaloneAccountingAccountMapping::query()
        ->whereIn('legacy_account_code', ['1201', '6115'])
        ->delete();

    $opname = StockOpname::create([
        'code' => 'SO-TEST-MAPPING-ROLLBACK',
        'date' => $snapshotAt->toDateString(),
        'snapshot_at' => $snapshotAt,
        'warehouse_id' => $warehouse->id,
        'type' => StockOpname::TYPE_PERIODIC,
        'status' => StockOpname::STATUS_REVIEWED,
        'created_by' => $user->id,
    ]);
    StockOpnameLine::create([
        'stock_opname_id' => $opname->id,
        'item_id' => $item->id,
        'system_qty' => 5,
        'physical_qty' => 4,
        'difference_qty' => -1,
        'is_counted' => true,
    ]);
    DB::table('inventory_mutations')->insert([
        'date' => $snapshotAt->toDateString(),
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty_change' => 5,
        'direction' => 'in',
        'unit_cost' => 100,
        'total_cost' => 500,
        'source_type' => 'test_before_snapshot',
        'source_id' => 4,
        'created_at' => $snapshotAt->copy()->subSecond(),
        'updated_at' => $snapshotAt->copy()->subSecond(),
    ]);
    InventoryStock::create([
        'warehouse_id' => $warehouse->id,
        'item_id' => $item->id,
        'qty' => 5,
    ]);

    $this->actingAs($user);

    expect(fn () => app(StockOpnameService::class)->finalize($opname))
        ->toThrow(ValidationException::class, 'Mapping akun legacy');

    expect(InventoryAdjustment::count())->toBe(0)
        ->and(InventoryMutation::count())->toBe(1)
        ->and($opname->fresh()->status)->toBe(StockOpname::STATUS_REVIEWED);
});

it('mirrors a posted inventory adjustment once into standalone accounting', function () {
    $warehouse = stockOpnameTestWarehouse();
    $user = User::factory()->create(['employee_code' => 'SO-OWNER-3', 'role' => 'owner']);
    $adjustment = InventoryAdjustment::create([
        'code' => 'ADJ-TEST-MIRROR',
        'date' => now()->toDateString(),
        'warehouse_id' => $warehouse->id,
        'status' => InventoryAdjustment::STATUS_APPROVED,
        'created_by' => $user->id,
    ]);

    foreach ([
        ['legacy' => '1201', 'standalone' => '1-150', 'side' => 'debit'],
        ['legacy' => '6115', 'standalone' => '6-210', 'side' => 'credit'],
    ] as $index => $row) {
        Account::updateOrCreate(
            ['code' => $row['legacy']],
            ['name' => 'Test '.$row['legacy'], 'type' => 'asset', 'is_active' => true],
        );
        StandaloneAccountingAccount::updateOrCreate(
            ['code' => $row['standalone']],
            ['name' => 'Test '.$row['standalone'], 'type' => 'asset', 'is_active' => true],
        );
        StandaloneAccountingAccountMapping::updateOrCreate(
            ['legacy_account_code' => $row['legacy']],
            ['standalone_account_code' => $row['standalone'], 'purpose' => 'test', 'is_active' => true],
        );
    }

    $journal = Journal::create([
        'date' => $adjustment->date,
        'description' => 'Test inventory adjustment',
        'source_type' => 'inventory_adjustment',
        'source_id' => $adjustment->id,
        'posted_at' => now(),
    ]);
    $legacyAccounts = Account::whereIn('code', ['1201', '6115'])->pluck('id', 'code');
    $journal->lines()->createMany([
        ['account_id' => $legacyAccounts['1201'], 'debit' => 100, 'credit' => 0],
        ['account_id' => $legacyAccounts['6115'], 'debit' => 0, 'credit' => 100],
    ]);

    $service = app(StandaloneInventoryAdjustmentPostingService::class);
    $first = $service->post($adjustment);
    $second = $service->post($adjustment);

    expect($first?->id)->toBe($second?->id)
        ->and(StandaloneAccountingJournal::where('source_type', 'inventory_adjustment')
            ->where('source_id', $adjustment->id)->count())->toBe(1)
        ->and((float) $first->lines()->sum('debit'))->toBe(100.0)
        ->and((float) $first->lines()->sum('credit'))->toBe(100.0);
});
