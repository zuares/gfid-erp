<?php

namespace App\Console\Commands;

use App\Models\CuttingBundleCorrection;
use App\Services\Production\QcService;
use Illuminate\Console\Command;

class RepairCuttingBundleCorrectionWip extends Command
{
    protected $signature = 'production:repair-cutting-correction-wip
                            {correctionId : ID correction bundle yang akan diperbaiki}
                            {--apply : Terapkan reversal audit}
                            {--confirm= : Wajib REPAIR-CUTTING-CORRECTION saat apply}
                            {--json : Keluarkan JSON}';

    protected $description = 'Reversal audit saldo WIP-CUT yang tertinggal dari koreksi bundle.';

    public function handle(QcService $qc): int
    {
        $id = (int) $this->argument('correctionId');
        $correction = CuttingBundleCorrection::query()->with('bundle')->find($id);
        if (! $correction) {
            return $this->outputResult(['status' => 'NOT_FOUND', 'correction_id' => $id], self::FAILURE);
        }

        $bundle = $correction->bundle;
        $residualQty = (float) \DB::table('inventory_mutations')
            ->where('cutting_job_bundle_id', $bundle->id)
            ->where(function ($query) use ($correction): void {
                $query
                    ->where(function ($original) use ($correction): void {
                        $original
                            ->whereIn('source_type', ['cutting_wip', 'cutting_reject', 'cutting_qc_adjust_in', 'cutting_qc_adjust_out'])
                            ->where('source_id', $correction->cutting_job_id);
                    })
                    ->orWhere('source_type', 'cutting_bundle_correction_wip_void')
                    ->orWhere('source_type', 'cutting_qc_bundle_void');
            })
            ->sum('qty_change');
        $alreadyRepaired = ! empty(($correction->metadata ?? [])['wip_reversal_mutation_ids']);
        $wageAlreadyRepaired = array_key_exists('wage_reversal_status', $correction->metadata ?? []);

        $preview = [
            'status' => ($alreadyRepaired && $wageAlreadyRepaired)
                ? 'ALREADY_REPAIRED'
                : (($residualQty > 0.0001 || ! $wageAlreadyRepaired) ? 'REPAIR_REQUIRED' : 'CLEAN'),
            'correction_id' => $id,
            'bundle_id' => (int) $bundle->id,
            'bundle_code' => $bundle->bundle_code,
            'residual_wip_qty' => round($residualQty, 4),
            'apply' => (bool) $this->option('apply'),
        ];

        if (! $this->option('apply') || ($alreadyRepaired && $wageAlreadyRepaired)) {
            return $this->outputResult($preview, self::SUCCESS);
        }

        if ($this->option('confirm') !== 'REPAIR-CUTTING-CORRECTION') {
            return $this->outputResult([
                ...$preview,
                'status' => 'BLOCKED',
                'reason' => 'Gunakan --confirm=REPAIR-CUTTING-CORRECTION.',
            ], self::FAILURE);
        }

        $repaired = $qc->repairCuttingBundleCorrectionWip($correction, auth()->id());

        return $this->outputResult([
            ...$preview,
            'status' => 'REPAIRED',
            'metadata' => $repaired->metadata,
        ], self::SUCCESS);
    }

    /** @param array<string, mixed> $payload */
    private function outputResult(array $payload, int $exitCode): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($payload, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
        } else {
            $this->table(['Field', 'Value'], collect($payload)->map(fn ($value, $key) => [$key, is_scalar($value) || $value === null ? $value : json_encode($value)])->values()->all());
        }

        return $exitCode;
    }
}
