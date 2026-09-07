<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Item;
use App\Models\SewingReturn;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class SewingReturnFinishedGoodsPostingTest extends TestCase
{
    use RefreshDatabase;

    public function test_qc_passed_sewing_return_posts_to_finished_goods_for_qc_in_flow(): void
    {
        [$return, $item, $sourceWarehouse, $destinationWarehouse] = $this->returnFixture('SR-TEST-QC-IN');

        DB::table('inventory_mutations')->insert([
            'date' => '2026-09-07',
            'warehouse_id' => $destinationWarehouse->id,
            'item_id' => $item->id,
            'qty_change' => 5,
            'direction' => 'in',
            'source_type' => 'sewing_qc_in',
            'source_id' => $return->id,
            'unit_cost' => 100,
            'total_cost' => 500,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        $journal = app(JournalService::class)->postSewingReturnOk($return);

        $this->assertNotNull($journal);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1203')->value('id'),
            'debit' => 500,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1202')->value('id'),
            'debit' => 0,
            'credit' => 500,
        ]);
    }

    public function test_legacy_sewing_return_ok_flow_also_posts_to_finished_goods(): void
    {
        [$return, $item, $sourceWarehouse, $destinationWarehouse] = $this->returnFixture('SR-TEST-LEGACY');

        DB::table('inventory_mutations')->insert([
            [
                'date' => '2026-09-07',
                'warehouse_id' => $sourceWarehouse->id,
                'item_id' => $item->id,
                'qty_change' => -5,
                'direction' => 'out',
                'source_type' => 'sewing_return_ok',
                'source_id' => $return->id,
                'unit_cost' => 100,
                'total_cost' => -500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '2026-09-07',
                'warehouse_id' => $destinationWarehouse->id,
                'item_id' => $item->id,
                'qty_change' => 5,
                'direction' => 'in',
                'source_type' => 'sewing_return_ok',
                'source_id' => $return->id,
                'unit_cost' => 100,
                'total_cost' => 500,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $journal = app(JournalService::class)->postSewingReturnOk($return);

        $this->assertNotNull($journal);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1203')->value('id'),
            'debit' => 500,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('journal_lines', [
            'journal_id' => $journal->id,
            'account_id' => Account::where('code', '1202')->value('id'),
            'debit' => 0,
            'credit' => 500,
        ]);
    }

    private function returnFixture(string $code): array
    {
        $this->account('1202', 'Persediaan WIP');
        $this->account('1203', 'Persediaan Barang Jadi');
        $this->account('1204', 'Persediaan Barang Cacat');
        $this->account('2102', 'Hutang Upah');

        $sourceWarehouse = Warehouse::create([
            'code' => 'WIP-SEW-' . substr($code, -6),
            'name' => 'WIP Sewing Test',
        ]);
        $destinationWarehouse = Warehouse::create([
            'code' => 'WH-PRD-' . substr($code, -6),
            'name' => 'Production Test',
        ]);
        $item = Item::create([
            'code' => 'ITEM-' . substr($code, -6),
            'name' => 'Test Finished Item',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'hpp' => 100,
            'last_purchase_price' => 100,
            'active' => true,
        ]);
        $return = SewingReturn::create([
            'code' => $code,
            'date' => '2026-09-07',
            'warehouse_id' => $sourceWarehouse->id,
            'status' => 'posted',
        ]);

        return [$return, $item, $sourceWarehouse, $destinationWarehouse];
    }

    private function account(string $code, string $name): Account
    {
        return Account::firstOrCreate(
            ['code' => $code],
            [
                'name' => $name,
                'type' => 'asset',
                'is_cash' => false,
                'is_active' => true,
            ]
        );
    }
}
