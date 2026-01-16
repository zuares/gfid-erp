<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\Lot;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockCardController extends Controller
{
    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');

        $itemId = $request->input('item_id'); // optional
        $qItem = trim((string) $request->input('q_item', '')); // keyword item (code/name)
        $warehouseId = $request->input('warehouse_id');
        $lotId = $request->input('lot_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $hasCost = $request->boolean('has_cost');
        $sortDir = $request->input('sort', 'desc');
        $direction = $request->input('direction'); // in/out/null
        $sourceType = $request->input('source_type'); // string/null

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $warehouses = Warehouse::orderBy('name')->get();

        $availableSourceTypes = [
            '' => 'Semua sumber',
            'purchase_receipt' => 'Goods Receipt (GRN)',
            'purchase_receipt_reverse' => 'Reverse GRN',
            'transfer_out' => 'Transfer Keluar',
            'transfer_in' => 'Transfer Masuk',
            'adjustment' => 'Penyesuaian (Opname)',
            'cutting_issue' => 'Issue ke Cutting',
            'cutting_receive' => 'Receive dari Cutting',
            'sewing_issue' => 'Issue ke Sewing',
            'sewing_receive' => 'Receive dari Sewing',
        ];

        // helper response
        $respond = function (array $payload) use ($isAjax) {
            if ($isAjax) {
                return response()->json([
                    'kpi' => view('inventory.stock_card._kpi', $payload)->render(),
                    'table' => view('inventory.stock_card._table', $payload)->render(),
                ]);
            }
            return view('inventory.stock_card.index', $payload);
        };

        // =========================
        // MODE A: ALL MUTATIONS (item_id kosong)
        // =========================
        if (!$itemId) {
            $q = InventoryMutation::query()
                ->with(['warehouse', 'lot', 'item'])
                ->when($warehouseId, fn($qq) => $qq->where('warehouse_id', $warehouseId))
                ->when($lotId, fn($qq) => $qq->where('lot_id', $lotId))
                ->when($hasCost, fn($qq) => $qq->whereNotNull('total_cost'))
                ->when($direction === 'in', fn($qq) => $qq->where('direction', 'in'))
                ->when($direction === 'out', fn($qq) => $qq->where('direction', 'out'))
                ->when($sourceType, fn($qq) => $qq->where('source_type', $sourceType))
                ->when($fromDate, fn($qq) => $qq->whereDate('date', '>=', $fromDate))
                ->when($toDate, fn($qq) => $qq->whereDate('date', '<=', $toDate))
                ->when($qItem !== '', function ($qq) use ($qItem) {
                    $qq->whereHas('item', function ($w) use ($qItem) {
                        $w->where('code', 'like', "%{$qItem}%")
                            ->orWhere('name', 'like', "%{$qItem}%");
                    });
                });

            // KPI totals
            $totalsRows = (clone $q)->get(['direction', 'qty_change', 'unit_cost', 'total_cost']);
            $sumIn = (float) $totalsRows->sum(fn($m) => ($m->direction === 'in') ? abs((float) $m->qty_change) : 0);
            $sumOut = (float) $totalsRows->sum(fn($m) => ($m->direction === 'out') ? abs((float) $m->qty_change) : 0);
            $sumValue = (float) $totalsRows->sum(function ($m) {
                $qtyAbs = abs((float) $m->qty_change);
                $isOut = ($m->direction ?? null) === 'out';

                $v = $m->total_cost;
                if ($v === null) {
                    $v = $qtyAbs * (float) ($m->unit_cost ?? 0);
                    if ($isOut) {
                        $v *= -1;
                    }

                }
                return (float) $v;
            });

            $mutations = $q
                ->when($sortDir === 'asc', fn($qq) => $qq->orderBy('date')->orderBy('created_at')->orderBy('id'))
                ->when($sortDir === 'desc', fn($qq) => $qq->orderByDesc('date')->orderByDesc('created_at')->orderByDesc('id'))
                ->paginate(100)
                ->withQueryString();

            foreach ($mutations as $m) {
                $qtyAbs = abs((float) $m->qty_change);
                $isOut = ($m->direction ?? null) === 'out';

                $lineValue = $m->total_cost;
                if ($lineValue === null) {
                    $lineValue = $qtyAbs * (float) ($m->unit_cost ?? 0);
                    if ($isOut) {
                        $lineValue *= -1;
                    }

                }
                $m->line_value = (float) $lineValue;
            }

            return $respond([
                'warehouses' => $warehouses,
                'lots' => collect(),
                'mutations' => $mutations, // paginator
                'openingQty' => 0,
                'openingValue' => 0,
                'closingQty' => 0,
                'closingValue' => 0,
                'selectedItem' => null,
                'availableSourceTypes' => $availableSourceTypes,
                'totals' => [
                    'sumIn' => $sumIn,
                    'sumOut' => $sumOut,
                    'sumValue' => $sumValue,
                ],
                'filters' => [
                    'item_id' => null,
                    'q_item' => $qItem,
                    'warehouse_id' => $warehouseId,
                    'lot_id' => $lotId,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'has_cost' => $hasCost,
                    'sort' => $sortDir,
                    'direction' => $direction,
                    'source_type' => $sourceType,
                ],
            ]);
        }

        // =========================
        // MODE B: PER ITEM (kartu stok running)
        // =========================
        $selectedItem = Item::where('active', 1)->find($itemId);
        if (!$selectedItem) {
            return $respond([
                'warehouses' => $warehouses,
                'lots' => collect(),
                'mutations' => collect(),
                'openingQty' => 0,
                'openingValue' => 0,
                'closingQty' => 0,
                'closingValue' => 0,
                'selectedItem' => null,
                'availableSourceTypes' => $availableSourceTypes,
                'totals' => null,
                'filters' => [
                    'item_id' => null,
                    'q_item' => $qItem,
                    'warehouse_id' => $warehouseId,
                    'lot_id' => $lotId,
                    'from_date' => $fromDate,
                    'to_date' => $toDate,
                    'has_cost' => $hasCost,
                    'sort' => $sortDir,
                    'direction' => $direction,
                    'source_type' => $sourceType,
                ],
            ]);
        }

        $lots = Lot::where('item_id', $itemId)->orderByDesc('created_at')->get();

        if (!$fromDate && !$toDate) {
            $toDate = now()->toDateString();
            $fromDate = now()->subDays(30)->toDateString();
        }

        $baseQuery = InventoryMutation::query()
            ->with(['warehouse', 'lot'])
            ->where('item_id', $itemId)
            ->when($warehouseId, fn($qq) => $qq->where('warehouse_id', $warehouseId))
            ->when($lotId, fn($qq) => $qq->where('lot_id', $lotId))
            ->when($hasCost, fn($qq) => $qq->whereNotNull('total_cost'))
            ->when($direction === 'in', fn($qq) => $qq->where('direction', 'in'))
            ->when($direction === 'out', fn($qq) => $qq->where('direction', 'out'))
            ->when($sourceType, fn($qq) => $qq->where('source_type', $sourceType));

        $openingQty = 0.0;
        $openingValue = 0.0;

        if ($fromDate) {
            $openingQty = (float) (clone $baseQuery)->whereDate('date', '<', $fromDate)->sum('qty_change');

            $openingValue = (float) (clone $baseQuery)
                ->whereDate('date', '<', $fromDate)
                ->get()
                ->sum(function ($m) {
                    $qtyAbs = abs((float) $m->qty_change);
                    $isOut = ($m->direction ?? null) === 'out';

                    $v = $m->total_cost;
                    if ($v === null) {
                        $v = $qtyAbs * (float) ($m->unit_cost ?? 0);
                        if ($isOut) {
                            $v *= -1;
                        }

                    }
                    return (float) $v;
                });
        }

        $mutationsQuery = (clone $baseQuery)
            ->when($fromDate, fn($qq) => $qq->whereDate('date', '>=', $fromDate))
            ->when($toDate, fn($qq) => $qq->whereDate('date', '<=', $toDate));

        // calc running ASC
        $calcRows = (clone $mutationsQuery)
            ->orderBy('date')->orderBy('created_at')->orderBy('id')
            ->get();

        $runningQty = $openingQty;
        $runningValue = $openingValue;
        $runningById = [];

        foreach ($calcRows as $m) {
            $runningQty += (float) $m->qty_change;

            $qtyAbs = abs((float) $m->qty_change);
            $isOut = ($m->direction ?? null) === 'out';

            $lineValue = $m->total_cost;
            if ($lineValue === null) {
                $lineValue = $qtyAbs * (float) ($m->unit_cost ?? 0);
                if ($isOut) {
                    $lineValue *= -1;
                }

            }

            $runningValue += (float) $lineValue;

            $runningById[$m->id] = [
                'qty' => $runningQty,
                'value' => $runningValue,
                'line_value' => (float) $lineValue,
            ];
        }

        $mutations = (clone $mutationsQuery)
            ->when($sortDir === 'asc', fn($qq) => $qq->orderBy('date')->orderBy('created_at')->orderBy('id'))
            ->when($sortDir === 'desc', fn($qq) => $qq->orderByDesc('date')->orderByDesc('created_at')->orderByDesc('id'))
            ->get();

        foreach ($mutations as $m) {
            $m->running_qty = $runningById[$m->id]['qty'] ?? null;
            $m->running_value = $runningById[$m->id]['value'] ?? null;
            $m->line_value = $runningById[$m->id]['line_value'] ?? (float) ($m->total_cost ?? 0);
        }

        // KPI item-mode totals
        $sumIn = (float) $mutations->sum(fn($m) => ($m->direction === 'in') ? abs((float) $m->qty_change) : 0);
        $sumOut = (float) $mutations->sum(fn($m) => ($m->direction === 'out') ? abs((float) $m->qty_change) : 0);

        return $respond([
            'warehouses' => $warehouses,
            'lots' => $lots,
            'mutations' => $mutations, // collection
            'openingQty' => $openingQty,
            'openingValue' => $openingValue,
            'closingQty' => $runningQty,
            'closingValue' => $runningValue,
            'selectedItem' => $selectedItem,
            'availableSourceTypes' => $availableSourceTypes,
            'totals' => [
                'sumIn' => $sumIn,
                'sumOut' => $sumOut,
                'sumValue' => null,
            ],
            'filters' => [
                'item_id' => $itemId,
                'q_item' => $qItem,
                'warehouse_id' => $warehouseId,
                'lot_id' => $lotId,
                'from_date' => $fromDate,
                'to_date' => $toDate,
                'has_cost' => $hasCost,
                'sort' => $sortDir,
                'direction' => $direction,
                'source_type' => $sourceType,
            ],
        ]);
    }

    public function export(Request $request): StreamedResponse
    {
        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $lotId = $request->input('lot_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $hasCost = $request->boolean('has_cost');
        $direction = $request->input('direction');
        $sourceType = $request->input('source_type');

        if (!$itemId) {
            abort(400, 'Item wajib dipilih untuk export kartu stok.');
        }

        $item = Item::findOrFail($itemId);

        $baseQuery = InventoryMutation::query()
            ->with(['warehouse', 'lot'])
            ->where('item_id', $itemId);

        if ($warehouseId) {
            $baseQuery->where('warehouse_id', $warehouseId);
        }

        if ($lotId) {
            $baseQuery->where('lot_id', $lotId);
        }

        if ($hasCost) {
            $baseQuery->whereNotNull('total_cost');
        }

        if ($direction === 'in') {
            $baseQuery->where('direction', 'in');
        } elseif ($direction === 'out') {
            $baseQuery->where('direction', 'out');
        }

        if ($sourceType) {
            $baseQuery->where('source_type', $sourceType);
        }

        if (!$fromDate && !$toDate) {
            $toDate = now()->toDateString();
            $fromDate = now()->subDays(30)->toDateString();
        }

        $openingQty = 0.0;
        $openingValue = 0.0;

        if ($fromDate) {
            $openingQty = (float) (clone $baseQuery)
                ->whereDate('date', '<', $fromDate)
                ->sum('qty_change');

            $openingValue = (float) (clone $baseQuery)
                ->whereDate('date', '<', $fromDate)
                ->get()
                ->sum(function ($m) {
                    $qtyAbs = abs((float) $m->qty_change);
                    $isOut = ($m->direction ?? null) === 'out';

                    if ($m->total_cost !== null) {
                        return (float) $m->total_cost;
                    }

                    $v = $qtyAbs * (float) ($m->unit_cost ?? 0);
                    return $isOut ? -$v : $v;
                });
        }

        $mutationsQuery = (clone $baseQuery);
        if ($fromDate) {
            $mutationsQuery->whereDate('date', '>=', $fromDate);
        }

        if ($toDate) {
            $mutationsQuery->whereDate('date', '<=', $toDate);
        }

        $mutations = $mutationsQuery
            ->orderBy('date')
            ->orderBy('created_at')
            ->orderBy('id')
            ->get();

        $runningQty = $openingQty;
        $runningValue = $openingValue;

        foreach ($mutations as $m) {
            $runningQty += (float) $m->qty_change;

            $qtyAbs = abs((float) $m->qty_change);
            $isOut = ($m->direction ?? null) === 'out';

            $lineValue = $m->total_cost;
            if ($lineValue === null) {
                $lineValue = $qtyAbs * (float) ($m->unit_cost ?? 0);
                if ($isOut) {
                    $lineValue *= -1;
                }

            }

            $runningValue += (float) $lineValue;

            $m->running_qty = $runningQty;
            $m->running_value = $runningValue;
            $m->line_value = (float) $lineValue;
        }

        $fileName = 'stock-card-' . $item->code . '-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use (
            $mutations,
            $item,
            $openingQty,
            $openingValue,
            $fromDate,
            $toDate
        ) {
            $handle = fopen('php://output', 'w');

            fputcsv($handle, ['Kartu Stok Item', $item->code, $item->name]);
            fputcsv($handle, ['Periode', $fromDate ?: '-', $toDate ?: '-']);
            fputcsv($handle, []);

            fputcsv($handle, [
                'Tgl',
                'Gudang',
                'LOT',
                'Sumber',
                'Direction',
                'Qty',
                'Saldo Qty',
                'Nilai Mutasi',
                'Saldo Nilai',
                'Catatan',
            ]);

            fputcsv($handle, [
                'Saldo Awal', '', '', '', '', 0,
                $openingQty, 0, $openingValue, '',
            ]);

            foreach ($mutations as $m) {
                $qtyAbs = abs((float) $m->qty_change);
                $warehouseLabel = $m->warehouse ? ($m->warehouse->code . ' - ' . $m->warehouse->name) : '';
                $lotCode = $m->lot?->code ?? '';
                $source = ($m->source_type ?? '') . ' #' . ($m->source_id ?? '-');

                fputcsv($handle, [
                    optional($m->date)->format('Y-m-d'),
                    $warehouseLabel,
                    $lotCode,
                    $source,
                    $m->direction ?? '',
                    $qtyAbs,
                    $m->running_qty ?? 0,
                    $m->line_value ?? 0,
                    $m->running_value ?? 0,
                    $m->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv']);
    }
}
