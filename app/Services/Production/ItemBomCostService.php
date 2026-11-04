<?php

namespace App\Services\Production;

use App\Models\ItemBom;

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
                'unit_cost' => $line->componentUnitCost(),
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
            'total' => round((float) $includedComponents->sum('estimated_cost'), 2),
            'components' => $components->all(),
        ];
    }

    public function total(ItemBom $bom, bool $includeOptional = false): float
    {
        return (float) $this->estimate($bom, $includeOptional)['total'];
    }
}
