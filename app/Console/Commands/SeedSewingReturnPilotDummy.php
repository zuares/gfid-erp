<?php

namespace App\Console\Commands;

use App\Models\QcResult;
use App\Models\SewingPickupLine;
use App\Models\SewingReturn;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedSewingReturnPilotDummy extends Command
{
    protected $signature = 'production:seed-sewing-return-dummy
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Buat fixture Sewing Return OK/reject untuk pilot accounting baru.';

    public function handle(InventoryService $inventory, JournalService $journal): int
    {
        $code = 'SWR-20261027-PILOT-OK-REJECT';
        $existing = SewingReturn::query()->where('code', $code)->first();
        if ($existing) {
            // Idempotent repair untuk fixture: sinkronkan counter pickup dari
            // return line yang sudah tersimpan, tanpa menyentuh jurnal legacy.
            $existing->load('lines');
            foreach ($existing->lines->groupBy('sewing_pickup_line_id') as $lineId => $lines) {
                $pickupLine = SewingPickupLine::query()->find($lineId);
                if ($pickupLine) {
                    $pickupLine->forceFill([
                        'qty_returned_ok' => (float) $lines->sum('qty_ok'),
                        'qty_returned_reject' => (float) $lines->sum('qty_reject'),
                        'status' => 'done',
                    ])->save();
                }
            }

            return $this->report([
                'status' => 'EXISTS',
                'return' => ['id' => (int) $existing->id, 'code' => $existing->code],
                'message' => 'Dummy sudah ada; tidak ada data baru dibuat.',
            ]);
        }

        $pickupLine = SewingPickupLine::query()
            ->where('status', '!=', 'void')
            ->where('qty_bundle', '>=', 2)
            ->with(['sewingPickup', 'bundle'])
            ->orderByDesc('id')
            ->first();

        $wipSew = Warehouse::query()->where('code', 'WIP-SEW')->first();
        $whPrd = Warehouse::query()->where('code', 'WH-PRD')->first();
        $rejSew = Warehouse::query()->where('code', 'REJ-SEW')->first();

        if (! $pickupLine || ! $wipSew || ! $whPrd || ! $rejSew) {
            return $this->report([
                'status' => 'BLOCKED',
                'reason' => 'Butuh pickup line aktif dengan qty minimal 2 dan warehouse WIP-SEW, WH-PRD, REJ-SEW.',
            ], self::FAILURE);
        }

        $return = DB::transaction(function () use ($pickupLine, $wipSew, $whPrd, $rejSew, $inventory, $journal, $code): SewingReturn {
            $date = $pickupLine->sewingPickup?->date ?: now()->toDateString();
            $itemId = (int) $pickupLine->finished_item_id;
            $bundleId = (int) $pickupLine->cutting_job_bundle_id;
            $qty = 1.0;
            $unitCost = 100.0;

            $return = SewingReturn::create([
                'code' => $code,
                'date' => $date,
                'warehouse_id' => (int) $wipSew->id,
                'destination_warehouse_id' => (int) $whPrd->id,
                'operator_id' => $pickupLine->sewingPickup?->operator_id,
                'status' => 'posted',
                'notes' => 'Dummy pilot Sewing Return OK + reject; jangan dipakai transaksi riil.',
                'pickup_id' => $pickupLine->sewing_pickup_id,
            ]);

            SewingReturnLine::create([
                'sewing_return_id' => $return->id,
                'sewing_pickup_line_id' => $pickupLine->id,
                'qty_ok' => $qty,
                'qty_reject' => $qty,
                'notes' => 'Dummy OK 1 pcs dan reject 1 pcs.',
            ]);

            $pickupLine->forceFill([
                'qty_returned_ok' => (float) ($pickupLine->qty_returned_ok ?? 0) + $qty,
                'qty_returned_reject' => (float) ($pickupLine->qty_returned_reject ?? 0) + $qty,
                'status' => 'done',
            ])->save();

            QcResult::create([
                'stage' => QcResult::STAGE_SEWING,
                'cutting_job_bundle_id' => $bundleId,
                'sewing_job_id' => $return->id,
                'qc_date' => $date,
                'qty_ok' => $qty,
                'qty_reject' => $qty,
                'status' => 'mixed',
                'operator_id' => $pickupLine->sewingPickup?->operator_id,
                'notes' => 'Dummy pilot Sewing Return.',
            ]);

            $inventory->stockOut(
                warehouseId: (int) $wipSew->id,
                itemId: $itemId,
                qty: 2,
                date: $date,
                sourceType: 'sewing_qc_out',
                sourceId: (int) $return->id,
                notes: 'Dummy Sewing Return QC OUT',
                allowNegative: false,
                lotId: null,
                unitCostOverride: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: $bundleId,
            );
            $inventory->stockIn(
                warehouseId: (int) $whPrd->id,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: 'sewing_qc_in',
                sourceId: (int) $return->id,
                notes: 'Dummy Sewing Return OK',
                lotId: null,
                unitCost: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: $bundleId,
            );
            $inventory->stockIn(
                warehouseId: (int) $rejSew->id,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: 'sewing_qc_reject',
                sourceId: (int) $return->id,
                notes: 'Dummy Sewing Return reject',
                lotId: null,
                unitCost: $unitCost,
                affectLotCost: false,
                cuttingJobBundleId: $bundleId,
            );

            $journal->postSewingReturnOk($return);
            $journal->postSewingReturnReject($return);

            return $return->fresh();
        });

        return $this->report([
            'status' => 'CREATED',
            'return' => [
                'id' => (int) $return->id,
                'code' => $return->code,
                'date' => (string) $return->date,
                'status' => $return->status,
            ],
            'pickup_line_id' => (int) $pickupLine->id,
            'quantities' => ['ok' => 1, 'reject' => 1, 'total' => 2],
            'message' => 'Dummy Sewing Return OK/reject berhasil dibuat; mirror akuntansi baru belum dijalankan.',
        ]);
    }

    /** @param array<string, mixed> $payload */
    private function report(array $payload, int $status = self::SUCCESS): int
    {
        $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return $status;
    }
}
