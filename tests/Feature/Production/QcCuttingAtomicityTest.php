<?php

namespace Tests\Feature\Production;

use App\Models\CuttingJob;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Lot;
use App\Models\PieceRate;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Production\CuttingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class QcCuttingAtomicityTest extends TestCase
{
    use RefreshDatabase;

    public function test_missing_piece_rate_does_not_persist_qc_result(): void
    {
        [$owner, $job, $bundle] = $this->makeCuttingJob(withPieceRate: false);

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), [
                'qc_date' => '2026-09-05',
                'results' => [[
                    'cutting_job_bundle_id' => $bundle->id,
                    'qty_ok' => 1,
                    'qty_reject' => 0,
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHas('error', fn (string $error) => str_contains($error, 'PieceRate cutting belum diset'));

        $this->assertDatabaseMissing('qc_results', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
        ]);
        $this->assertDatabaseHas('cutting_jobs', [
            'id' => $job->id,
            'status' => 'cut',
        ]);
        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'qty_qc_ok' => 0,
            'qty_qc_reject' => 0,
        ]);
    }

    public function test_qc_result_rolls_back_when_wip_posting_fails(): void
    {
        [$owner, $job, $bundle] = $this->makeCuttingJob(withPieceRate: true);

        $cutting = \Mockery::mock(CuttingService::class);
        $cutting->shouldReceive('validatePieceRatesForQc')
            ->once()
            ->andReturnNull();
        $cutting->shouldReceive('createWipFromCuttingQc')
            ->once()
            ->andThrow(new \RuntimeException('simulated WIP failure'));
        $this->app->instance(CuttingService::class, $cutting);

        $this->actingAs($owner)
            ->put(route('production.qc.cutting.update', $job), [
                'qc_date' => '2026-09-05',
                'results' => [[
                    'cutting_job_bundle_id' => $bundle->id,
                    'qty_ok' => 1,
                    'qty_reject' => 0,
                ]],
            ])
            ->assertRedirect()
            ->assertSessionHas('error', 'QC gagal: simulated WIP failure');

        $this->assertDatabaseMissing('qc_results', [
            'cutting_job_id' => $job->id,
            'cutting_job_bundle_id' => $bundle->id,
        ]);
        $this->assertDatabaseHas('cutting_job_bundles', [
            'id' => $bundle->id,
            'qty_qc_ok' => 0,
            'qty_qc_reject' => 0,
        ]);
    }

    /** @return array{0: User, 1: CuttingJob, 2: CuttingJobBundle} */
    private function makeCuttingJob(bool $withPieceRate): array
    {
        $employee = Employee::create([
            'code' => 'CUT-ATOMIC-'.uniqid(),
            'name' => 'Operator Cutting Atomicity',
            'role' => 'cutting',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $ownerEmployee = Employee::create([
            'code' => 'OWN-ATOMIC-'.uniqid(),
            'name' => 'Owner Atomicity',
            'role' => 'owner',
            'payment_type' => 'variable',
            'active' => true,
        ]);
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => $ownerEmployee->code,
            'employee_id' => $ownerEmployee->id,
        ]);

        $warehouse = Warehouse::create([
            'code' => 'RM-ATOMIC-'.uniqid(),
            'name' => 'Raw Materials Atomicity',
            'type' => 'internal',
            'active' => true,
        ]);
        $category = ItemCategory::create([
            'code' => 'ATOMIC-'.uniqid(),
            'name' => 'Atomicity Test',
        ]);
        $fabric = Item::create([
            'code' => 'FAB-ATOMIC-'.uniqid(),
            'name' => 'Atomicity Fabric',
            'type' => 'material',
            'item_category_id' => $category->id,
        ]);
        $finished = Item::create([
            'code' => 'FG-ATOMIC-'.uniqid(),
            'name' => 'Atomicity Finished Good',
            'type' => 'finished_good',
            'item_category_id' => $category->id,
        ]);
        $lot = Lot::create([
            'code' => 'LOT-ATOMIC-'.uniqid(),
            'item_id' => $fabric->id,
            'initial_qty' => 10,
            'initial_cost' => 100,
            'qty_onhand' => 10,
            'total_cost' => 100,
            'avg_cost' => 10,
            'status' => 'open',
        ]);

        if ($withPieceRate) {
            PieceRate::create([
                'module' => 'cutting',
                'employee_id' => $employee->id,
                'item_category_id' => $category->id,
                'rate_per_pcs' => 800,
                'effective_from' => '2026-01-01',
            ]);
        }

        $job = CuttingJob::create([
            'code' => 'CUT-ATOMIC-'.uniqid(),
            'date' => '2026-09-05',
            'warehouse_id' => $warehouse->id,
            'lot_id' => $lot->id,
            'fabric_item_id' => $fabric->id,
            'operator_id' => $employee->id,
            'total_bundles' => 1,
            'total_qty_pcs' => 1,
            'status' => 'cut',
        ]);
        $bundle = CuttingJobBundle::create([
            'cutting_job_id' => $job->id,
            'bundle_code' => 'BND-ATOMIC-'.uniqid(),
            'bundle_no' => 1,
            'lot_id' => $lot->id,
            'finished_item_id' => $finished->id,
            'item_category_id' => $category->id,
            'qty_pcs' => 1,
            'qty_used_fabric' => 1,
            'operator_id' => $employee->id,
            'status' => 'cut',
        ]);

        return [$owner, $job, $bundle];
    }
}
