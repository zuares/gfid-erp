<?php

namespace App\Console\Commands;

use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\User;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedFinishingPilotDummy extends Command
{
    private const CODE = 'FIN-20261027-PILOT-OK-REJECT';

    protected $signature = 'production:seed-finishing-dummy
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Buat fixture Finishing Job posted untuk pilot accounting baru.';

    public function handle(InventoryService $inventory, JournalService $journal): int
    {
        $existing = FinishingJob::query()->where('code', self::CODE)->first();
        if ($existing) {
            return $this->report([
                'status' => 'EXISTS',
                'job' => [
                    'id' => (int) $existing->id,
                    'code' => $existing->code,
                    'status' => $existing->status,
                ],
                'message' => 'Dummy Finishing sudah ada; tidak ada data baru dibuat.',
            ]);
        }

        $bundle = CuttingJobBundle::query()
            ->whereNotNull('finished_item_id')
            ->with('finishedItem')
            ->orderBy('id')
            ->first();

        $wipFin = Warehouse::query()->where('code', 'WIP-FIN')->first();
        $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();
        $rejFin = Warehouse::query()->where('code', 'REJ-FIN')->first();

        if (! $bundle || ! $wipFin || ! $whPrd || ! $rejFin) {
            return $this->report([
                'status' => 'BLOCKED',
                'reason' => 'Butuh bundle dengan finished item dan gudang WIP-FIN, WH-PRD, REJ-FIN.',
            ], self::FAILURE);
        }

        $date = now()->toDateString();
        $unitCost = 100.0;
        $qtyOk = 1;
        $qtyReject = 1;
        $qtyUsed = $qtyOk + $qtyReject;
        $operatorId = Employee::query()
            ->whereIn('role', ['sewing', 'operating'])
            ->orderBy('id')
            ->value('id');
        $userId = User::query()->orderBy('id')->value('id');

        $job = DB::transaction(function () use (
            $bundle,
            $wipFin,
            $whPrd,
            $rejFin,
            $date,
            $unitCost,
            $qtyOk,
            $qtyReject,
            $qtyUsed,
            $operatorId,
            $userId,
            $inventory,
            $journal
        ): FinishingJob {
            $job = FinishingJob::create([
                'code' => self::CODE,
                'date' => $date,
                'status' => 'posted',
                'notes' => 'Dummy pilot Finishing OK + reject; jangan dipakai untuk transaksi riil.',
                'destination_warehouse_id' => $whPrd->id,
                'created_by' => $userId,
                'updated_by' => $userId,
                'posted_at' => now(),
            ]);

            $line = FinishingJobLine::create([
                'finishing_job_id' => $job->id,
                'bundle_id' => $bundle->id,
                'operator_id' => $operatorId,
                'item_id' => $bundle->finished_item_id,
                'qty_in' => $qtyUsed,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_cause' => 'finishing',
                'reject_reason' => 'Dummy pilot reject finishing',
                'reject_notes' => 'Fixture pilot akuntansi baru.',
                'processed_at' => $date,
            ]);

            // Seed input WIP-FIN hanya untuk fixture ini. Source type khusus
            // sengaja tidak dianggap jurnal legacy maupun mirror baru.
            $inventory->stockIn(
                warehouseId: (int) $wipFin->id,
                itemId: (int) $bundle->finished_item_id,
                qty: $qtyUsed,
                date: $date,
                sourceType: 'finishing_pilot_seed',
                sourceId: (int) $job->id,
                notes: 'Seed WIP-FIN untuk dummy Finishing pilot',
                lotId: null,
                unitCost: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: (int) $bundle->id,
            );

            $inventory->stockOut(
                warehouseId: (int) $wipFin->id,
                itemId: (int) $bundle->finished_item_id,
                qty: $qtyUsed,
                date: $date,
                sourceType: FinishingJob::class,
                sourceId: (int) $job->id,
                notes: 'Dummy Finishing OUT WIP-FIN',
                allowNegative: false,
                lotId: null,
                unitCostOverride: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: (int) $bundle->id,
            );

            $inventory->stockIn(
                warehouseId: (int) $whPrd->id,
                itemId: (int) $bundle->finished_item_id,
                qty: $qtyOk,
                date: $date,
                sourceType: FinishingJob::class,
                sourceId: (int) $job->id,
                notes: 'Dummy Finishing OK WIP-FIN ke WH-PRD',
                lotId: null,
                unitCost: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: (int) $bundle->id,
            );

            $inventory->stockIn(
                warehouseId: (int) $rejFin->id,
                itemId: (int) $bundle->finished_item_id,
                qty: $qtyReject,
                date: $date,
                sourceType: FinishingJob::class,
                sourceId: (int) $job->id,
                notes: 'Dummy Finishing reject ke REJ-FIN',
                lotId: null,
                unitCost: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: (int) $bundle->id,
            );

            // Jurnal legacy tetap menjadi sumber yang akan diaudit dan
            // dimirror; feature flag akuntansi baru tidak disentuh di sini.
            $journal->postFinishingJob($job->load('lines'));

            return $job->fresh(['lines']);
        });

        return $this->report([
            'status' => 'CREATED',
            'job' => [
                'id' => (int) $job->id,
                'code' => $job->code,
                'date' => (string) $job->date,
                'status' => $job->status,
            ],
            'bundle_id' => (int) $bundle->id,
            'item' => [
                'id' => (int) $bundle->finished_item_id,
                'code' => $bundle->finishedItem?->code,
            ],
            'quantities' => [
                'ok' => $qtyOk,
                'reject' => $qtyReject,
                'total' => $qtyUsed,
            ],
            'legacy_journal' => [
                'source_type' => JournalService::SRC_FINISHING_JOB,
                'source_id' => (int) $job->id,
            ],
            'message' => 'Dummy Finishing posted berhasil dibuat. Mirror akuntansi baru belum dijalankan.',
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function report(array $payload, int $status = self::SUCCESS): int
    {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $status;
    }
}
