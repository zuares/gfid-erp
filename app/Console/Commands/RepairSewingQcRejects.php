<?php

namespace App\Console\Commands;

use App\Models\SewingPickup;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RepairSewingQcRejects extends Command
{
    protected $signature = 'production:repair-sewing-qc-rejects
                            {--apply : Simpan perubahan. Tanpa opsi ini hanya preview}
                            {--return= : Batasi ke sewing_return_id tertentu}
                            {--force : Izinkan apply saat APP_DB_MODE bukan dev}';

    protected $description = 'Sinkronkan hasil QC jahit reject ke sewing_return_lines dan counter pickup.';

    public function handle(): int
    {
        $apply = (bool) $this->option('apply');
        $returnId = $this->option('return') ? (int) $this->option('return') : null;
        $dbMode = strtolower((string) env('APP_DB_MODE', ''));

        if ($apply && $dbMode !== 'dev' && ! $this->option('force')) {
            $this->error('APP_DB_MODE bukan dev. Tambahkan --force kalau memang ingin apply di DB aktif ini.');
            return self::FAILURE;
        }

        $rows = $this->mismatchedRows($returnId);

        if ($rows->isEmpty()) {
            $this->info('Tidak ada data QC jahit reject yang perlu diperbaiki.');
            return self::SUCCESS;
        }

        $this->warn($apply ? '[APPLY] Data akan diperbaiki.' : '[PREVIEW] Tidak ada data yang disimpan.');
        $this->table(
            ['QC', 'Return', 'Line', 'Pickup Line', 'QC OK', 'QC Reject', 'Line OK', 'Line Reject', 'Pickup OK', 'Pickup RJ'],
            $rows->map(fn($row) => [
                $row->qc_id,
                $row->sewing_job_id,
                $row->line_id,
                $row->pickup_line_id,
                $row->qc_ok,
                $row->qc_reject,
                $row->line_ok,
                $row->line_reject,
                $row->qty_returned_ok,
                $row->qty_returned_reject,
            ])->all()
        );

        if (! $apply) {
            $this->line('Jalankan dengan --apply untuk menyimpan perubahan.');
            return self::SUCCESS;
        }

        DB::transaction(function () use ($rows) {
            $touchedPickupIds = [];

            foreach ($rows as $row) {
                $line = DB::table('sewing_return_lines')
                    ->where('id', (int) $row->line_id)
                    ->lockForUpdate()
                    ->first();

                $pickupLine = DB::table('sewing_pickup_lines')
                    ->where('id', (int) $row->pickup_line_id)
                    ->lockForUpdate()
                    ->first();

                if (! $line || ! $pickupLine) {
                    continue;
                }

                $qcOk = (float) $row->qc_ok;
                $qcReject = (float) $row->qc_reject;
                $oldLineOk = (float) ($line->qty_ok ?? 0);
                $oldLineReject = (float) ($line->qty_reject ?? 0);
                $oldFinishedQty = (float) ($line->finished_qty ?? 0);

                DB::table('sewing_return_lines')
                    ->where('id', (int) $line->id)
                    ->update([
                        'qty_ok' => $qcOk,
                        'qty_reject' => $qcReject,
                        'finished_qty' => min($oldFinishedQty, $qcOk),
                        'updated_at' => now(),
                    ]);

                DB::table('sewing_pickup_lines')
                    ->where('id', (int) $pickupLine->id)
                    ->update([
                        'qty_returned_ok' => max($qcOk, 0),
                        'qty_returned_reject' => max($qcReject, 0),
                        'updated_at' => now(),
                    ]);

                if ($pickupLine->sewing_pickup_id) {
                    $touchedPickupIds[(int) $pickupLine->sewing_pickup_id] = true;
                }
            }

            foreach (array_keys($touchedPickupIds) as $pickupId) {
                $pickup = SewingPickup::with('lines')->lockForUpdate()->find($pickupId);
                if ($pickup && $pickup->isFillable('status')) {
                    $pickup->status = $pickup->recalcStatus();
                    $pickup->save();
                }
            }
        });

        $remaining = $this->mismatchedRows($returnId)->count();
        $this->info("Repair selesai. Sisa mismatch: {$remaining}.");

        return $remaining === 0 ? self::SUCCESS : self::FAILURE;
    }

    private function mismatchedRows(?int $returnId)
    {
        return DB::table('qc_results as q')
            ->join('sewing_return_lines as rl', 'rl.sewing_return_id', '=', 'q.sewing_job_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->where('q.stage', 'sewing')
            ->where('q.qty_reject', '>', 0)
            ->whereColumn('pl.cutting_job_bundle_id', 'q.cutting_job_bundle_id')
            ->when($returnId, fn($query) => $query->where('q.sewing_job_id', $returnId))
            ->where(function ($query) {
                $query->whereRaw('ABS(COALESCE(rl.qty_ok,0) - COALESCE(q.qty_ok,0)) > 0.000001')
                    ->orWhereRaw('ABS(COALESCE(rl.qty_reject,0) - COALESCE(q.qty_reject,0)) > 0.000001')
                    ->orWhereRaw('ABS(COALESCE(pl.qty_returned_ok,0) - COALESCE(q.qty_ok,0)) > 0.000001')
                    ->orWhereRaw('ABS(COALESCE(pl.qty_returned_reject,0) - COALESCE(q.qty_reject,0)) > 0.000001');
            })
            ->select([
                'q.id as qc_id',
                'q.sewing_job_id',
                'q.cutting_job_bundle_id',
                'q.qty_ok as qc_ok',
                'q.qty_reject as qc_reject',
                'rl.id as line_id',
                'rl.qty_ok as line_ok',
                'rl.qty_reject as line_reject',
                'rl.finished_qty',
                'pl.id as pickup_line_id',
                'pl.sewing_pickup_id',
                'pl.qty_returned_ok',
                'pl.qty_returned_reject',
                'pl.qty_bundle',
            ])
            ->orderBy('q.sewing_job_id')
            ->orderBy('q.id')
            ->get();
    }
}
