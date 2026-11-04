<?php

namespace App\Services\Production;

use App\Models\ItemBom;
use App\Models\ItemCostSnapshot;
use App\Models\PieceRate;

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
     * @return array{cutting: float, sewing: float, finishing: float, total: float, available: bool, snapshot_id: int|null, source: string, is_estimate: bool}
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

        $cutting = round((float) ($snapshot?->cutting_unit_cost ?? 0), 4);
        $sewing = round((float) ($snapshot?->sewing_unit_cost ?? 0), 4);
        $finishing = round((float) ($snapshot?->finishing_unit_cost ?? 0), 4);

        if ($snapshot) {
            return [
                'cutting' => $cutting,
                'sewing' => $sewing,
                'finishing' => $finishing,
                'total' => round($cutting + $sewing + $finishing, 2),
                'available' => true,
                'snapshot_id' => (int) $snapshot->id,
                'source' => 'payroll_snapshot',
                'is_estimate' => false,
            ];
        }

        // Sebelum snapshot payroll tersedia, gunakan tarif piece rate aktif
        // tertinggi per modul sebagai estimasi konservatif. Tarif tertinggi
        // mencegah HPP BOM terlihat terlalu rendah ketika operator produksi
        // belum ditentukan pada saat BOM dibuat.
        $cuttingEstimate = $this->highestActivePieceRate($item, 'cutting');
        $sewingEstimate = $this->highestActivePieceRate($item, 'sewing');
        $estimatedTotal = round($cuttingEstimate + $sewingEstimate, 2);

        return [
            'cutting' => $cuttingEstimate,
            'sewing' => $sewingEstimate,
            'finishing' => 0.0,
            'total' => $estimatedTotal,
            'available' => $estimatedTotal > 0,
            'snapshot_id' => null,
            'source' => $estimatedTotal > 0 ? 'piece_rate_estimate' : 'unavailable',
            'is_estimate' => true,
        ];
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
