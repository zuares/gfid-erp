<?php

namespace Tests\Feature\Inventory;

use App\Services\Inventory\DefectInventoryAuditService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class DefectInventoryAuditTest extends TestCase
{
    use RefreshDatabase;

    public function test_audit_compares_current_stock_with_mutations_and_account_1204(): void
    {
        $rejectSew = $this->warehouse('REJ-SEW');
        $rejectFin = $this->warehouse('REJ-FIN');
        $rejectCut = $this->warehouse('REJ-CUT');
        $rts = $this->warehouse('WH-RTS');

        $original = $this->item('TSHIRT-BLK', 'T-shirt black');
        $rejectCategoryPrefix = $this->item('REJ-TSHIRT', 'Reject T-shirt legacy');
        $rejectCategorySuffix = $this->item('TSHIRT-RJCT', 'T-shirt reject');

        DB::table('inventory_stocks')->insert([
            [
                'warehouse_id' => $rejectSew,
                'item_id' => $original,
                'qty' => 4,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'warehouse_id' => $rejectFin,
                'item_id' => $original,
                'qty' => 7,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'warehouse_id' => $rejectCut,
                'item_id' => $original,
                'qty' => 3,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'warehouse_id' => $rts,
                'item_id' => $rejectCategorySuffix,
                'qty' => 2,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->mutation($rejectSew, $original, 5, 100, 20);
        $this->mutation($rejectSew, $original, -1, -20, 20);
        $this->mutation($rejectFin, $original, 5, 50, 10);
        $this->mutation($rejectCut, $original, 3, null, null);
        $this->mutation($rts, $rejectCategorySuffix, 2, 40, 20);

        $accountId = DB::table('accounts')->where('code', '1204')->value('id');
        $activeJournal = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => 'Test defect balance',
            'source_type' => 'test_defect',
            'source_id' => 1,
            'posted_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_lines')->insert([
            [
                'journal_id' => $activeJournal,
                'account_id' => $accountId,
                'debit' => 200,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $voidedJournal = DB::table('journals')->insertGetId([
            'date' => now()->toDateString(),
            'description' => 'Voided test defect balance',
            'source_type' => 'test_defect_void',
            'source_id' => 2,
            'posted_at' => now(),
            'voided_at' => now(),
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('journal_lines')->insert([
            [
                'journal_id' => $voidedJournal,
                'account_id' => $accountId,
                'debit' => 999,
                'credit' => 0,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $report = app(DefectInventoryAuditService::class)->audit();

        $this->assertSame(16.0, $report['totals']['stock_qty']);
        $this->assertSame(14.0, $report['totals']['mutation_qty']);
        $this->assertSame(2.0, $report['totals']['qty_variance']);
        $this->assertSame(170.0, $report['totals']['mutation_value']);
        $this->assertSame(3.0, $report['totals']['unvalued_qty']);
        $this->assertSame(200.0, $report['account_1204']['balance']);
        $this->assertSame(30.0, $report['totals']['account_1204_variance']);

        $this->assertSame(1, count($report['pair_mismatches']));
        $this->assertSame('REJ-FIN', $report['pair_mismatches'][0]['warehouse_code']);
        $this->assertSame(1, count($report['unvalued_rows']));
        $this->assertSame('REJ-CUT', $report['unvalued_rows'][0]['warehouse_code']);

        $this->assertCount(1, $report['duplicate_sku_groups']);
        $this->assertSame(['REJ-TSHIRT', 'TSHIRT-RJCT'], $report['duplicate_sku_groups'][0]['codes']);
        $this->assertSame('REVIEW', $report['summary']['status']);
    }

    public function test_command_is_read_only_and_can_fail_on_findings(): void
    {
        $warehouse = $this->warehouse('REJ-SEW');
        $item = $this->item('TEST-RJCT', 'Test reject');

        DB::table('inventory_stocks')->insert([
            'warehouse_id' => $warehouse,
            'item_id' => $item,
            'qty' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->mutation($warehouse, $item, 1, null, null);

        $beforeMutations = DB::table('inventory_mutations')->count();
        $beforeStocks = DB::table('inventory_stocks')->count();

        $this->artisan('inventory:audit-defect-stock', ['--fail-on-finding' => true])
            ->expectsOutputToContain('READ ONLY')
            ->expectsOutputToContain('Status: REVIEW')
            ->assertFailed();

        $this->assertSame($beforeMutations, DB::table('inventory_mutations')->count());
        $this->assertSame($beforeStocks, DB::table('inventory_stocks')->count());
    }

    private function warehouse(string $code): int
    {
        return (int) DB::table('warehouses')->insertGetId([
            'code' => $code,
            'name' => $code,
            'type' => 'internal',
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function item(string $code, string $name): int
    {
        return (int) DB::table('items')->insertGetId([
            'code' => $code,
            'name' => $name,
            'unit' => 'pcs',
            'type' => 'finished_good',
            'item_role' => 'finished_good',
            'is_stocked' => true,
            'hpp_behavior' => 'hpp',
            'last_purchase_price' => 0,
            'hpp' => 0,
            'active' => true,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }

    private function mutation(int $warehouseId, int $itemId, float $qty, ?float $totalCost, ?float $unitCost): void
    {
        DB::table('inventory_mutations')->insert([
            'date' => now()->toDateString(),
            'warehouse_id' => $warehouseId,
            'item_id' => $itemId,
            'qty_change' => $qty,
            'direction' => $qty >= 0 ? 'in' : 'out',
            'source_type' => 'test_defect',
            'source_id' => 1,
            'unit_cost' => $unitCost,
            'total_cost' => $totalCost,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
    }
}
