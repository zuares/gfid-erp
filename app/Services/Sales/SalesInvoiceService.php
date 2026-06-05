<?php

namespace App\Services\Sales;

use App\Models\Item;
use Illuminate\Support\Collection;

/**
 * Business logic perhitungan baris & total Sales Invoice.
 * Satu sumber rumus HPP/margin/total supaya store() & update() tidak menduplikasi logika.
 */
class SalesInvoiceService
{
    /**
     * Preload HPP master untuk item-item pada baris invoice. [item_id => hpp]
     *
     * @param array<int,array<string,mixed>> $items
     */
    public function hppMap(array $items): Collection
    {
        $itemIds = collect($items)
            ->pluck('item_id')
            ->map(fn ($x) => (int) $x)
            ->unique()
            ->values();

        return Item::query()
            ->whereIn('id', $itemIds->all())
            ->pluck('hpp', 'id');
    }

    /**
     * True kalau ada minimal satu baris dengan unit_price > 0.
     *
     * @param array<int,array<string,mixed>> $items
     */
    public function hasAnyPrice(array $items): bool
    {
        return collect($items)->contains(function ($row) {
            $v = $row['unit_price'] ?? null;
            return $v !== null && $v !== '' && (float) $v > 0;
        });
    }

    /**
     * Status awal invoice berdasarkan ada/tidaknya harga.
     *
     * @param array<int,array<string,mixed>> $items
     */
    public function statusFromPricing(array $items): string
    {
        return $this->hasAnyPrice($items) ? 'draft' : 'unpriced';
    }

    /**
     * Bangun atribut baris invoice + subtotal dari data tervalidasi.
     *
     * @param array<int,array<string,mixed>> $items
     * @return array{lines: array<int,array<string,mixed>>, subtotal: float}
     */
    public function buildLines(array $items, Collection $hppMap): array
    {
        $lines = [];
        $subtotal = 0.0;

        foreach ($items as $row) {
            $itemId = (int) $row['item_id'];
            $qty = (float) $row['qty'];

            $unitPriceRaw = $row['unit_price'] ?? null;
            $unitPrice = ($unitPriceRaw !== null && $unitPriceRaw !== '') ? (float) $unitPriceRaw : 0.0;

            $lineDiscount = (float) ($row['line_discount'] ?? 0.0);

            $lineTotal = max(0, ($qty * $unitPrice) - $lineDiscount);
            $subtotal += $lineTotal;

            $hppUnit = (float) ($hppMap[$itemId] ?? 0.0);
            $hppTotal = $hppUnit * $qty;

            $marginTotal = $lineTotal - $hppTotal;
            $marginUnit = $qty > 0 ? ($marginTotal / $qty) : 0.0;

            $lines[] = [
                'item_id' => $itemId,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'line_discount' => $lineDiscount,
                'line_total' => $lineTotal,

                'hpp_unit_snapshot' => $hppUnit,
                'hpp_total_snapshot' => $hppTotal,

                'margin_unit' => $marginUnit,
                'margin_total' => $marginTotal,
            ];
        }

        return ['lines' => $lines, 'subtotal' => $subtotal];
    }

    /**
     * Hitung total header invoice (subtotal, diskon, pajak, grand total).
     *
     * @return array{subtotal: float, discount_total: float, tax_amount: float, grand_total: float}
     */
    public function computeTotals(float $subtotal, float $headerDiscount, float $taxPercent): array
    {
        $discountTotal = min($headerDiscount, $subtotal);
        $dpp = $subtotal - $discountTotal;

        $taxAmount = $taxPercent > 0 ? round($dpp * $taxPercent / 100, 2) : 0.0;
        $grandTotal = $dpp + $taxAmount;

        return [
            'subtotal' => $subtotal,
            'discount_total' => $discountTotal,
            'tax_amount' => $taxAmount,
            'grand_total' => $grandTotal,
        ];
    }
}
