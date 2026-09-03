<?php

namespace App\Services\Inventory;

use Carbon\CarbonImmutable;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

/**
 * Read-only baseline audit for defective-goods inventory.
 *
 * The audit deliberately compares two independent sources:
 * - inventory_stocks: the current operational balance used by the UI;
 * - inventory_mutations: the stock ledger and its recorded value.
 *
 * A reject-coded item outside a reject warehouse is also included because the
 * current conversion flow stores category-RJCT items in WH-RTS.
 */
class DefectInventoryAuditService
{
    public const DEFECT_WAREHOUSE_CODES = [
        'REJ-CUT',
        'REJ-SEW',
        'REJ-FIN',
        'REJECT',
    ];

    private const VOIDED_JOURNAL_SOURCES = [
        'opening_balance_void',
        'opening_balance_batch_void',
    ];

    /**
     * Build a current-state report without writing anything to the database.
     */
    public function audit(?CarbonImmutable $asOf = null): array
    {
        $asOf ??= CarbonImmutable::now();

        $warehouses = DB::table('warehouses')
            ->whereIn('code', self::DEFECT_WAREHOUSE_CODES)
            ->orderByRaw("CASE code WHEN 'REJ-CUT' THEN 1 WHEN 'REJ-SEW' THEN 2 WHEN 'REJ-FIN' THEN 3 WHEN 'REJECT' THEN 4 ELSE 5 END")
            ->get(['id', 'code', 'name', 'active']);

        $warehouseIds = $warehouses->pluck('id')->map(fn ($id) => (int) $id)->all();

        $stockRows = $this->stockRows($warehouseIds);
        $mutationRows = $this->mutationRows($warehouseIds, $asOf);
        $pairRows = $this->mergePairRows($stockRows, $mutationRows);

        $rejectItems = $this->rejectItems();
        $rejectItemIds = $rejectItems->pluck('id')->map(fn ($id) => (int) $id)->all();

        // Include category-RJCT stock in WH-RTS (and any other non-reject
        // warehouse) without double-counting rows already selected by warehouse.
        $rejectItemStockRows = $this->stockRows([], $rejectItemIds, true);
        $rejectItemMutationRows = $this->mutationRows([], $asOf, $rejectItemIds, true);
        $selectedRows = $this->mergePairRows(
            $stockRows->concat($rejectItemStockRows),
            $mutationRows->concat($rejectItemMutationRows)
        );

        $warehouseSummary = $this->warehouseSummary($pairRows, $warehouses);
        $itemSummary = $this->itemSummary($selectedRows);
        $account = $this->account1204();

        $stockQty = round((float) $selectedRows->sum('stock_qty'), 3);
        $mutationQty = round((float) $selectedRows->sum('mutation_qty'), 3);
        $qtyVariance = round($stockQty - $mutationQty, 3);
        $mutationValue = round((float) $selectedRows->sum('mutation_value'), 2);
        $unvaluedQty = round((float) $selectedRows->sum('unvalued_qty'), 3);
        $accountVariance = $account['found']
            ? round((float) $account['balance'] - $mutationValue, 2)
            : null;

        $pairMismatches = $selectedRows
            ->filter(fn ($row) => abs((float) $row->qty_variance) > 0.0005)
            ->sortBy([
                ['warehouse_code', 'asc'],
                ['item_code', 'asc'],
            ])
            ->values()
            ->map(fn ($row) => $this->formatPairRow($row))
            ->all();

        $unvaluedRows = $selectedRows
            ->filter(fn ($row) => (float) $row->unvalued_qty > 0.0005)
            ->sortBy([
                ['warehouse_code', 'asc'],
                ['item_code', 'asc'],
            ])
            ->values()
            ->map(fn ($row) => $this->formatPairRow($row))
            ->all();

        $duplicateSkuGroups = $this->duplicateSkuGroups($rejectItems);
        $findings = $this->findings(
            $warehouses,
            $account,
            $qtyVariance,
            $accountVariance,
            $pairMismatches,
            $unvaluedRows,
            $duplicateSkuGroups
        );

        return [
            'generated_at' => $asOf->toIso8601String(),
            'read_only' => true,
            'defect_warehouse_codes' => self::DEFECT_WAREHOUSE_CODES,
            'warehouses' => $warehouseSummary,
            'items' => $itemSummary,
            'account_1204' => $account,
            'totals' => [
                'stock_qty' => $stockQty,
                'mutation_qty' => $mutationQty,
                'qty_variance' => $qtyVariance,
                'mutation_value' => $mutationValue,
                'unvalued_qty' => $unvaluedQty,
                'account_1204_variance' => $accountVariance,
            ],
            'pair_mismatches' => $pairMismatches,
            'unvalued_rows' => $unvaluedRows,
            'duplicate_sku_groups' => $duplicateSkuGroups,
            'findings' => $findings,
            'summary' => [
                'status' => empty($findings) ? 'CLEAN' : 'REVIEW',
                'finding_count' => count($findings),
            ],
        ];
    }

    private function stockRows(array $warehouseIds, array $itemIds = [], bool $allWarehouses = false): Collection
    {
        if ($allWarehouses && $itemIds === []) {
            return collect();
        }

        return DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id')
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->when(!$allWarehouses, fn ($query) => $query->whereIn('s.warehouse_id', $warehouseIds))
            ->when($itemIds !== [], fn ($query) => $query->whereIn('s.item_id', $itemIds))
            ->select([
                's.warehouse_id',
                's.item_id',
                'w.code as warehouse_code',
                'w.name as warehouse_name',
                'i.code as item_code',
                'i.name as item_name',
                's.qty as stock_qty',
            ])
            ->get()
            ->map(function ($row) {
                $row->stock_qty = (float) $row->stock_qty;
                return $row;
            });
    }

    private function mutationRows(array $warehouseIds, CarbonImmutable $asOf, array $itemIds = [], bool $allWarehouses = false): Collection
    {
        if ($allWarehouses && $itemIds === []) {
            return collect();
        }

        return DB::table('inventory_mutations as m')
            ->join('warehouses as w', 'w.id', '=', 'm.warehouse_id')
            ->join('items as i', 'i.id', '=', 'm.item_id')
            ->when(!$allWarehouses, fn ($query) => $query->whereIn('m.warehouse_id', $warehouseIds))
            ->when($itemIds !== [], fn ($query) => $query->whereIn('m.item_id', $itemIds))
            ->whereDate('m.date', '<=', $asOf->toDateString())
            ->groupBy('m.warehouse_id', 'm.item_id', 'w.code', 'w.name', 'i.code', 'i.name')
            ->selectRaw('
                m.warehouse_id,
                m.item_id,
                w.code as warehouse_code,
                w.name as warehouse_name,
                i.code as item_code,
                i.name as item_name,
                SUM(m.qty_change) as mutation_qty,
                SUM(COALESCE(m.total_cost, 0)) as mutation_value,
                SUM(CASE WHEN m.total_cost IS NULL OR m.unit_cost IS NULL OR m.total_cost = 0 THEN ABS(m.qty_change) ELSE 0 END) as unvalued_qty,
                COUNT(m.id) as mutation_count
            ')
            ->get()
            ->map(function ($row) {
                $row->mutation_qty = (float) $row->mutation_qty;
                $row->mutation_value = (float) $row->mutation_value;
                $row->unvalued_qty = (float) $row->unvalued_qty;
                $row->mutation_count = (int) $row->mutation_count;
                return $row;
            });
    }

    private function mergePairRows(Collection $stockRows, Collection $mutationRows): Collection
    {
        $rows = collect();

        foreach ($stockRows->concat($mutationRows) as $source) {
            $key = ((int) $source->warehouse_id) . ':' . ((int) $source->item_id);
            $row = $rows->get($key) ?? (object) [
                'warehouse_id' => (int) $source->warehouse_id,
                'item_id' => (int) $source->item_id,
                'warehouse_code' => (string) $source->warehouse_code,
                'warehouse_name' => (string) $source->warehouse_name,
                'item_code' => (string) $source->item_code,
                'item_name' => (string) $source->item_name,
                'stock_qty' => 0.0,
                'mutation_qty' => 0.0,
                'mutation_value' => 0.0,
                'unvalued_qty' => 0.0,
                'mutation_count' => 0,
            ];

            if (isset($source->stock_qty)) {
                $row->stock_qty = (float) $source->stock_qty;
            }
            if (isset($source->mutation_qty)) {
                $row->mutation_qty = (float) $source->mutation_qty;
                $row->mutation_value = (float) $source->mutation_value;
                $row->unvalued_qty = (float) $source->unvalued_qty;
                $row->mutation_count = (int) $source->mutation_count;
            }

            $row->qty_variance = round($row->stock_qty - $row->mutation_qty, 3);
            $rows->put($key, $row);
        }

        return $rows->values();
    }

    private function warehouseSummary(Collection $pairRows, Collection $warehouses): array
    {
        return $warehouses->map(function ($warehouse) use ($pairRows) {
            $rows = $pairRows->where('warehouse_id', (int) $warehouse->id);

            return [
                'id' => (int) $warehouse->id,
                'code' => $warehouse->code,
                'name' => $warehouse->name,
                'active' => (bool) $warehouse->active,
                'stock_qty' => round((float) $rows->sum('stock_qty'), 3),
                'mutation_qty' => round((float) $rows->sum('mutation_qty'), 3),
                'qty_variance' => round((float) $rows->sum('qty_variance'), 3),
                'mutation_value' => round((float) $rows->sum('mutation_value'), 2),
                'unvalued_qty' => round((float) $rows->sum('unvalued_qty'), 3),
                'mutation_count' => (int) $rows->sum('mutation_count'),
            ];
        })->all();
    }

    private function itemSummary(Collection $pairRows): array
    {
        return $pairRows
            ->groupBy('item_id')
            ->map(function (Collection $rows) {
                $first = $rows->first();

                return [
                    'id' => (int) $first->item_id,
                    'code' => $first->item_code,
                    'name' => $first->item_name,
                    'locations' => $rows->map(fn ($row) => [
                        'warehouse_code' => $row->warehouse_code,
                        'stock_qty' => round((float) $row->stock_qty, 3),
                        'mutation_qty' => round((float) $row->mutation_qty, 3),
                        'qty_variance' => round((float) $row->qty_variance, 3),
                        'mutation_value' => round((float) $row->mutation_value, 2),
                    ])->values()->all(),
                    'stock_qty' => round((float) $rows->sum('stock_qty'), 3),
                    'mutation_qty' => round((float) $rows->sum('mutation_qty'), 3),
                    'qty_variance' => round((float) $rows->sum('qty_variance'), 3),
                    'mutation_value' => round((float) $rows->sum('mutation_value'), 2),
                    'unvalued_qty' => round((float) $rows->sum('unvalued_qty'), 3),
                ];
            })
            ->sortBy('code')
            ->values()
            ->all();
    }

    private function rejectItems(): Collection
    {
        return DB::table('items')
            ->where(function ($query) {
                $query->where('code', 'like', 'REJ-%')
                    ->orWhere('code', 'like', '%-RJCT')
                    ->orWhere('code', '=', 'REJECT');
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name']);
    }

    private function duplicateSkuGroups(Collection $rejectItems): array
    {
        return $rejectItems
            ->groupBy(function ($item) {
                $code = strtoupper((string) $item->code);

                if ($code === 'REJECT') {
                    return 'REJECT';
                }
                if (str_starts_with($code, 'REJ-')) {
                    return substr($code, 4);
                }
                if (str_ends_with($code, '-RJCT')) {
                    return substr($code, 0, -5);
                }

                return $code;
            })
            ->filter(fn (Collection $items) => $items->count() > 1)
            ->map(fn (Collection $items, string $base) => [
                'normalized_key' => $base,
                'codes' => $items->pluck('code')->values()->all(),
                'item_ids' => $items->pluck('id')->map(fn ($id) => (int) $id)->values()->all(),
            ])
            ->values()
            ->all();
    }

    private function account1204(): array
    {
        $account = DB::table('accounts')->where('code', '1204')->first(['id', 'code', 'name', 'is_active']);

        if (!$account) {
            return [
                'found' => false,
                'code' => '1204',
                'name' => null,
                'balance' => null,
                'debit' => null,
                'credit' => null,
                'line_count' => 0,
            ];
        }

        $totals = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $account->id)
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::VOIDED_JOURNAL_SOURCES)
            ->selectRaw('COALESCE(SUM(jl.debit), 0) as debit, COALESCE(SUM(jl.credit), 0) as credit, COUNT(jl.id) as line_count')
            ->first();

        $debit = (float) $totals->debit;
        $credit = (float) $totals->credit;

        return [
            'found' => true,
            'id' => (int) $account->id,
            'code' => $account->code,
            'name' => $account->name,
            'active' => (bool) $account->is_active,
            'balance' => round($debit - $credit, 2),
            'debit' => round($debit, 2),
            'credit' => round($credit, 2),
            'line_count' => (int) $totals->line_count,
        ];
    }

    private function formatPairRow(object $row): array
    {
        return [
            'warehouse_code' => $row->warehouse_code,
            'warehouse_name' => $row->warehouse_name,
            'item_id' => (int) $row->item_id,
            'item_code' => $row->item_code,
            'item_name' => $row->item_name,
            'stock_qty' => round((float) $row->stock_qty, 3),
            'mutation_qty' => round((float) $row->mutation_qty, 3),
            'qty_variance' => round((float) $row->qty_variance, 3),
            'mutation_value' => round((float) $row->mutation_value, 2),
            'unvalued_qty' => round((float) $row->unvalued_qty, 3),
            'mutation_count' => (int) $row->mutation_count,
        ];
    }

    private function findings(
        Collection $warehouses,
        array $account,
        float $qtyVariance,
        ?float $accountVariance,
        array $pairMismatches,
        array $unvaluedRows,
        array $duplicateSkuGroups
    ): array {
        $findings = [];

        $missingWarehouses = collect(self::DEFECT_WAREHOUSE_CODES)
            ->diff($warehouses->pluck('code'))
            ->values()
            ->all();

        if ($missingWarehouses !== []) {
            $findings[] = [
                'code' => 'MISSING_DEFECT_WAREHOUSE',
                'severity' => 'high',
                'message' => 'Gudang reject belum lengkap: ' . implode(', ', $missingWarehouses),
            ];
        }

        if (!$account['found'] || !(bool) ($account['active'] ?? false)) {
            $findings[] = [
                'code' => 'MISSING_OR_INACTIVE_ACCOUNT_1204',
                'severity' => 'high',
                'message' => 'Akun 1204 Persediaan Barang Cacat tidak ditemukan atau nonaktif.',
            ];
        }

        if (abs($qtyVariance) > 0.0005 || $pairMismatches !== []) {
            $findings[] = [
                'code' => 'STOCK_MUTATION_MISMATCH',
                'severity' => 'high',
                'message' => count($pairMismatches) . ' lokasi/item berbeda antara inventory_stocks dan inventory_mutations.',
            ];
        }

        if ($accountVariance !== null && abs($accountVariance) > 0.01) {
            $findings[] = [
                'code' => 'ACCOUNT_1204_VALUE_MISMATCH',
                'severity' => 'high',
                'message' => 'Saldo akun 1204 berbeda dari nilai mutasi stok barang cacat sebesar Rp ' . number_format(abs($accountVariance), 2, ',', '.'),
            ];
        }

        if ($unvaluedRows !== []) {
            $findings[] = [
                'code' => 'UNVALUED_DEFECT_MUTATIONS',
                'severity' => 'medium',
                'message' => count($unvaluedRows) . ' lokasi/item memiliki mutasi reject tanpa nilai HPP lengkap.',
            ];
        }

        if ($duplicateSkuGroups !== []) {
            $findings[] = [
                'code' => 'DUPLICATE_REJECT_SKU_CONVENTION',
                'severity' => 'medium',
                'message' => count($duplicateSkuGroups) . ' kelompok SKU reject memakai lebih dari satu kode.',
            ];
        }

        return $findings;
    }
}
