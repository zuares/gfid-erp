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

    /**
     * Kartu stok per item + per LOT, dengan nilai (cost).
     */
    public function index(Request $request)
    {
        $items = Item::where('active', 1)->orderBy('name')->get();
        $warehouses = Warehouse::orderBy('name')->get();

        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $lotId = $request->input('lot_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $hasCost = $request->boolean('has_cost'); // filter: hanya mutasi yang punya cost
        $sortDir = $request->input('sort', 'desc'); // asc / desc
        $direction = $request->input('direction'); // in / out / null
        $sourceType = $request->input('source_type'); // string atau null

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        // daftar source type yang umum dipakai (boleh kamu modif sendiri)
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

        $mutations = collect();
        $openingQty = 0;
        $openingValue = 0;
        $closingQty = 0;
        $closingValue = 0;
        $selectedItem = null;
        $lots = collect();

        if ($itemId) {
            $selectedItem = Item::find($itemId);

            // daftar LOT untuk dropdown
            $lots = Lot::where('item_id', $itemId)
                ->orderByDesc('created_at')
                ->get();

            // default periode 30 hari terakhir kalau kosong
            if (!$fromDate && !$toDate) {
                $toDate = now()->toDateString();
                $fromDate = now()->subDays(30)->toDateString();
            }

            // base query
            $baseQuery = InventoryMutation::with(['warehouse', 'lot'])
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

            // filter jenis mutasi: masuk / keluar
            if ($direction === 'in') {
                $baseQuery->where('qty_change', '>', 0);
            } elseif ($direction === 'out') {
                $baseQuery->where('qty_change', '<', 0);
            }

            // filter source_type
            if ($sourceType) {
                $baseQuery->where('source_type', $sourceType);
            }

            // saldo awal (qty & value) sebelum fromDate
            if ($fromDate) {
                $openingQuery = clone $baseQuery;

                $openingQty = (float) $openingQuery
                    ->whereDate('date', '<', $fromDate)
                    ->sum('qty_change');

                $openingValue = (float) $openingQuery
                    ->whereDate('date', '<', $fromDate)
                    ->sum('total_cost'); // signed: in +, out -
            }

            // mutasi periode
            $mutationsQuery = clone $baseQuery;

            if ($fromDate) {
                $mutationsQuery->whereDate('date', '>=', $fromDate);
            }
            if ($toDate) {
                $mutationsQuery->whereDate('date', '<=', $toDate);
            }

            // 1) Ambil ASC untuk hitung running balance
            $mutationsForCalc = (clone $mutationsQuery)
                ->orderBy('date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get();

            $runningQty = $openingQty;
            $runningValue = $openingValue;
            $runningById = [];

            foreach ($mutationsForCalc as $m) {
                $runningQty += (float) $m->qty_change;
                $runningValue += (float) ($m->total_cost ?? 0);

                $runningById[$m->id] = [
                    'qty' => $runningQty,
                    'value' => $runningValue,
                ];
            }

            // 2) Ambil mutasi sesuai sort (asc / desc) untuk ditampilkan
            if ($sortDir === 'asc') {
                $mutations = (clone $mutationsQuery)
                    ->orderBy('date')
                    ->orderBy('created_at')
                    ->orderBy('id')
                    ->get();
            } else {
                $mutations = (clone $mutationsQuery)
                    ->orderByDesc('date')
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->get();
            }

            // tempel running balance
            foreach ($mutations as $m) {
                $m->running_qty = $runningById[$m->id]['qty'] ?? null;
                $m->running_value = $runningById[$m->id]['value'] ?? null;
            }

            $closingQty = $runningQty;
            $closingValue = $runningValue;
        }

        return view('inventory.stock_card', [
            'items' => $items,
            'warehouses' => $warehouses,
            'lots' => $lots,
            'mutations' => $mutations,
            'openingQty' => $openingQty,
            'openingValue' => $openingValue,
            'closingQty' => $closingQty,
            'closingValue' => $closingValue,
            'selectedItem' => $selectedItem,
            'availableSourceTypes' => $availableSourceTypes,
            'filters' => [
                'item_id' => $itemId,
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

    /**
     * Export kartu stok ke Excel (CSV) dengan filter yang sama.
     * (Tetap urut kronologis ASC, biar rapi di Excel)
     */
    public function export(Request $request): StreamedResponse
    {
        $itemId = $request->input('item_id');
        $warehouseId = $request->input('warehouse_id');
        $lotId = $request->input('lot_id');
        $fromDate = $request->input('from_date');
        $toDate = $request->input('to_date');
        $hasCost = $request->boolean('has_cost');

        if (!$itemId) {
            abort(400, 'Item wajib dipilih untuk export kartu stok.');
        }

        $item = Item::findOrFail($itemId);

        // base query sama seperti index
        $baseQuery = InventoryMutation::with(['warehouse', 'lot'])
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

        // default periode 30 hari terakhir kalau kosong
        if (!$fromDate && !$toDate) {
            $toDate = now()->toDateString();
            $fromDate = now()->subDays(30)->toDateString();
        }

        $openingQty = 0;
        $openingValue = 0;

        if ($fromDate) {
            $openingQuery = clone $baseQuery;

            $openingQty = (float) $openingQuery
                ->whereDate('date', '<', $fromDate)
                ->sum('qty_change');

            $openingValue = (float) $openingQuery
                ->whereDate('date', '<', $fromDate)
                ->sum('total_cost');
        }

        $mutationsQuery = clone $baseQuery;

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

        // running balance
        $runningQty = $openingQty;
        $runningValue = $openingValue;

        foreach ($mutations as $m) {
            $runningQty += (float) $m->qty_change;
            $runningValue += (float) ($m->total_cost ?? 0);

            $m->running_qty = $runningQty;
            $m->running_value = $runningValue;
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

            // header info
            fputcsv($handle, ['Kartu Stok Item', $item->code, $item->name]);
            fputcsv($handle, ['Periode', $fromDate ?: '-', $toDate ?: '-']);
            fputcsv($handle, []); // empty line

            // header kolom
            fputcsv($handle, [
                'Tgl',
                'Gudang',
                'LOT',
                'Sumber',
                'Qty Masuk',
                'Qty Keluar',
                'Saldo Qty',
                'Nilai Mutasi',
                'Saldo Nilai',
                'Catatan',
            ]);

            // saldo awal
            fputcsv($handle, [
                'Saldo Awal',
                '',
                '',
                '',
                0,
                0,
                $openingQty,
                0,
                $openingValue,
                '',
            ]);

            // baris mutasi
            foreach ($mutations as $m) {
                $qtyIn = $m->qty_change > 0 ? $m->qty_change : 0;
                $qtyOut = $m->qty_change < 0 ? abs($m->qty_change) : 0;
                $value = $m->total_cost ?? 0;

                $warehouseLabel = $m->warehouse
                ? $m->warehouse->code . ' - ' . $m->warehouse->name
                : '';

                $lotCode = $m->lot?->code ?? '';

                $source = $m->source_type . ' #' . ($m->source_id ?? '-');

                fputcsv($handle, [
                    optional($m->date)->format('Y-m-d'),
                    $warehouseLabel,
                    $lotCode,
                    $source,
                    $qtyIn,
                    $qtyOut,
                    $m->running_qty ?? 0,
                    $value,
                    $m->running_value ?? 0,
                    $m->notes ?? '',
                ]);
            }

            fclose($handle);
        }, $fileName, [
            'Content-Type' => 'text/csv',
        ]);
    }
}
