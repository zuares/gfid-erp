<?php

namespace App\Services\Costing;

use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\ItemCostSnapshot;
use App\Models\SewingPickupLine;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * Rebuild finished-good HPP from the legacy production and inventory flow.
 *
 * This service deliberately does not read the standalone accounting ledger.
 * It uses the final posted finishing output as the total unit-cost boundary,
 * then explains that value with legacy production components where available.
 */
class LegacyHppRebuildService
{
    private const EPSILON = 0.000001;

    public function __construct(
        protected HppService $hpp,
    ) {}

    public function rebuild(
        int $itemId,
        string $dateFrom,
        string $dateTo,
        ?int $warehouseId = null,
        ?string $notes = null,
    ): ItemCostSnapshot {
        $dateFrom = Carbon::parse($dateFrom)->toDateString();
        $dateTo = Carbon::parse($dateTo)->toDateString();

        $lines = FinishingJobLine::query()
            ->where('item_id', $itemId)
            ->where('qty_ok', '>', 0)
            ->whereHas('job', function ($query) use ($dateFrom, $dateTo): void {
                $query
                    ->where('status', 'posted')
                    ->whereDate('date', '>=', $dateFrom)
                    ->whereDate('date', '<=', $dateTo);
            })
            ->with([
                'job',
                'bundle.cuttingJob.lot.item',
            ])
            ->orderBy('finishing_job_id')
            ->orderBy('id')
            ->get();

        if ($lines->isEmpty()) {
            throw ValidationException::withMessages([
                'item_id' => 'Belum ada Finishing Job posted dengan qty OK untuk item dan periode ini.',
            ]);
        }

        $totalFinalQty = 0.0;
        $totalFinalCost = 0.0;
        $componentTotals = [
            'rm' => 0.0,
            'cutting' => 0.0,
            'sewing' => 0.0,
            'packaging' => 0.0,
        ];
        $sourceCodes = [];
        $warnings = [];
        $latestReferenceId = null;

        foreach ($lines as $line) {
            $output = $this->latestPostedOutput($line);
            if ($output === null || $output['qty'] <= self::EPSILON) {
                $warnings[] = "Output inventory untuk finishing line #{$line->id} tidak ditemukan.";

                continue;
            }

            $qty = (float) $output['qty'];
            $totalFinalQty += $qty;
            $totalFinalCost += (float) $output['total_cost'];
            $latestReferenceId = (int) $line->finishing_job_id;
            $sourceCodes[] = (string) ($line->job?->code ?? "FINISHING-{$line->finishing_job_id}");

            $components = $this->componentsForLine($line, $qty);
            foreach ($componentTotals as $key => $_value) {
                $componentTotals[$key] += $components[$key] * $qty;
            }

            foreach ($components['warnings'] as $warning) {
                $warnings[] = "Line #{$line->id}: {$warning}";
            }
        }

        if ($totalFinalQty <= self::EPSILON || $totalFinalCost <= self::EPSILON) {
            throw ValidationException::withMessages([
                'item_id' => 'Output barang jadi posted belum memiliki nilai cost inventory yang valid.',
            ]);
        }

        $finalUnitCost = $totalFinalCost / $totalFinalQty;
        $rmUnitCost = $componentTotals['rm'] / $totalFinalQty;
        $cuttingUnitCost = $componentTotals['cutting'] / $totalFinalQty;
        $sewingUnitCost = $componentTotals['sewing'] / $totalFinalQty;
        $packagingUnitCost = $componentTotals['packaging'] / $totalFinalQty;
        $knownUnitCost = $rmUnitCost + $cuttingUnitCost + $sewingUnitCost + $packagingUnitCost;
        $finishingUnitCost = $finalUnitCost - $knownUnitCost;

        if ($finishingUnitCost < -0.01) {
            throw ValidationException::withMessages([
                'item_id' => sprintf(
                    'Komponen legacy melebihi cost barang jadi sebesar Rp%s/unit. Periksa bundle atau reversal sebelum generate ulang.',
                    number_format(abs($finishingUnitCost), 2, ',', '.')
                ),
            ]);
        }

        $finishingUnitCost = max(0.0, $finishingUnitCost);
        $message = implode(', ', array_values(array_unique($sourceCodes)));
        $snapshotNotes = trim(implode(' | ', array_filter([
            $notes,
            "Legacy HPP rebuild dari produksi: {$message}",
            'Tidak membaca standalone accounting; snapshot dibuat nonaktif untuk review.',
            $warnings !== [] ? 'Warning: '.implode(' ', array_values(array_unique($warnings))) : null,
        ])));

        return $this->hpp->createSnapshot([
            'item_id' => $itemId,
            'warehouse_id' => $warehouseId,
            'snapshot_date' => $dateTo,
            'reference_type' => 'legacy_hpp_rebuild',
            'reference_id' => $latestReferenceId,
            'qty_basis' => $totalFinalQty,
            'rm_unit_cost' => $rmUnitCost,
            'cutting_unit_cost' => $cuttingUnitCost,
            'sewing_unit_cost' => $sewingUnitCost,
            'finishing_unit_cost' => $finishingUnitCost,
            'packaging_unit_cost' => $packagingUnitCost,
            'overhead_unit_cost' => 0.0,
            'notes' => $snapshotNotes,
            'is_active' => false,
        ]);
    }

    /** @return array{qty: float, total_cost: float, created_at: string|null} */
    protected function latestPostedOutput(FinishingJobLine $line): ?array
    {
        $rows = DB::table('inventory_mutations')
            ->where('source_type', FinishingJob::class)
            ->where('source_id', $line->finishing_job_id)
            ->where('item_id', $line->item_id)
            ->where('qty_change', '>', 0)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['qty_change', 'unit_cost', 'total_cost', 'created_at']);

        if ($rows->isEmpty()) {
            return null;
        }

        // A void/repost keeps the same source_type/source_id. The latest
        // positive mutation batch is the current legacy posting.
        $latestCreatedAt = (string) $rows->first()->created_at;
        $batch = $rows->filter(fn ($row): bool => (string) $row->created_at === $latestCreatedAt);

        return [
            'qty' => (float) $batch->sum(fn ($row): float => (float) $row->qty_change),
            'total_cost' => (float) $batch->sum(function ($row): float {
                return $row->total_cost !== null
                    ? (float) $row->total_cost
                    : (float) $row->qty_change * (float) ($row->unit_cost ?? 0);
            }),
            'created_at' => $latestCreatedAt,
        ];
    }

    /** @return array{rm: float, cutting: float, sewing: float, packaging: float, warnings: array<int, string>} */
    protected function componentsForLine(FinishingJobLine $line, float $qty): array
    {
        $warnings = [];
        $bundle = $line->bundle;

        if (! $bundle) {
            return [
                'rm' => 0.0,
                'cutting' => 0.0,
                'sewing' => 0.0,
                'packaging' => $this->latestPackagingCostPerUnit($line, $qty),
                'warnings' => ['bundle cutting tidak ditemukan; RM/cutting tidak dapat dipecah.'],
            ];
        }

        $bundleQty = max((float) ($bundle->qty_pcs ?? 0), 0.0);
        $ratio = $bundleQty > self::EPSILON
            ? min(max($qty / $bundleQty, 0.0), 1.0)
            : 0.0;
        $lotUnitCost = (float) ($bundle->cuttingJob?->lot?->avg_cost
            ?: ($bundle->cuttingJob?->lot?->item?->effective_unit_cost ?? 0));
        $rawTotal = (float) ($bundle->qty_used_fabric ?? 0) * $lotUnitCost * $ratio;
        $rawUnit = $qty > self::EPSILON ? $rawTotal / $qty : 0.0;

        $wipCut = DB::table('inventory_mutations')
            ->where('cutting_job_bundle_id', $bundle->id)
            ->whereIn('source_type', ['cutting_wip', 'cutting_qc_adjust_in'])
            ->where('direction', 'in')
            ->where('qty_change', '>', 0)
            ->selectRaw('SUM(qty_change) as qty, SUM(COALESCE(total_cost, qty_change * unit_cost)) as total_cost')
            ->first();
        $wipCutUnit = ((float) ($wipCut?->qty ?? 0)) > self::EPSILON
            ? (float) $wipCut->total_cost / (float) $wipCut->qty
            : 0.0;
        $cuttingUnit = max(0.0, $wipCutUnit - $rawUnit);

        if ($lotUnitCost <= self::EPSILON) {
            $warnings[] = 'cost lot bahan baku kosong.';
        }
        if ($wipCutUnit <= self::EPSILON) {
            $warnings[] = 'cost WIP-CUT tidak ditemukan.';
        }

        $sewingLines = SewingPickupLine::query()
            ->where('cutting_job_bundle_id', $bundle->id)
            ->whereNull('voided_at')
            ->where('status', '!=', 'void')
            ->get(['qty_bundle', 'wage_per_pcs']);
        $sewingQty = (float) $sewingLines->sum('qty_bundle');
        $sewingWage = (float) $sewingLines->sum(
            fn ($row): float => (float) $row->qty_bundle * (float) $row->wage_per_pcs
        );
        $sewingUnit = $sewingQty > self::EPSILON ? $sewingWage / $sewingQty : 0.0;
        if ($sewingUnit <= self::EPSILON) {
            $warnings[] = 'upah sewing per pcs tidak ditemukan.';
        }

        return [
            // WIP-CUT dipecah menjadi bahan RM dan upah/proses Cutting.
            // Komponen Cutting tidak digabung dengan upah Sewing.
            'rm' => max(0.0, $rawUnit),
            'cutting' => $cuttingUnit,
            'sewing' => max(0.0, $sewingUnit),
            'packaging' => $this->latestPackagingCostPerUnit($line, $qty),
            'warnings' => $warnings,
        ];
    }

    protected function latestPackagingCostPerUnit(FinishingJobLine $line, float $qty): float
    {
        if ($qty <= self::EPSILON) {
            return 0.0;
        }

        $rows = DB::table('inventory_mutations')
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $line->id)
            ->where('qty_change', '<', 0)
            ->orderByDesc('created_at')
            ->orderByDesc('id')
            ->get(['qty_change', 'unit_cost', 'total_cost', 'created_at']);

        if ($rows->isEmpty()) {
            return 0.0;
        }

        // Keep only the latest BOM batch, so a voided pilot BOM is not carried
        // into the rebuilt snapshot.
        $latestCreatedAt = (string) $rows->first()->created_at;
        $batch = $rows->filter(fn ($row): bool => (string) $row->created_at === $latestCreatedAt);
        $total = abs((float) $batch->sum(function ($row): float {
            return $row->total_cost !== null
                ? (float) $row->total_cost
                : (float) $row->qty_change * (float) ($row->unit_cost ?? 0);
        }));

        return $total / $qty;
    }
}
