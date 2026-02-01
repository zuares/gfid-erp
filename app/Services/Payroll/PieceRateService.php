<?php

namespace App\Services\Payroll;

use App\Models\Item;
use App\Models\PieceRate;
use Carbon\Carbon;

class PieceRateService
{
    /**
     * Return rate per pcs berdasarkan:
     * module + employee + item (+ category fallback) + effective date
     *
     * PRIORITAS:
     * 1) employee + item_id
     * 2) employee + item_category_id (item_id null)
     * 3) employee default (item_id null & item_category_id null)
     */
    public function getRatePerPcs(
        string $module,
        int $employeeId,
        int $itemId,
        string | \DateTimeInterface  | null $date = null
    ): float {
        $module = strtolower(trim($module));
        if (!in_array($module, ['cutting', 'sewing'], true)) {
            return 0.0;
        }

        if ($employeeId <= 0 || $itemId <= 0) {
            return 0.0;
        }

        $day = $this->toDateString($date);

        $item = Item::whereKey($itemId)->first(['id', 'item_category_id']);
        $catId = (int) ($item?->item_category_id ?? 0);

        $base = PieceRate::query()
            ->where('module', $module)
            ->where('employee_id', $employeeId)
            ->whereDate('effective_from', '<=', $day)
            ->where(function ($q) use ($day) {
                $q->whereNull('effective_to')
                    ->orWhereDate('effective_to', '>=', $day);
            });

        // 1) operator + item
        $rate = (float) (clone $base)
            ->where('item_id', $itemId)
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('rate_per_pcs');

        if ($rate > 0) {
            return round($rate, 2);
        }

        // 2) operator + category
        if ($catId > 0) {
            $rate = (float) (clone $base)
                ->whereNull('item_id')
                ->where('item_category_id', $catId)
                ->orderByDesc('effective_from')
                ->orderByDesc('id')
                ->value('rate_per_pcs');

            if ($rate > 0) {
                return round($rate, 2);
            }

        }

        // 3) operator default
        $rate = (float) (clone $base)
            ->whereNull('item_id')
            ->whereNull('item_category_id')
            ->orderByDesc('effective_from')
            ->orderByDesc('id')
            ->value('rate_per_pcs');

        return $rate > 0 ? round($rate, 2) : 0.0;
    }

    /**
     * Strict mode: kalau rate 0, throw error (biar disiplin).
     */
    public function requireRatePerPcs(
        string $module,
        int $employeeId,
        int $itemId,
        string | \DateTimeInterface  | null $date = null
    ): float {
        $rate = $this->getRatePerPcs($module, $employeeId, $itemId, $date);

        if ($rate <= 0) {
            throw new \RuntimeException("PieceRate {$module} belum diset untuk employee {$employeeId} item {$itemId} (date {$this->toDateString($date)}).");
        }

        return $rate;
    }

    private function toDateString(string | \DateTimeInterface  | null $date): string
    {
        if ($date instanceof \DateTimeInterface) {
            return Carbon::instance($date)->toDateString();
        }
        if (is_string($date) && trim($date) !== '') {
            return Carbon::parse($date)->toDateString();
        }
        return now()->toDateString();
    }
}
