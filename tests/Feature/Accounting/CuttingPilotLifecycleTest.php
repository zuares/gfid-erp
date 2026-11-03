<?php

namespace Tests\Feature\Accounting;

use App\Models\Account;
use App\Models\CuttingJob;
use App\Models\CuttingJobLot;
use App\Models\Employee;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Journal;
use App\Models\Lot;
use App\Models\PieceRate;
use App\Models\StandaloneAccountingJournal;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Accounting\StandaloneProductionPostingService;
use App\Services\Production\CuttingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CuttingPilotLifecycleTest extends TestCase
{
    use RefreshDatabase;

    public function test_void_unpost_reverses_stock_and_new_ledger_then_repost_uses_new_trace(): void
    {
        config(['accounting.standalone_dual_posting_production_cutting_enabled' => true]);

        [$owner, $job, $lot, $stock] = $this->makePostedPilotJob();

        $this->actingAs($owner)
            ->post(route('production.cutting_jobs.void', $job))
            ->assertRedirect(route('production.cutting_jobs.show', $job));

        $job->refresh();
        $lot->refresh();
        $stock->refresh();

        $this->assertSame('voided', $job->status);
        $this->assertSame('voided', (string) $job->bundles()->value('status'));
        $this->assertEqualsWithDelta(10, (float) $lot->qty_onhand, 0.0001);
        $this->assertEqualsWithDelta(10, (float) $stock->qty, 0.0001);

        $this->assertSame(0, Journal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', ['cutting_job', 'cutting_job_wage'])
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(0, StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
        $this->assertSame(4, StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->count());

        $activeStandaloneTotals = StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->with('lines')
            ->get();
        $this->assertEqualsWithDelta(0, (float) $activeStandaloneTotals->flatMap->lines->sum('debit'), 0.01);
        $this->assertEqualsWithDelta(0, (float) $activeStandaloneTotals->flatMap->lines->sum('credit'), 0.01);

        $this->assertSame(4, Journal::query()
            ->where('source_id', $job->id)
            ->whereIn('source_type', ['cutting_job', 'cutting_job_wage'])
            ->whereNotNull('voided_at')
            ->count());

        $journal = app(JournalService::class);
        $repostJob = $job->fresh(['bundles']);
        $journal->postCuttingJob($repostJob);
        $journal->postCuttingJobWage($repostJob);

        $repost = app(StandaloneProductionPostingService::class)->mirrorCuttingJob($job->id);

        $this->assertCount(2, $repost);
        $this->assertSame([
            'cutting_job_repost_1',
            'cutting_job_wage_repost_1',
        ], collect($repost)->pluck('source_type')->all());
        $this->assertSame(2, StandaloneAccountingJournal::query()
            ->where('source_id', $job->id)
            ->whereNull('voided_at')
            ->count());
    }

    /** @return array{0: User, 1: CuttingJob, 2: Lot, 3: InventoryStock} */
    private function makePostedPilotJob(): array
    {
        $ownerEmployee = Employee::create([
            'code' => 'EMP-LIFECYCLE-OWNER-'.uniqid(),
            'name' => 'Lifecycle Pilot Owner',
            'role' => 'owner',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => $ownerEmployee->code,
            'employee_id' => $ownerEmployee->id,
        ]);
        $employee = Employee::create([
            'code' => 'EMP-LIFECYCLE-'.uniqid(),
            'name' => 'Lifecycle Pilot Operator',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'RM-LIFECYCLE-'.uniqid(),
            'name' => 'Lifecycle Raw Material',
            'type' => 'internal',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'LIFECYCLE-'.uniqid(),
            'name' => 'Lifecycle Pilot',
        ]);
        $fabric = Item::create([
            'code' => 'FAB-LIFECYCLE-'.uniqid(),
            'name' => 'Lifecycle Fabric',
            'type' => 'raw_material',
            'item_category_id' => $category->id,
        ]);
        $finished = Item::create([
            'code' => 'FG-LIFECYCLE-'.uniqid(),
            'name' => 'Lifecycle Finished Good',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-LIFECYCLE-'.uniqid(),
            'item_id' => $fabric->id,
            'initial_qty' => 10,
            'initial_cost' => 1000,
            'qty_onhand' => 10,
            'total_cost' => 1000,
            'avg_cost' => 100,
            'status' => 'open',
        ]);
        $stock = InventoryStock::create([
            'warehouse_id' => $warehouse->id,
            'item_id' => $fabric->id,
            'qty' => 10,
            'allocated_qty' => 0,
        ]);
        InventoryMutation::create([
            'date' => '2026-10-27',
            'warehouse_id' => $warehouse->id,
            'item_id' => $fabric->id,
            'qty_change' => 10,
            'direction' => 'in',
            'source_type' => 'lifecycle_fixture',
            'source_id' => $lot->id,
            'lot_id' => $lot->id,
            'unit_cost' => 100,
            'total_cost' => 1000,
        ]);
        PieceRate::create([
            'module' => 'cutting',
            'employee_id' => $employee->id,
            'item_id' => $finished->id,
            'rate_per_pcs' => 100,
            'effective_from' => '2026-01-01',
        ]);

        $job = app(CuttingService::class)->create([
            'code' => 'CUT-LIFECYCLE-'.uniqid(),
            'date' => '2026-10-27',
            'warehouse_id' => $warehouse->id,
            'lot_id' => $lot->id,
            'fabric_item_id' => $fabric->id,
            'operator_id' => $employee->id,
            'bundles' => [[
                'bundle_no' => 1,
                'lot_id' => $lot->id,
                'finished_item_id' => $finished->id,
                'qty_pcs' => 10,
                'qty_used_fabric' => 1,
                'operator_id' => $employee->id,
            ]],
        ]);
        CuttingJobLot::create([
            'cutting_job_id' => $job->id,
            'lot_id' => $lot->id,
            'planned_fabric_qty' => 1,
            'used_fabric_qty' => 0,
        ]);
        app(CuttingService::class)->reconsumeFabricFromLots($job->fresh());

        Account::firstOrCreate(['code' => '1201'], [
            'name' => 'Lifecycle Raw',
            'type' => 'asset',
            'is_active' => true,
        ]);
        Account::firstOrCreate(['code' => '1202'], [
            'name' => 'Lifecycle WIP',
            'type' => 'asset',
            'is_active' => true,
        ]);
        Account::firstOrCreate(['code' => '2102'], [
            'name' => 'Lifecycle Payroll',
            'type' => 'liability',
            'is_active' => true,
        ]);
        $postedJob = $job->fresh(['bundles']);
        app(JournalService::class)->postCuttingJob($postedJob);
        app(JournalService::class)->postCuttingJobWage($postedJob);
        app(StandaloneProductionPostingService::class)->mirrorCuttingJob($job->id);

        return [$owner, $job->fresh(), $lot->fresh(), $stock->fresh()];
    }
}
