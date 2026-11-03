<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\Item;
use App\Models\Journal;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Tests\TestCase;

class SewingReturnPilotLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_developer_is_treated_as_owner_for_production_controls(): void
    {
        $developer = User::factory()->make([
            'role' => 'admin',
            'is_developer' => true,
        ]);

        $this->assertTrue($developer->isDeveloper());
        $this->assertTrue($developer->isOwner());
    }

    public function test_sewing_return_ok_and_reject_mirror_is_balanced_and_idempotent(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, 7001, [
            ['code' => '1203', 'debit' => 100, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 100],
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_REJECT, 7001, [
            ['code' => '1204', 'debit' => 50, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 50],
        ]);

        $service = app(StandaloneProductionPostingService::class);
        $first = $service->mirrorSewingReturn(7001);
        $second = $service->mirrorSewingReturn(7001);

        $this->assertCount(2, $first);
        $this->assertCount(2, $second);
        $this->assertSame(2, StandaloneAccountingJournal::query()->where('source_id', 7001)->count());

        foreach ($first as $journal) {
            $this->assertEqualsWithDelta(
                (float) $journal->lines->sum('debit'),
                (float) $journal->lines->sum('credit'),
                0.01,
            );
            $this->assertNotNull($journal->posted_at);
        }
    }

    public function test_sewing_return_ok_to_wip_fin_stays_wip_in_new_accounting(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $wipFin = Warehouse::create([
            'code' => 'WIP-FIN',
            'name' => 'WIP Finishing',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'ITEM-SR-WIP-FIN',
            'name' => 'Sewing Return WIP-FIN Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $returnId = 7010;

        DB::table('inventory_mutations')->insert([
            'date' => '2026-10-27',
            'warehouse_id' => $wipFin->id,
            'item_id' => $item->id,
            'qty_change' => 2,
            'direction' => 'in',
            'source_type' => 'sewing_qc_in',
            'source_id' => $returnId,
            'unit_cost' => 100,
            'total_cost' => 200,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, $returnId, [
            ['code' => '1203', 'debit' => 200, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 200],
        ]);

        $mirrors = app(StandaloneProductionPostingService::class)->mirrorSewingReturn($returnId);
        $this->assertCount(1, $mirrors);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-151-03')->value('id'),
            'debit' => 200,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-151-02')->value('id'),
            'debit' => 0,
            'credit' => 200,
        ]);
        $this->assertDatabaseMissing('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-152')->value('id'),
            'debit' => 200,
            'credit' => 0,
        ]);
    }

    public function test_direct_sewing_return_to_wip_fin_source_is_reclassified_to_wip_fin(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $wipSew = Warehouse::create([
            'code' => 'WIP-SEW',
            'name' => 'WIP Sewing',
            'active' => true,
        ]);
        $wipFin = Warehouse::create([
            'code' => 'WIP-FIN',
            'name' => 'WIP Finishing',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'ITEM-SR-DIRECT-WIP-FIN',
            'name' => 'Direct Sewing Return WIP-FIN Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $returnId = 7012;

        DB::table('inventory_mutations')->insert([
            [
                'date' => '2026-10-28',
                'warehouse_id' => $wipSew->id,
                'item_id' => $item->id,
                'qty_change' => -2,
                'direction' => 'out',
                'source_type' => 'sewing_return_ok',
                'source_id' => $returnId,
                'unit_cost' => 100,
                'total_cost' => -200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '2026-10-28',
                'warehouse_id' => $wipFin->id,
                'item_id' => $item->id,
                'qty_change' => 2,
                'direction' => 'in',
                'source_type' => 'sewing_return_ok',
                'source_id' => $returnId,
                'unit_cost' => 100,
                'total_cost' => 200,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, $returnId, [
            ['code' => '1203', 'debit' => 200, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 200],
        ]);

        $mirrors = app(StandaloneProductionPostingService::class)->mirrorSewingReturn($returnId);

        $this->assertCount(1, $mirrors);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-151-03')->value('id'),
            'debit' => 200,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-151-02')->value('id'),
            'debit' => 0,
            'credit' => 200,
        ]);
    }

    public function test_sewing_reject_rework_mirror_uses_wip_fin_account(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $rejectWarehouse = Warehouse::create([
            'code' => 'REJ-SEW',
            'name' => 'Reject Sewing',
            'active' => true,
        ]);
        $wipFin = Warehouse::create([
            'code' => 'WIP-FIN',
            'name' => 'WIP Finishing',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'ITEM-SR-REWORK-WIP-FIN',
            'name' => 'Sewing Reject Rework WIP-FIN Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $returnId = 7013;

        DB::table('inventory_mutations')->insert([
            [
                'date' => '2026-10-28',
                'warehouse_id' => $rejectWarehouse->id,
                'item_id' => $item->id,
                'qty_change' => -1,
                'direction' => 'out',
                'source_type' => StandaloneProductionPostingService::SOURCE_SEWING_REWORK_OK,
                'source_id' => $returnId,
                'unit_cost' => 100,
                'total_cost' => -100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'date' => '2026-10-28',
                'warehouse_id' => $wipFin->id,
                'item_id' => $item->id,
                'qty_change' => 1,
                'direction' => 'in',
                'source_type' => StandaloneProductionPostingService::SOURCE_SEWING_REWORK_OK,
                'source_id' => $returnId,
                'unit_cost' => 100,
                'total_cost' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_REWORK_OK, $returnId, [
            ['code' => '1203', 'debit' => 100, 'credit' => 0],
            ['code' => '1204', 'debit' => 0, 'credit' => 100],
        ]);

        $mirrors = app(StandaloneProductionPostingService::class)->mirrorSewingReturn($returnId);

        $this->assertCount(1, $mirrors);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-151-03')->value('id'),
            'debit' => 100,
            'credit' => 0,
        ]);
        $this->assertDatabaseHas('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-153')->value('id'),
            'debit' => 0,
            'credit' => 100,
        ]);
        $this->assertDatabaseMissing('standalone_accounting_journal_lines', [
            'journal_id' => $mirrors[0]->id,
            'account_id' => \App\Models\StandaloneAccountingAccount::where('code', '1-152')->value('id'),
            'debit' => 100,
            'credit' => 0,
        ]);
    }

    public function test_mixed_sewing_return_destinations_block_new_accounting_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $wipFin = Warehouse::create([
            'code' => 'WIP-FIN',
            'name' => 'WIP Finishing',
            'active' => true,
        ]);
        $whPrd = Warehouse::create([
            'code' => 'WH-PRD',
            'name' => 'Produksi',
            'active' => true,
        ]);
        $item = Item::create([
            'code' => 'ITEM-SR-MIXED-DEST',
            'name' => 'Sewing Return Mixed Destination Test',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
        ]);
        $returnId = 7011;

        foreach ([[$wipFin->id, 1], [$whPrd->id, 1]] as [$warehouseId, $qty]) {
            DB::table('inventory_mutations')->insert([
                'date' => '2026-10-27',
                'warehouse_id' => $warehouseId,
                'item_id' => $item->id,
                'qty_change' => $qty,
                'direction' => 'in',
                'source_type' => 'sewing_qc_in',
                'source_id' => $returnId,
                'unit_cost' => 100,
                'total_cost' => 100,
                'created_at' => now(),
                'updated_at' => now(),
            ]);
        }
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, $returnId, [
            ['code' => '1203', 'debit' => 200, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 200],
        ]);

        try {
            app(StandaloneProductionPostingService::class)->mirrorSewingReturn($returnId);
            $this->fail('Mirror seharusnya berhenti ketika tujuan Sewing Return bercampur.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('tujuan stok campuran', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', ['source_id' => $returnId]);
    }

    public function test_sewing_return_void_creates_voided_reversals_and_repost_trace(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $legacy = $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, 7002, [
            ['code' => '1203', 'debit' => 75, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 75],
        ]);
        $service = app(StandaloneProductionPostingService::class);
        $mirrors = $service->mirrorSewingReturn(7002);

        $service->voidSewingReturn(7002);
        $service->voidSewingReturn(7002);

        $this->assertNotNull($mirrors[0]->fresh()->voided_at);
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', 7002)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(2, StandaloneAccountingJournal::query()->where('source_id', 7002)->count());
        $this->assertNotNull(StandaloneAccountingJournal::query()
            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK.'_reversal_'.$mirrors[0]->id)
            ->whereNotNull('voided_at')
            ->first());

        $legacy->forceFill(['voided_at' => now()])->save();
        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, 7002, [
            ['code' => '1203', 'debit' => 60, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 60],
        ]);

        $repost = $service->mirrorSewingReturn(7002);
        $this->assertCount(1, $repost);
        $this->assertSame(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK.'_repost_1', $repost[0]->source_type);
        $this->assertNull($repost[0]->fresh()->voided_at);
    }

    public function test_missing_sewing_return_mapping_blocks_without_creating_mirror(): void
    {
        config(['accounting.standalone_dual_posting_production_sewing_return_enabled' => true]);

        $this->legacyJournal(StandaloneProductionPostingService::SOURCE_SEWING_RETURN_OK, 7003, [
            ['code' => '9999', 'debit' => 10, 'credit' => 0],
            ['code' => '1202', 'debit' => 0, 'credit' => 10],
        ]);

        try {
            app(StandaloneProductionPostingService::class)->mirrorSewingReturn(7003);
            $this->fail('Mirror seharusnya gagal ketika mapping akun tidak tersedia.');
        } catch (ValidationException $exception) {
            $this->assertStringContainsString('9999', (string) $exception->getMessage());
        }

        $this->assertDatabaseMissing('standalone_accounting_journals', ['source_id' => 7003]);
    }

    /** @param array<int, array{code:string,debit:int|float,credit:int|float}> $lines */
    private function legacyJournal(string $sourceType, int $sourceId, array $lines): Journal
    {
        $journal = Journal::create([
            'date' => '2026-10-27',
            'description' => 'Test '.$sourceType.' #'.$sourceId,
            'source_type' => $sourceType,
            'source_id' => $sourceId,
            'posted_at' => now(),
        ]);

        $journal->lines()->createMany(array_map(function (array $line): array {
            return [
                'account_id' => Account::firstOrCreate(
                    ['code' => $line['code']],
                    ['name' => 'Test '.$line['code'], 'type' => 'asset', 'is_active' => true],
                )->id,
                'debit' => $line['debit'],
                'credit' => $line['credit'],
            ];
        }, $lines));

        return $journal->load('lines.account');
    }
}
