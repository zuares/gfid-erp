<?php

namespace App\Services\Production;

use App\Models\ItemBom;
use App\Models\ItemCostSnapshot;

class ItemBomCostService
{
    /**
     * Calculate a one-level BOM cost estimate.
     *
     * By default, all required lines and the optional line with the highest
     * estimated cost are included. Set $includeOptional to true when a screen
     * needs the full theoretical BOM cost with every optional line.
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
                'scrap_pct' => (float) ($line->scrap_pct ?? 0),
                'is_optional' => (bool) $line->is_optional,
                'included_in_estimate' => $included,
                'stock_unit' => (string) ($material?->stockUnit() ?? $line->uom ?? 'pcs'),
                'purchase_unit' => (string) ($material?->purchaseUnit() ?? $material?->stockUnit() ?? $line->uom ?? 'pcs'),
                'purchase_conversion_factor' => $purchaseFactor,
                'unit_cost' => $unitCost,
                'purchase_unit_cost' => round($unitCost * $purchaseFactor, 2),
                'estimated_cost' => $estimatedCost,
            ];
        })->values();

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
                    $component['included_in_estimate'] = ! $component['is_optional']
                        || $component['line_id'] === $selectedOptionalLineId;

                    return $component;
                });
            }
        }

        $includedComponents = $components->filter(
            fn (array $component): bool => $component['included_in_estimate']
        );
        $materialTotal = round((float) $includedComponents->sum('estimated_cost'), 2);
        $productionWage = $this->getProductionWage((int) $bom->item_id);

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
     * Snapshot ini berasal dari payroll cutting, sewing, dan finishing.
     *
     * @return array{cutting: float, sewing: float, finishing: float, total: float, available: bool, snapshot_id: int|null}
     */
    private function getProductionWage(int $itemId): array
    {
        $snapshot = ItemCostSnapshot::query()
            ->where('item_id', $itemId)
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

        $cutting = round((float) ($snapshot?->cutting_unit_cost ?? 0), 4);
        $sewing = round((float) ($snapshot?->sewing_unit_cost ?? 0), 4);
        $finishing = round((float) ($snapshot?->finishing_unit_cost ?? 0), 4);

        return [
            'cutting' => $cutting,
            'sewing' => $sewing,
            'finishing' => $finishing,
            'total' => round($cutting + $sewing + $finishing, 2),
            'available' => $snapshot !== null,
            'snapshot_id' => $snapshot?->id ? (int) $snapshot->id : null,
        ];
    }

    public function total(ItemBom $bom, bool $includeOptional = false): float
    {
        return (float) $this->estimate($bom, $includeOptional)['total'];
    }
}
