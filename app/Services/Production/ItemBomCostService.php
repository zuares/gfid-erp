<?php

namespace App\Services\Production;

use App\Models\ItemBom;
use App\Models\ItemBomLine;
use App\Models\ItemCostSnapshot;
use App\Models\PieceRate;

class ItemBomCostService
{
    /**
     * Calculate a one-level BOM cost estimate.
     *
     * By default, the highest-cost main-material line, all other required
     * lines, and the optional line with the highest estimated cost are
     * included. Main-material lines are treated as alternatives for the
     * temporary BOM estimate; production consumption remains unchanged.
     * Set $includeOptional to true when a screen needs the full theoretical
     * BOM cost with every optional line.
     * This method is an estimate only; it does not update item HPP or history.
     */
    public function estimate(ItemBom $bom, bool $includeOptional = false): array
    {
        $bom->loadMissing(['item', 'lines.material']);

        $components = $bom->lines->map(function ($line) use ($includeOptional): array {
            $material = $line->material;
            $included = $includeOptional || ! $line->is_optional;
            $unitCost = $line->componentUnitCost();
            $purchaseFactor = $material?->purchaseConversionFactor() ?? 1.0;
            $estimatedCost = $line->estimatedCost();

            return [
                'line_id' => (int) $line->id,
                'material_item_id' => (int) $line->material_item_id,
                'code' => (string) ($material?->code ?? ''),
                'name' => (string) ($material?->name ?? ''),
                'qty' => (float) $line->qty,
                'qty_with_scrap' => $line->qtyWithScrap(),
                'uom' => (string) ($line->uom ?: ($material?->stockUnit() ?? 'pcs')),
                'usage_stage' => (string) ($line->usage_stage ?: ItemBomLine::STAGE_MAIN_MATERIAL),
                'scrap_pct' => (float) ($line->scrap_pct ?? 0),
                'is_optional' => (bool) $line->is_optional,
                'included_in_estimate' => $included,
                'stock_unit' => (string) ($material?->stockUnit() ?? $line->uom ?? 'pcs'),
                'purchase_unit' => (string) ($material?->purchaseUnit() ?? $material?->stockUnit() ?? $line->uom ?? 'pcs'),
                'purchase_conversion_factor' => $purchaseFactor,
                'last_purchase_price' => (float) ($material?->last_purchase_price ?? 0),
                'unit_cost' => $unitCost,
                'purchase_unit_cost' => round($unitCost * $purchaseFactor, 2),
                'estimated_cost' => $estimatedCost,
            ];
        })->values();

        $selectedMainMaterialLineId = null;
        $mainMaterialComponents = $components->filter(
            fn (array $component): bool => $component['usage_stage'] === ItemBomLine::STAGE_MAIN_MATERIAL
                && ! $component['is_optional']
        );

        if ($mainMaterialComponents->count() > 1) {
            $selectedMainMaterial = $mainMaterialComponents->reduce(function (?array $selected, array $component): array {
                if ($selected === null || $component['estimated_cost'] > $selected['estimated_cost']) {
                    return $component;
                }

                return $selected;
            });

            $selectedMainMaterialLineId = $selectedMainMaterial['line_id'] ?? null;

            if ($selectedMainMaterialLineId !== null) {
                $components = $components->map(function (array $component) use ($selectedMainMaterialLineId): array {
                    if ($component['usage_stage'] === ItemBomLine::STAGE_MAIN_MATERIAL && ! $component['is_optional']) {
                        $component['included_in_estimate'] = $component['line_id'] === $selectedMainMaterialLineId;
                    }

                    return $component;
                });
            }
        }

        $selectedOptionalLineId = null;

        if (! $includeOptional) {
            $selectedOptional = $components
                ->where('is_optional', true)
                ->reduce(function (?array $selected, array $component): array {
                    if ($selected === null || $component['estimated_cost'] > $selected['estimated_cost']) {
                        return $component;
                    }

                    return $selected;
                });

            $selectedOptionalLineId = $selectedOptional['line_id'] ?? null;

            if ($selectedOptionalLineId !== null) {
                $components = $components->map(function (array $component) use ($selectedOptionalLineId): array {
                    if ($component['is_optional']) {
                        $component['included_in_estimate'] = $component['line_id'] === $selectedOptionalLineId;
                    }

                    return $component;
                });
            }
        }

        $includedComponents = $components->filter(
            fn (array $component): bool => $component['included_in_estimate']
        );
        $materialTotal = round((float) $includedComponents->sum('estimated_cost'), 2);
        $productionWage = $this->getProductionWage($bom->item);

        return [
            'bom_id' => (int) $bom->id,
            'item_id' => (int) $bom->item_id,
            'item_code' => (string) ($bom->item?->code ?? ''),
            'item_name' => (string) ($bom->item?->name ?? ''),
            'item_unit' => (string) ($bom->item?->stockUnit() ?? $bom->item?->unit ?? 'pcs'),
            'line_count' => $components->count(),
            'required_line_count' => $components->where('is_optional', false)->count(),
            'optional_line_count' => $components->where('is_optional', true)->count(),
            'include_optional' => $includeOptional,
            'included_optional_line_count' => $includedComponents->where('is_optional', true)->count(),
            'main_material_line_count' => $mainMaterialComponents->count(),
            'included_main_material_line_count' => $includedComponents
                ->where('usage_stage', ItemBomLine::STAGE_MAIN_MATERIAL)
                ->where('is_optional', false)
                ->count(),
            'selected_main_material_line_id' => $selectedMainMaterialLineId,
            'selected_optional_line_id' => $selectedOptionalLineId,
            'material_total' => $materialTotal,
            'production_wage' => $productionWage,
            'production_wage_total' => $productionWage['total'],
            'total' => round($materialTotal + $productionWage['total'], 2),
            'components' => $components->all(),
        ];
    }

    /**
     * Ambil upah produksi per pcs dari snapshot HPP produksi aktif.
     * Jika snapshot belum tersedia, gunakan estimasi tarif piece rate aktif.
     *
     * @return array{cutting: float, sewing: float, finishing: float, total: float, available: bool, snapshot_id: int|null, source: string, is_estimate: bool, processes: array<int, array{key: string, label: string, rate: float, source: string, source_label: string}>}
     */
    private function getProductionWage($item): array
    {
        $snapshot = ItemCostSnapshot::query()
            ->where('item_id', (int) $item->id)
            ->whereNull('warehouse_id')
            ->where('is_active', true)
            ->whereIn('reference_type', ['production_cost_period', 'auto_hpp_period'])
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first([
                'id',
                'cutting_unit_cost',
                'sewing_unit_cost',
                'finishing_unit_cost',
            ]);

        $snapshotRates = [
            'cutting' => round((float) ($snapshot?->cutting_unit_cost ?? 0), 4),
            'sewing' => round((float) ($snapshot?->sewing_unit_cost ?? 0), 4),
            'finishing' => round((float) ($snapshot?->finishing_unit_cost ?? 0), 4),
        ];
        $processLabels = [
            'cutting' => 'Cutting',
            'sewing' => 'Sewing',
            'finishing' => 'Finishing',
        ];
        $processes = [];

        foreach ($processLabels as $module => $label) {
            $categoryRate = $this->highestActiveCategoryPieceRate($item, $module);

            if ($categoryRate > 0) {
                $rate = $categoryRate;
                $source = 'piece_rate_category';
                $sourceLabel = 'Kategori item · tarif tertinggi';
            } elseif ($snapshot && $snapshotRates[$module] > 0) {
                $rate = $snapshotRates[$module];
                $source = 'payroll_snapshot';
                $sourceLabel = 'Snapshot payroll aktif';
            } else {
                // Fallback untuk data lama: tarif item atau default global.
                $rate = $this->highestActivePieceRate($item, $module);
                $source = $rate > 0 ? 'piece_rate_estimate' : 'unavailable';
                $sourceLabel = $rate > 0 ? 'Piece rate aktif' : 'Belum ada tarif';
            }

            $processes[] = [
                'key' => $module,
                'label' => $label,
                'rate' => round($rate, 4),
                'source' => $source,
                'source_label' => $sourceLabel,
            ];
        }

        $wageByProcess = collect($processes)->pluck('rate', 'key');
        $estimatedTotal = round((float) $wageByProcess->sum(), 2);
        $hasCategoryRate = collect($processes)->contains(fn (array $process): bool => $process['source'] === 'piece_rate_category');
        $hasEstimateRate = collect($processes)->contains(fn (array $process): bool => $process['source'] === 'piece_rate_estimate');
        $hasSnapshotRate = collect($processes)->contains(fn (array $process): bool => $process['source'] === 'payroll_snapshot');
        $source = $hasCategoryRate
            ? 'piece_rate_category'
            : ($hasEstimateRate ? 'piece_rate_estimate' : ($hasSnapshotRate ? 'payroll_snapshot' : 'unavailable'));

        return [
            'cutting' => (float) ($wageByProcess['cutting'] ?? 0),
            'sewing' => (float) ($wageByProcess['sewing'] ?? 0),
            'finishing' => (float) ($wageByProcess['finishing'] ?? 0),
            'total' => $estimatedTotal,
            'available' => $estimatedTotal > 0,
            'snapshot_id' => $snapshot ? (int) $snapshot->id : null,
            'source' => $source,
            'is_estimate' => $source !== 'payroll_snapshot',
            'processes' => $processes,
        ];
    }

    private function highestActiveCategoryPieceRate($item, string $module): float
    {
        $categoryId = (int) ($item->item_category_id ?? 0);
        if ($categoryId <= 0) {
            return 0.0;
        }

        return round((float) PieceRate::query()
            ->where('module', $module)
            ->whereNull('item_id')
            ->where('item_category_id', $categoryId)
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', now()->toDateString());
            })
            ->max('rate_per_pcs'), 4);
    }

    private function highestActivePieceRate($item, string $module): float
    {
        $query = PieceRate::query()
            ->where('module', $module)
            ->whereDate('effective_from', '<=', now()->toDateString())
            ->where(function ($query): void {
                $query->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', now()->toDateString());
            });

        $rate = (float) (clone $query)
            ->where('item_id', (int) $item->id)
            ->max('rate_per_pcs');

        if ($rate <= 0 && (int) ($item->item_category_id ?? 0) > 0) {
            $rate = (float) (clone $query)
                ->whereNull('item_id')
                ->where('item_category_id', (int) $item->item_category_id)
                ->max('rate_per_pcs');
        }

        if ($rate <= 0) {
            $rate = (float) (clone $query)
                ->whereNull('item_id')
                ->whereNull('item_category_id')
                ->max('rate_per_pcs');
        }

        return round(max(0, $rate), 4);
    }

    public function total(ItemBom $bom, bool $includeOptional = false): float
    {
        return (float) $this->estimate($bom, $includeOptional)['total'];
    }
}
