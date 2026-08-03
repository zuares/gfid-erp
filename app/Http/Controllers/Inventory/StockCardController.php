<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\Item;
use App\Models\SystemSetting;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\StreamedResponse;

class StockCardController extends Controller
{
    protected array $adjustmentReasonCache = [];

    public function index(Request $request)
    {
        $isAjax = $request->boolean('ajax');

        $itemId = $request->input('item_id'); // optional
        $qItem = trim((string) $request->input('q_item', '')); // keyword item (code/name)
        $warehouses = Warehouse::orderBy('name')->get();

        if ($request->has('warehouse_id')) {
            $warehouseId = $request->filled('warehouse_id') ? (int) $request->input('warehouse_id') : null;
        } else {
            $role = strtolower(trim((string) (auth()->user()->role ?? '')));
            if ($role === 'admin') {
                $rts = $warehouses->firstWhere('code', 'WH-RTS');
                $warehouseId = $rts ? $rts->id : null;
            } else {
                $warehouseId = null;
            }
        }

        if ($warehouseId && !$warehouses->firstWhere('id', $warehouseId)) {
            $warehouseId = null;
        }

        $lotId = $request->input('lot_id');

        // Default from_date = cut-off date (jika belum diisi user dan cut-off sudah di-set)
        // Tambahkan ?show_legacy=1 di URL untuk melihat semua data historis
        $cutoffDate   = SystemSetting::cutoffDateString();
        $showLegacy   = $request->boolean('show_legacy');
        $cutoffActive = $cutoffDate && !$request->has('from_date') && !$showLegacy;
        $fromDate = $request->input('from_date', $cutoffActive ? $cutoffDate : null);
        $toDate = $request->input('to_date');
        $hasCost = $request->boolean('has_cost');
        $sortDir = $request->input('sort', 'desc');
        $direction = $request->input('direction'); // in/out/null
        $sourceType = $request->input('source_type'); // string/null

        if (!in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        $availableSourceTypes = [
            '' => 'Semua sumber',
            'purchase_receipt' => 'Penerimaan barang (GRN)',
            'purchase_receipt_reverse' => 'Pembatalan GRN',
            'purchase_receipt_void' => 'GRN dibatalkan',
            'transfer_out' => 'Transfer keluar',
            'transfer_in' => 'Transfer masuk',
            'adjustment' => 'Koreksi stok / opname',
            'cutting_issue' => 'Keluar ke Cutting',
            'cutting_receive' => 'Masuk dari Cutting',
            'sewing_issue' => 'Keluar ke Jahit',
            'sewing_receive' => 'Masuk dari Jahit',
            'stock_request' => 'Permintaan stok',
            'shipment' => 'Pengiriman pesanan',
            'auto_sr_ok_rts' => 'Penerimaan otomatis RTS',
            'sewing_qc_in' => 'QC Jahit masuk',
            'sewing_qc_out' => 'QC Jahit keluar',
            'sewing_qc_reject' => 'QC Jahit reject',
        ];

        $role = strtolower(trim((string) (auth()->user()->role ?? '')));
        $isOwner = $role === 'owner';
        $canViewCost = $isOwner;

        // helper response
        $respond = function (array $payload) use ($isAjax, $cutoffDate, $cutoffActive, $showLegacy, $role, $isOwner, $canViewCost) {
            // Tambahkan info cut-off ke semua view
            $payload['cutoff'] = [
                'date'   => $cutoffDate,
                'active' => $cutoffActive,
                'legacy' => $showLegacy,
            ];
            $payload['role'] = $role;
            $payload['isOwner'] = $isOwner;
            $payload['canViewCost'] = $canViewCost;

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
                ->with(['warehouse', 'lot', 'item', 'createdBy'])
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

            // Running saldo untuk mode semua transaksi.
            // Ini saldo kumulatif dari seluruh mutasi yang sedang difilter,
            // jadi kolom "Stok Akhir" tetap muncul walau item belum dipilih.
            $calcRows = (clone $q)
                ->orderBy('date')
                ->orderBy('created_at')
                ->orderBy('id')
                ->get(['id', 'qty_change']);

            $runningQty = 0.0;
            $runningById = [];
            foreach ($calcRows as $m) {
                $runningQty += (float) $m->qty_change;
                $runningById[$m->id] = $runningQty;
            }

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
                $m->running_qty = $runningById[$m->id] ?? null;
                $m->created_by_name = $this->stockCardActorName($m);
                $m->adjustment_reason = $this->stockCardAdjustmentReason($m);
                $sourceMeta = $this->stockCardSourceMeta((string) ($m->source_type ?? ''), $m->source_id ? (int) $m->source_id : null);
                $m->source_label = $sourceMeta['label'];
                $m->source_detail = $sourceMeta['detail'];
            }

            return $respond([
                'warehouses' => $warehouses,
                'lots' => collect(),
                'mutations' => $mutations, // paginator
                'openingQty' => 0,
                'openingValue' => 0,
                'closingQty' => $runningQty,
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

        if (!$fromDate && !$toDate) {
            $toDate = now()->toDateString();
            $fromDate = now()->subDays(30)->toDateString();
        }

        $baseQuery = InventoryMutation::query()
            ->with(['warehouse', 'lot', 'createdBy'])
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
            $m->created_by_name = $this->stockCardActorName($m);
            $m->adjustment_reason = $this->stockCardAdjustmentReason($m);
            $sourceMeta = $this->stockCardSourceMeta((string) ($m->source_type ?? ''), $m->source_id ? (int) $m->source_id : null);
            $m->source_label = $sourceMeta['label'];
            $m->source_detail = $sourceMeta['detail'];
        }

        // KPI item-mode totals
        $sumIn = (float) $mutations->sum(fn($m) => ($m->direction === 'in') ? abs((float) $m->qty_change) : 0);
        $sumOut = (float) $mutations->sum(fn($m) => ($m->direction === 'out') ? abs((float) $m->qty_change) : 0);

        return $respond([
            'warehouses' => $warehouses,
            'lots' => collect(),
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
            ->with(['warehouse', 'lot', 'createdBy'])
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
                'Oleh',
                'Direction',
                'Qty',
                'Saldo Qty',
                'Nilai Mutasi',
                'Saldo Nilai',
                'Catatan',
            ]);

            fputcsv($handle, [
                'Saldo Awal', '', '', '', '', '', 0,
                $openingQty, 0, $openingValue, '',
            ]);

            foreach ($mutations as $m) {
                $qtyAbs = abs((float) $m->qty_change);
                $warehouseLabel = $m->warehouse ? ($m->warehouse->code . ' - ' . $m->warehouse->name) : '';
                $lotCode = $m->lot?->code ?? '';
                $source = ($m->source_type ?? '') . ' #' . ($m->source_id ?? '-');
                $createdBy = $this->stockCardActorName($m);

                fputcsv($handle, [
                    optional($m->date)->format('Y-m-d'),
                    $warehouseLabel,
                    $lotCode,
                    $source,
                    $createdBy,
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

    protected function stockCardSourceMeta(string $sourceType, ?int $sourceId = null): array
    {
        $rawType = trim($sourceType);
        $type = strtolower(class_basename($rawType));

        $label = match ($type) {
            'purchase_receipt' => 'Barang masuk dari pembelian',
            'purchase_receipt_reverse', 'purchase_receipt_void' => 'Pembatalan penerimaan pembelian',
            'purchase_return' => 'Retur pembelian',
            'transfer_out' => 'Kirim ke gudang lain',
            'transfer_in' => 'Terima dari gudang lain',
            'adjustment', 'inventoryadjustment' => 'Koreksi stok / opname',
            'cutting_issue' => 'Keluar untuk cutting',
            'cutting_receive' => 'Masuk dari cutting',
            'sewing_issue' => 'Keluar untuk jahit',
            'sewing_receive' => 'Masuk dari jahit',
            'sewing_return_ok' => 'Masuk dari jahit',
            'sewing_return_reject' => 'Masuk dari jahit (perlu perbaikan)',
            'sewing_rework_ok' => 'Masuk dari jahit (selesai diperbaiki)',
            'stock_request' => 'Permintaan stok',
            'shipment' => 'Barang keluar ke pesanan',
            'auto_sr_ok_rts' => 'Masuk otomatis ke RTS',
            'production_issue' => 'Keluar untuk produksi',
            'production_receipt' => 'Masuk dari produksi',
            'production_activity_cut' => 'Hasil cutting',
            'production_activity_cut_void' => 'Pembatalan hasil cutting',
            'opening_balance' => 'Saldo awal',
            'opening_balance_void' => 'Batal saldo awal',
            'cash_receipt' => 'Penerimaan kas',
            'cash_receipt_void' => 'Batal penerimaan kas',
            'cash_expense' => 'Pengeluaran kas',
            'cash_expense_void' => 'Batal pengeluaran kas',
            'marketplace_payout' => 'Pencairan marketplace',
            'marketplace_payout_void' => 'Batal pencairan marketplace',
            'prd_dispatch_correction' => 'Koreksi pengiriman PRD',
            'dev_command' => 'Aksi sistem / developer',
            'scanner' => 'Input scanner',
            'sewing_qc_in' => 'Masuk dari QC jahit',
            'sewing_qc_out' => 'Keluar dari QC jahit',
            'sewing_qc_reject' => 'Reject QC jahit',
            'app\models\inventoryadjustment' => 'Koreksi stok / opname',
            'app\models\sewingpickup' => 'Pengambilan jahit',
            'app\models\finishingjob' => 'Pekerjaan finishing',
            default => $this->humanizeStockCardSource($sourceType),
        };

        $detail = match ($type) {
            'purchase_receipt' => 'Barang datang dari supplier atau pembelian',
            'purchase_receipt_reverse', 'purchase_receipt_void' => 'Penerimaan pembelian dibatalkan',
            'purchase_return' => 'Barang dikembalikan ke supplier',
            'transfer_out' => 'Barang dikirim keluar dari gudang asal',
            'transfer_in' => 'Barang diterima dari gudang lain',
            'adjustment', 'inventoryadjustment', 'app\\models\\inventoryadjustment' => 'Selisih atau penyesuaian stok',
            'cutting_issue' => 'Barang dipakai untuk proses cutting',
            'cutting_receive' => 'Barang kembali dari proses cutting',
            'sewing_issue' => 'Barang dipakai untuk proses jahit',
            'sewing_receive' => 'Barang kembali dari proses jahit',
            'sewing_return_ok' => 'Barang hasil jahit yang lolos pengecekan',
            'sewing_return_reject' => 'Barang hasil jahit yang perlu perbaikan',
            'sewing_rework_ok' => 'Barang kembali setelah perbaikan selesai',
            'stock_request' => 'Stok diminta untuk kebutuhan proses lain',
            'shipment' => 'Barang dikirim ke pesanan pelanggan',
            'auto_sr_ok_rts' => 'Mutasi otomatis saat barang masuk ke RTS',
            'production_issue' => 'Barang keluar untuk produksi',
            'production_receipt' => 'Barang masuk dari hasil produksi',
            'production_activity_cut' => 'Barang hasil aktivitas cutting',
            'production_activity_cut_void' => 'Pembatalan hasil aktivitas cutting',
            'opening_balance' => 'Stok awal periode',
            'opening_balance_void' => 'Pembatalan stok awal periode',
            'cash_receipt' => 'Penerimaan kas yang memengaruhi stok',
            'cash_receipt_void' => 'Pembatalan penerimaan kas',
            'cash_expense' => 'Pengeluaran kas yang memengaruhi stok',
            'cash_expense_void' => 'Pembatalan pengeluaran kas',
            'marketplace_payout' => 'Pencairan marketplace',
            'marketplace_payout_void' => 'Pembatalan pencairan marketplace',
            'prd_dispatch_correction' => 'Koreksi atas pengiriman PRD',
            'dev_command' => 'Aksi yang dijalankan dari sistem atau developer',
            'scanner' => 'Input dibuat lewat scanner',
            'sewing_qc_in' => 'Barang masuk dari hasil QC jahit',
            'sewing_qc_out' => 'Barang keluar dari hasil QC jahit',
            'sewing_qc_reject' => 'Barang ditolak saat QC jahit',
            default => null,
        };

        if ($sourceId !== null) {
            $detail = trim(($detail ? $detail . ' · ' : '') . 'Ref #' . $sourceId);
        }

        return [
            'label' => $label,
            'detail' => $detail,
        ];
    }

    protected function stockCardAdjustmentReason(InventoryMutation $mutation): ?string
    {
        $type = strtolower(class_basename(trim((string) ($mutation->source_type ?? ''))));
        $isAdjustment = in_array($type, ['adjustment', 'inventoryadjustment'], true)
            || in_array(strtolower(trim((string) ($mutation->source_type ?? ''))), ['adjustment', 'inventoryadjustment', 'app\\models\\inventoryadjustment'], true);

        if (!$isAdjustment || !$mutation->source_id) {
            return null;
        }

        $sourceId = (int) $mutation->source_id;

        if (!array_key_exists($sourceId, $this->adjustmentReasonCache)) {
            $this->adjustmentReasonCache[$sourceId] = InventoryAdjustment::query()
                ->whereKey($sourceId)
                ->value('reason');
        }

        $reason = trim((string) ($this->adjustmentReasonCache[$sourceId] ?? ''));

        if ($reason !== '') {
            return $reason;
        }

        $notes = trim((string) ($mutation->notes ?? ''));
        return $notes !== '' ? $notes : null;
    }

    protected function stockCardActorName(InventoryMutation $mutation): string
    {
        return trim((string) ($mutation->createdBy?->name ?: 'Sistem'));
    }

    protected function humanizeStockCardSource(string $sourceType): string
    {
        $sourceType = trim($sourceType);
        if ($sourceType === '') {
            return 'Mutasi stok';
        }

        $display = str_contains($sourceType, '\\') ? class_basename($sourceType) : $sourceType;
        $display = preg_replace('/([a-z])([A-Z])/', '$1 $2', $display) ?? $display;
        $display = str_replace(['_', '-'], ' ', $display);
        $display = trim(preg_replace('/\s+/', ' ', $display) ?? $display);

        $translations = [
            'adjustment' => 'koreksi',
            'auto' => 'otomatis',
            'balance' => 'saldo',
            'cash' => 'kas',
            'cutting' => 'cutting',
            'dev' => 'sistem',
            'expense' => 'pengeluaran',
            'finishing' => 'finishing',
            'in' => 'masuk',
            'issue' => 'keluar',
            'marketplace' => 'marketplace',
            'normalization' => 'normalisasi',
            'opening' => 'awal',
            'order' => 'pesanan',
            'out' => 'keluar',
            'payout' => 'pencairan',
            'pickup' => 'pengambilan',
            'production' => 'produksi',
            'purchase' => 'pembelian',
            'receipt' => 'penerimaan',
            'reject' => 'reject',
            'request' => 'permintaan',
            'return' => 'retur',
            'rework' => 'perbaikan',
            'scanner' => 'scanner',
            'sewing' => 'jahit',
            'shipment' => 'pengiriman',
            'stock' => 'stok',
            'transfer' => 'transfer',
            'void' => 'batal',
            'wip' => 'WIP',
        ];

        $parts = preg_split('/\s+/', strtolower($display), -1, PREG_SPLIT_NO_EMPTY) ?: [];
        $parts = array_map(fn ($part) => $translations[$part] ?? $part, $parts);

        $phrase = trim(implode(' ', $parts));
        return $phrase !== '' ? Str::headline($phrase) : 'Mutasi stok';
    }
}
