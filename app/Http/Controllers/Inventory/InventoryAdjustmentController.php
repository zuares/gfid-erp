<?php

namespace App\Http\Controllers\Inventory;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemCostSnapshot;
use App\Models\ItemRole;
use App\Models\Lot;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use App\Services\Production\SewingSupplyFulfillmentService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class InventoryAdjustmentController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected JournalService $journal,
        protected SewingSupplyFulfillmentService $sewingSupplyFulfillment,
    ) {}

    /**
     * Daftar dokumen penyesuaian stok (Inventory Adjustment)
     * Views : inventory.adjustments.index
     * Routes: inventory.adjustments.*
     */
    public function index(Request $request): View
    {
        $query = InventoryAdjustment::query()
            ->with(['warehouse', 'creator', 'approver'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        // ========= FILTER GUDANG =========
        if ($warehouseId = $request->integer('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

        // ========= FILTER STATUS (VALID ONLY) =========
        $status = $request->input('status');
        $allowedStatuses = [
            InventoryAdjustment::STATUS_DRAFT,
            InventoryAdjustment::STATUS_PENDING,
            InventoryAdjustment::STATUS_APPROVED,
            InventoryAdjustment::STATUS_VOID,
        ];

        if ($status !== null && $status !== '' && $status !== 'all' && in_array($status, $allowedStatuses, true)) {
            $query->where('status', $status);
        }

        // ========= FILTER ASAL DOKUMEN =========
        // source_type = stock_opname / manual / all
        $sourceType = $request->input('source_type');
        if ($sourceType === 'stock_opname') {
            $query->where('source_type', StockOpname::class);
        } elseif ($sourceType === 'manual') {
            $query->whereNull('source_type');
        }

        // ========= FILTER TANGGAL =========
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        // ========= SEARCH =========
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('reason', 'like', '%' . $q . '%')
                    ->orWhere('notes', 'like', '%' . $q . '%');
            });
        }

        $adjustments = $query->paginate(25)->withQueryString();

        $ids = $adjustments->pluck('id')->all();

        // 1) qty summary dari adjustment_lines (selalu ada)
        $qtyRows = \App\Models\InventoryAdjustmentLine::query()
            ->selectRaw('
                inventory_adjustment_id,
                COALESCE(SUM(CASE WHEN direction = "in"  THEN ABS(qty_change) ELSE 0 END), 0) AS in_qty,
                COALESCE(SUM(CASE WHEN direction = "out" THEN ABS(qty_change) ELSE 0 END), 0) AS out_qty
            ')
            ->whereIn('inventory_adjustment_id', $ids)
            ->groupBy('inventory_adjustment_id')
            ->get()
            ->keyBy('inventory_adjustment_id');

        // 2) value summary dari inventory_mutations (hanya ada saat approved / sudah eksekusi)
        $valRows = \App\Models\InventoryMutation::query()
            ->selectRaw('
                source_id as inventory_adjustment_id,
                COALESCE(SUM(CASE WHEN direction = "in"  THEN total_cost ELSE 0 END), 0) AS in_value,
                COALESCE(SUM(CASE WHEN direction = "out" THEN total_cost ELSE 0 END), 0) AS out_value
            ')
            ->where('source_type', \App\Models\InventoryAdjustment::class)
            ->whereIn('source_id', $ids)
            ->groupBy('source_id')
            ->get()
            ->keyBy('inventory_adjustment_id');

        $formatRp = function ($n) {
            $n = (float) ($n ?? 0);
            $neg = $n < 0;
            $n = abs($n);
            return ($neg ? '-Rp ' : 'Rp ') . number_format($n, 0, ',', '.');
        };

        $adjustmentSummaries = [];
        foreach ($ids as $id) {
            $q = $qtyRows[$id] ?? null;
            $v = $valRows[$id] ?? null;

            $inQty = (float) ($q->in_qty ?? 0);
            $outQty = (float) ($q->out_qty ?? 0);

            // total_cost OUT kamu simpan negatif → biar tampilan konsisten, ambil absolute untuk "keluar"
            $inVal = (float) ($v->in_value ?? 0);
            $outValAbs = abs((float) ($v->out_value ?? 0));
            $netVal = $inVal - $outValAbs;

            $adjustmentSummaries[$id] = [
                'in_qty' => $inQty,
                'out_qty' => $outQty,

                // nilai: kalau belum ada mutation → 0 (atau nanti kamu tampilkan "—")
                'in_value' => $inVal,
                'out_value' => $outValAbs,
                'net_value' => $netVal,

                'in_value_fmt' => $formatRp($inVal),
                'out_value_fmt' => $formatRp($outValAbs),
                'net_value_fmt' => $formatRp($netVal),

                // flag biar view bisa kasih label “estimasi / belum dieksekusi”
                'has_value' => $v !== null,
            ];
        }

        $warehouses = Warehouse::orderBy('name')->get();

        $filters = [
            'warehouse_id' => $request->input('warehouse_id'),
            'status' => $request->input('status', 'all'),
            'source_type' => $request->input('source_type', 'all'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'q' => $request->input('q'),
        ];

        return view('inventory.adjustments.index', compact(
            'adjustments',
            'warehouses',
            'filters',
            'adjustmentSummaries'
        ));
    }

    /**
     * Detail 1 dokumen adjustment
     * View: inventory.adjustments.show
     */
    public function show(InventoryAdjustment $inventoryAdjustment): View
    {
        $inventoryAdjustment->load([
            'warehouse',
            'creator',
            'approver',
            'source',
            'lines' => function ($q) {
                $q->with('item')->orderBy('id');
            },
        ]);

        $warehouseId = (int) $inventoryAdjustment->warehouse_id;

        // ========= MAP UNIT COST DARI SO (KALAU SUMBER STOCK OPNAME) =========
        $soUnitCostByItemId = collect();
        if ($inventoryAdjustment->source_type === StockOpname::class && $inventoryAdjustment->source) {
            $inventoryAdjustment->source->loadMissing('lines');
            $soUnitCostByItemId = $inventoryAdjustment->source->lines->pluck('unit_cost', 'item_id');
        }

        // ========= SUMMARY QTY =========
        $totalInQty = (float) $inventoryAdjustment->lines->where('direction', 'in')->sum('qty_change');
        $totalOutQtyAbs = abs((float) $inventoryAdjustment->lines->where('direction', 'out')->sum('qty_change'));

        // ========= (A) APPROVED -> utamakan nilai dari MUTATIONS, tapi HPP boleh fallback =========
        if ($inventoryAdjustment->status === InventoryAdjustment::STATUS_APPROVED) {
            $mutations = InventoryMutation::query()
                ->where('source_type', InventoryAdjustment::class)
                ->where('source_id', $inventoryAdjustment->id)
                ->orderBy('id')
                ->get();

            // total nilai: tetap pakai yang "locked" dari mutasi
            $totalInValue = (float) $mutations->where('direction', 'in')->sum('total_cost'); // positif
            $totalOutValueAbs = abs((float) $mutations->where('direction', 'out')->sum('total_cost')); // out negatif
            $netValue = $totalInValue - $totalOutValueAbs;

            $mutByItem = $mutations->groupBy('item_id');

            $lineTotals = [];
            foreach ($inventoryAdjustment->lines as $line) {
                $muts = $mutByItem->get($line->item_id, collect());

                // nilai "locked" dari mutasi (bisa 0 kalau unit_cost kosong)
                $itemTotalCostAbs = abs((float) $muts->sum('total_cost'));

                $lastWithCost = $muts->whereNotNull('unit_cost')->last();
                $unitCost = $lastWithCost?->unit_cost !== null ? (float) $lastWithCost->unit_cost : null;

                // ✅ Fallback kalau mutasi tidak punya HPP:
                //    pakai aturan yang sama: SO line → snapshot aktif → master HPP (items.hpp)
                if ($unitCost === null || $unitCost <= 0) {
                    $fallback = $this->resolveUnitCostForLine(
                        line: $line,
                        warehouseId: $warehouseId,
                        soUnitCostByItemId: $soUnitCostByItemId
                    );

                    if ($fallback !== null && $fallback > 0) {
                        $unitCost = $fallback;

                        // untuk tampilan per-baris: value = |qty| × HPP fallback
                        $qtyAbs = abs((float) ($line->qty_change ?? 0));
                        $itemTotalCostAbs = $qtyAbs * $unitCost;
                    }
                }

                $lineTotals[$line->id] = [
                    'unit_cost' => $unitCost,
                    'value' => $itemTotalCostAbs,
                    'unit_cost_fmt' => ($unitCost !== null && $unitCost > 0)
                    ? $this->formatRupiah($unitCost)
                    : '-',
                    'value_fmt' => $this->formatRupiah($itemTotalCostAbs),
                ];
            }

            $summary = [
                'total_in_qty' => $totalInQty,
                'total_out_qty_abs' => $totalOutQtyAbs,
                'total_in_value' => $totalInValue,
                'total_out_value' => $totalOutValueAbs,
                'net_value' => $netValue,
            ];

            $summaryFmt = [
                'total_in_value' => $this->formatRupiah($totalInValue),
                'total_out_value' => $this->formatRupiah($totalOutValueAbs),
                'net_value' => $this->formatRupiah($netValue),
            ];

            return view('inventory.adjustments.show', [
                'adjustment' => $inventoryAdjustment,
                'summary' => $summary,
                'summaryFmt' => $summaryFmt,
                'lineTotals' => $lineTotals,
            ]);
        }

        // ========= (B) BELUM APPROVED -> estimasi (pakai resolveUnitCostForLine) =========

        $totalInValue = 0.0;
        $totalOutValue = 0.0;
        $lineTotals = [];

        foreach ($inventoryAdjustment->lines as $line) {
            $qtyChange = (float) ($line->qty_change ?? 0);

            if (abs($qtyChange) < 0.000001) {
                $lineTotals[$line->id] = [
                    'unit_cost' => null,
                    'value' => 0.0,
                    'unit_cost_fmt' => '-',
                    'value_fmt' => $this->formatRupiah(0),
                ];
                continue;
            }

            $unitCost = $this->resolveUnitCostForLine(
                line: $line,
                warehouseId: $warehouseId,
                soUnitCostByItemId: $soUnitCostByItemId
            );

            $value = 0.0;
            if ($unitCost !== null && $unitCost > 0) {
                $value = abs($qtyChange) * $unitCost;

                $dir = $line->direction === 'out' ? 'out' : 'in';
                if ($dir === 'in') {
                    $totalInValue += $value;
                } else {
                    $totalOutValue += $value;
                }
            }

            $lineTotals[$line->id] = [
                'unit_cost' => $unitCost,
                'value' => $value,
                'unit_cost_fmt' => ($unitCost !== null && $unitCost > 0)
                ? $this->formatRupiah($unitCost)
                : '-',
                'value_fmt' => $this->formatRupiah($value),
            ];
        }

        $netValue = $totalInValue - $totalOutValue;

        $summary = [
            'total_in_qty' => $totalInQty,
            'total_out_qty_abs' => $totalOutQtyAbs,
            'total_in_value' => $totalInValue,
            'total_out_value' => $totalOutValue,
            'net_value' => $netValue,
        ];

        $summaryFmt = [
            'total_in_value' => $this->formatRupiah($totalInValue),
            'total_out_value' => $this->formatRupiah($totalOutValue),
            'net_value' => $this->formatRupiah($netValue),
        ];

        return view('inventory.adjustments.show', [
            'adjustment' => $inventoryAdjustment,
            'summary' => $summary,
            'summaryFmt' => $summaryFmt,
            'lineTotals' => $lineTotals,
        ]);
    }

    protected function formatRupiah(float | int | null $amount): string
    {
        $n = (float) ($amount ?? 0);
        $negative = $n < 0;
        $n = abs($n);

        $formatted = number_format($n, 0, ',', '.');
        return ($negative ? '-Rp ' : 'Rp ') . $formatted;
    }

    /**
     * Resolve HPP/unit untuk line (untuk ESTIMASI di show sebelum approved):
     * - Prioritas 1: SO Opening/Periodic line unit_cost (kalau ada)
     * - Prioritas 2: Snapshot aktif item+warehouse
     * - Prioritas 3: Item HPP master (kolom items.hpp)
     */
    protected function resolveUnitCostForLine($line, int $warehouseId, $soUnitCostByItemId): ?float
    {
        // 1) dari SO (opening/periodik)
        if ($soUnitCostByItemId && $soUnitCostByItemId->has($line->item_id)) {
            $uc = (float) $soUnitCostByItemId[$line->item_id];
            if ($uc > 0) {
                return $uc;
            }
        }

        // 2) snapshot aktif
        $snap = ItemCostSnapshot::getActiveForItem($line->item_id, $warehouseId);
        if ($snap && (float) $snap->unit_cost > 0) {
            return (float) $snap->unit_cost;
        }

        // 3) fallback ke master HPP (items.hpp)
        if ($line->item && (float) $line->item->hpp > 0) {
            return (float) $line->item->hpp;
        }

        return null;
    }

    /**
     * Resolve unit cost yang dipakai SAAT APPROVE manual (biar mutation IN juga punya cost)
     * - Snapshot aktif
     * - HPP master (items.hpp)
     * - fallback avg incoming (dari inventory_mutations)
     */
    protected function resolveUnitCostForAdjustmentApprove(
        int $itemId,
        int $warehouseId,
        ?Item $item,
        InventoryService $inventory
    ): ?float {
        $snap = ItemCostSnapshot::getActiveForItem($itemId, $warehouseId);
        if ($snap && (float) $snap->unit_cost > 0) {
            return (float) $snap->unit_cost;
        }

        // ✅ pakai kolom HPP master
        if ($item && (float) $item->hpp > 0) {
            return (float) $item->hpp;
        }

        $avg = (float) $inventory->getItemIncomingUnitCost($warehouseId, $itemId);
        return $avg > 0 ? $avg : null;
    }

    /**
     * Form Adjustment Manual
     * View: inventory.adjustments.manual_create
     */
    public function createManual(): View
    {
        $warehouses = Warehouse::orderBy('code')->get();
        $itemCategories = ItemCategory::query()
            ->where('active', true)
            ->orderBy('kind')
            ->orderBy('code')
            ->get();
        $itemRoles = ItemRole::query()
            ->where('active', true)
            ->orderBy('code')
            ->get();

        return view('inventory.adjustments.manual_create', compact('warehouses', 'itemCategories', 'itemRoles'));
    }

    /**
     * Simpan Adjustment Manual.
     *
     * Owner   -> status APPROVED + eksekusi stok langsung (mutasi + total_cost)
     * NonOwner-> status PENDING  + belum eksekusi stok
     */
    public function storeManual(Request $request, InventoryService $inventory): RedirectResponse
    {
        $rules = [
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.lot_id' => ['nullable', 'exists:lots,id'],
            'lines.*.qty_change' => ['required', 'numeric'],
            'lines.*.notes' => ['nullable', 'string'],
        ];

        $validated = $request->validate($rules);

        foreach ($validated['lines'] as $index => $lineData) {
            $signedChange = (float) ($lineData['qty_change'] ?? 0);
            if (abs($signedChange) < 0.000001) {
                continue;
            }

            if (trim((string) ($lineData['notes'] ?? '')) === '') {
                return back()
                    ->withErrors([
                        "lines.$index.notes" => 'Alasan baris wajib diisi untuk item yang diubah.',
                    ])
                    ->withInput();
            }
        }

        $userId = $request->user()?->id;
        $adjustment = DB::transaction(function () use ($validated, $userId) {
            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateCodeForDate($validated['date']);
            $adjustment->warehouse_id = (int) $validated['warehouse_id'];
            $adjustment->date = $validated['date'];
            $adjustment->reason = 'Adjustment Manual Draft';
            $adjustment->notes = $validated['notes'] ?? null;
            $adjustment->status = InventoryAdjustment::STATUS_DRAFT;
            $adjustment->created_by = $userId;

            $adjustment->save();

            foreach ($validated['lines'] as $lineData) {
                $itemId = (int) $lineData['item_id'];
                $lotId = isset($lineData['lot_id']) && $lineData['lot_id'] ? (int) $lineData['lot_id'] : null;
                $signedChange = (float) $lineData['qty_change'];

                if (abs($signedChange) < 0.000001) {
                    continue;
                }

                $direction = $signedChange >= 0 ? 'in' : 'out';

                $adjustment->lines()->create([
                    'item_id' => $itemId,
                    'qty_before' => null,
                    'qty_after' => null,
                    'qty_change' => $signedChange,
                    'direction' => $direction,
                    'notes' => $lineData['notes'] ?? null,
                    'lot_id' => $lotId,
                ]);
            }

            return $adjustment;
        });

        return redirect()
            ->route('inventory.adjustments.show', ['inventoryAdjustment' => $adjustment->getKey()])
            ->with('status', 'success')
            ->with('message', 'Draft adjustment berhasil disimpan.');
    }

    /**
     * APPROVE dokumen Adjustment (khusus Owner/Admin).
     */
    public function approve(Request $request, InventoryAdjustment $inventoryAdjustment, InventoryService $inventory): RedirectResponse
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Harus login untuk approve adjustment.');
        }

        $role = $user->role ?? null;
        $isOwner = $role === 'owner';
        $isAdmin = $role === 'admin';

        // Hanya Owner & Admin yang boleh approve
        if (!$isOwner && !$isAdmin) {
            abort(403, 'Hanya Owner atau Admin yang boleh approve adjustment.');
        }

        $fromStockOpname = $inventoryAdjustment->source_type === StockOpname::class;

        // Kalau sumber dari StockOpname (opening / periodic) → WAJIB Owner
        if ($fromStockOpname && !$isOwner) {
            abort(403, 'Adjustment dari Stock Opname hanya boleh di-approve oleh Owner.');
        }

        if (!$inventoryAdjustment->canApprove()) {
            return redirect()
                ->route('inventory.adjustments.show', ['inventoryAdjustment' => $inventoryAdjustment->getKey()])
                ->with('status', 'error')
                ->with('message', 'Hanya dokumen dengan status pending yang bisa di-approve.');
        }

        DB::transaction(function () use ($inventoryAdjustment, $inventory, $user) {
            $inventoryAdjustment->load(['lines.item', 'source']);

            $warehouseId = (int) $inventoryAdjustment->warehouse_id;
            $date = $inventoryAdjustment->date?->toDateString() ?? now()->toDateString();

            if ($inventoryAdjustment->source_type === StockOpname::class && $inventoryAdjustment->source_id) {
                // SO Opening / Periodic
                $this->approveFromStockOpname($inventoryAdjustment, $inventory, $warehouseId, $date);
            } else {
                // Manual Adjustment
                $this->approveManualAdjustment($inventoryAdjustment, $inventory, $warehouseId, $date);
            }

            $inventoryAdjustment->status = InventoryAdjustment::STATUS_APPROVED;
            $inventoryAdjustment->approved_by = $user->id;
            $inventoryAdjustment->approved_at = now();
            $inventoryAdjustment->save();

            $this->journal->postInventoryAdjustment($inventoryAdjustment);
            $this->sewingSupplyFulfillment->fulfillApprovedAdjustment($inventoryAdjustment);
        });

        // Post jurnal setelah transaksi inventory committed
        try {
            $this->journal->postInventoryAdjustment($inventoryAdjustment);
        } catch (\Throwable $e) {
            Log::warning('[InventoryAdjustment] Gagal post jurnal setelah approve.', [
                'adjustment_id' => $inventoryAdjustment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('inventory.adjustments.show', ['inventoryAdjustment' => $inventoryAdjustment->getKey()])
            ->with('status', 'success')
            ->with('message', 'Adjustment berhasil di-approve dan stok sudah dikoreksi.');
    }

    /**
     * VOID dokumen Adjustment (khusus Owner/Admin)
     */
    public function void(Request $request, InventoryAdjustment $inventoryAdjustment): RedirectResponse
    {
        $user = $request->user();
        if (!$user) {
            abort(403, 'Harus login untuk membatalkan adjustment.');
        }

        $role = $user->role ?? null;
        if (!in_array($role, ['owner', 'admin'])) {
            abort(403, 'Hanya Owner atau Admin yang boleh membatalkan adjustment.');
        }

        if (!$inventoryAdjustment->isPending() && !$inventoryAdjustment->isDraft()) {
            return redirect()
                ->route('inventory.adjustments.show', ['inventoryAdjustment' => $inventoryAdjustment->getKey()])
                ->with('status', 'error')
                ->with('message', 'Hanya adjustment berstatus pending atau draft yang bisa dibatalkan.');
        }

        $inventoryAdjustment->status = InventoryAdjustment::STATUS_VOID;
        $inventoryAdjustment->save();

        return redirect()
            ->route('inventory.adjustments.show', ['inventoryAdjustment' => $inventoryAdjustment->getKey()])
            ->with('status', 'success')
            ->with('message', 'Adjustment berhasil dibatalkan (VOID).');
    }

    /**
     * Tentukan LOT tujuan untuk mutasi koreksi stok.
     *
     * Kain (RM) di sistem ini di-track per-LOT. Halaman lain yang membaca stok
     * kain — mis. Cutting Job Create (getAvailableLots) — HANYA menampilkan
     * saldo yang menempel pada sebuah LOT (whereNotNull('lot_id')). Kalau koreksi
     * stok opname / adjustment menambah stok kain tapi mutasinya lot_id = null,
     * maka tambahan itu tidak akan pernah muncul sebagai LOT yang bisa dipilih.
     *
     * Aturan:
     *  - Kalau baris sudah menunjuk lot_id → pakai LOT itu.
     *  - Kalau item RM DAN ini penambahan stok (signedDiff > 0) → buat LOT baru
     *    supaya tambahannya lot-tracked dan muncul di daftar LOT.
     *  - Selain itu → null (biarkan perilaku lama; pengurangan tidak bikin LOT baru).
     */
    protected function resolveLotIdForAdjustment(?Item $item, ?int $existingLotId, float $signedDiff): ?int
    {
        if ($existingLotId) {
            return $existingLotId;
        }

        // Hanya untuk PENAMBAHAN stok item RM (kain lot-tracked).
        if ($signedDiff <= 0.000001) {
            return null;
        }

        if (!$item || ($item->role_code ?? null) !== ItemRole::RM) {
            return null;
        }

        $lot = Lot::create([
            'code'         => CodeGenerator::generate('LOT'),
            'item_id'      => (int) $item->id,
            'initial_qty'  => 0,
            'initial_cost' => 0,
            'qty_onhand'   => 0,
            'total_cost'   => 0,
            'avg_cost'     => 0,
            'status'       => 'open',
        ]);

        return (int) $lot->id;
    }

    /**
     * APPROVE untuk Adjustment Manual (source_type NULL).
     * ✅ Update qty_before / qty_after + eksekusi mutasi dengan unit_cost (biar total_cost ada).
     */
    protected function approveManualAdjustment(
        InventoryAdjustment $adjustment,
        InventoryService $inventory,
        int $warehouseId,
        string $date
    ): void {
        foreach ($adjustment->lines as $line) {
            $signed = (float) $line->qty_change;
            if (abs($signed) < 0.000001) {
                continue;
            }

            $qtyBefore = $inventory->getOnHandQty(warehouseId: $warehouseId, itemId: $line->item_id);

            $unitCostOverride = $this->resolveUnitCostForAdjustmentApprove(
                itemId: (int) $line->item_id,
                warehouseId: $warehouseId,
                item: $line->item,
                inventory: $inventory
            );

            $mutation = $inventory->adjustByDifference(
                warehouseId: $warehouseId,
                itemId: $line->item_id,
                qtyChange: $signed,
                date: $date,
                sourceType: InventoryAdjustment::class,
                sourceId: $adjustment->id,
                notes: $line->notes ?? $adjustment->reason,
                lotId: $this->resolveLotIdForAdjustment(
                    $line->item,
                    $line->lot_id ? (int) $line->lot_id : null,
                    $signed
                ),
                allowNegative: false,
                unitCostOverride: $unitCostOverride,
                affectLotCost: false,
            );

            if (!$mutation) {
                continue;
            }

            $qtyAfter = $inventory->getOnHandQty(warehouseId: $warehouseId, itemId: $line->item_id);

            $line->qty_before = $qtyBefore;
            $line->qty_after = $qtyAfter;
            $line->direction = $signed >= 0 ? 'in' : 'out';
            $line->save();
        }
    }

    /**
     * APPROVE untuk Adjustment dari StockOpname (opening/periodic).
     * ✅ Set stok real = qty_after (physical)
     * ✅ ISI qty_before/qty_after + qty_change + direction
     * ✅ Kirim unit_cost ke adjustTo supaya mutation punya total_cost
     */
    protected function approveFromStockOpname(
        InventoryAdjustment $adjustment,
        InventoryService $inventory,
        int $warehouseId,
        string $date
    ): void {
        /** @var StockOpname|null $opname */
        $opname = $adjustment->source_type === StockOpname::class ? $adjustment->source : null;

        if ($opname && $opname->type === StockOpname::TYPE_OPENING) {
            $this->approveFromStockOpnameOpening($adjustment, $inventory, $warehouseId, $date, $opname);
            return;
        }

        $this->approveFromStockOpnamePeriodic($adjustment, $inventory, $warehouseId, $date, $opname);
    }

    protected function approveFromStockOpnameOpening(
        InventoryAdjustment $adjustment,
        InventoryService $inventory,
        int $warehouseId,
        string $date,
        StockOpname $opname
    ): void {
        $opname->loadMissing(['lines.item']);

        foreach ($adjustment->lines as $line) {
            if ($line->qty_after === null) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $physicalQty = (float) $line->qty_after;

            // BEFORE (real onhand sebelum koreksi)
            $qtyBefore = $inventory->getOnHandQty($warehouseId, $itemId);

            // diff signed
            $signedDiff = $physicalQty - $qtyBefore;
            if (abs($signedDiff) < 0.000001) {
                // tetap isi audit supaya show enak
                $line->qty_before = $qtyBefore;
                $line->qty_after = $physicalQty;
                $line->qty_change = 0;
                $line->direction = 'in';
                $line->save();
                continue;
            }

            // ambil unit_cost dari SO line
            $soLine = $opname->lines->firstWhere('item_id', $itemId);
            $unitCost = ($soLine && (float) $soLine->unit_cost > 0) ? (float) $soLine->unit_cost : null;

            // ✅ Buat mutasi pakai unitCostOverride → unit_cost & total_cost KEISI
            $inventory->adjustByDifference(
                warehouseId: $warehouseId,
                itemId: $itemId,
                qtyChange: $signedDiff, // signed
                date: $date,
                sourceType: InventoryAdjustment::class,
                sourceId: $adjustment->id,
                notes: $adjustment->reason ?? ('Saldo awal dari SO opening ' . ($opname->code ?? '')),
                lotId: $this->resolveLotIdForAdjustment($line->item, null, $signedDiff),
                allowNegative: false,
                unitCostOverride: $unitCost, // ✅ ini kuncinya
                affectLotCost: false,
            );

            // AFTER = physical
            $line->qty_before = $qtyBefore;
            $line->qty_after = $physicalQty;
            $line->qty_change = $signedDiff;
            $line->direction = $signedDiff >= 0 ? 'in' : 'out';
            $line->save();

            // (opsional) snapshot & update base_unit_cost tetap pakai kode kamu yang sudah ada
        }
    }

    protected function approveFromStockOpnamePeriodic(
        InventoryAdjustment $adjustment,
        InventoryService $inventory,
        int $warehouseId,
        string $date,
        ?StockOpname $opname
    ): void {
        // Pastikan relasi sudah ke-load untuk menghindari N+1
        $adjustment->loadMissing(['lines.item']);

        // Map baris SO per item_id biar gampang ambil unit_cost dari SO
        $opnameLinesByItem = $opname
        ? $opname->lines->keyBy('item_id')
        : collect();

        foreach ($adjustment->lines as $line) {
            if ($line->qty_after === null) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $physicalQty = (float) $line->qty_after;

            // qty_before = stok on hand sebelum penyesuaian
            $qtyBefore = $inventory->getOnHandQty(
                warehouseId: $warehouseId,
                itemId: $itemId
            );

            /**
             * ==== Resolve unit cost periodic ====
             * Urutan:
             * 1. Kalau SO Periodik punya unit_cost di baris item → pakai itu
             * 2. Kalau tidak, pakai HPP master item (items.hpp)
             *
             * (di sini tidak baca ItemCostSnapshot; snapshot dibuat terpisah)
             */
            $unitCost = null;

            // 1) Ambil dari baris SO kalau ada
            $soLine = $opnameLinesByItem->get($itemId);
            if ($soLine && (float) $soLine->unit_cost > 0) {
                $unitCost = (float) $soLine->unit_cost;
            } elseif ($line->item && (float) $line->item->hpp > 0) {
                // 2) fallback dari master item HPP
                $unitCost = (float) $line->item->hpp;
            }

            // Sesuaikan stok ke qty fisik hasil SO
            $inventory->adjustTo(
                warehouseId: $warehouseId,
                itemId: $itemId,
                newQty: $physicalQty,
                date: $date,
                sourceType: InventoryAdjustment::class,
                sourceId: $adjustment->id,
                notes: $adjustment->reason ?? ('Penyesuaian stok dari stock opname ' . ($opname?->code ?? '')),
                lotId: $this->resolveLotIdForAdjustment($line->item, null, $physicalQty - (float) $qtyBefore),
                unitCostOverride: $unitCost, // ← override cost di mutasi periodic
                affectLotCost: false,
            );

            // Update ringkasan line adjustment
            $qtyAfter = $physicalQty;
            $qtyChange = $qtyAfter - (float) $qtyBefore;

            $line->qty_before = $qtyBefore;
            $line->qty_after = $qtyAfter;
            $line->qty_change = $qtyChange;
            $line->direction = $qtyChange >= 0 ? 'in' : 'out';
            $line->save();

            // Snapshot periodic (pakai unitCost yg sama: SO line / HPP master)
            if ($unitCost !== null && $unitCost > 0) {
                ItemCostSnapshot::query()
                    ->where('item_id', $itemId)
                    ->forWarehouseOrGlobal($warehouseId)
                    ->active()
                    ->update(['is_active' => false]);

                ItemCostSnapshot::create([
                    'item_id' => $itemId,
                    'warehouse_id' => $warehouseId,
                    'snapshot_date' => $date,
                    'reference_type' => 'stock_opname_periodic',
                    'reference_id' => $opname?->id ?? $adjustment->id,
                    'qty_basis' => $physicalQty,
                    'rm_unit_cost' => $unitCost,
                    'cutting_unit_cost' => 0,
                    'sewing_unit_cost' => 0,
                    'finishing_unit_cost' => 0,
                    'packaging_unit_cost' => 0,
                    'overhead_unit_cost' => 0,
                    'unit_cost' => $unitCost,
                    'notes' => 'SO periodic ' . ($opname?->code ?? ''),
                    'is_active' => true,
                    'created_by' => $opname?->created_by ?? $adjustment->created_by,
                ]);
            }
        }
    }

    /**
     * Endpoint AJAX: item yang punya stok (qty != 0) di gudang
     * GET /inventory/adjustments/items?warehouse_id=xx&q=KODE
     */
    protected function getLotStockRowsForItems(array $itemIds, int $warehouseId, $itemsCollection = null): array
    {
        $mutations = \App\Models\InventoryMutation::query()
            ->selectRaw('item_id, lot_id, SUM(qty_change) as lot_qty')
            ->where('warehouse_id', $warehouseId)
            ->whereIn('item_id', $itemIds)
            ->whereNotNull('lot_id')
            ->groupBy('item_id', 'lot_id')
            ->havingRaw('SUM(qty_change) != 0')
            ->get();
            
        $results = [];
        if ($mutations->isEmpty()) {
            return $results;
        }
        
        $lots = \App\Models\Lot::whereIn('id', $mutations->pluck('lot_id')->unique())->get()->keyBy('id');
        $items = $itemsCollection ? $itemsCollection->keyBy('id') : \App\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');

        foreach ($mutations as $mut) {
            $item = $items[$mut->item_id] ?? null;
            $lot = $lots[$mut->lot_id] ?? null;
            if (!$item || !$lot) continue;

            $results[] = [
                'id' => $item->id,
                'code' => $item->code ?? '',
                'name' => $item->name ?? '',
                'on_hand' => (float) $mut->lot_qty,
                'lot_id' => $lot->id,
                'lot_code' => $lot->code,
                'not_in_warehouse' => abs((float) $mut->lot_qty) < 0.000001,
            ];
        }

        return $results;
    }

    public function itemsForWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        if (!$warehouseId) {
            return response()->json([], 400);
        }

        $q = trim((string) $request->get('q', ''));
        $itemId = $request->integer('item_id');

        if ($itemId) {
            $item = Item::with('role')->find($itemId);
            if (!$item) {
                return response()->json([], 404);
            }

            if ($item->role_code === 'RM') {
                $lots = $this->getLotStockRowsForItems([$item->id], $warehouseId);
                if (!empty($lots)) {
                    return response()->json($lots);
                }
            }

            $qty = (float) InventoryStock::query()
                ->where('warehouse_id', $warehouseId)
                ->where('item_id', $item->id)
                ->value('qty');

            return response()->json([[
                'id' => $item->id,
                'code' => $item->code ?? '',
                'name' => $item->name ?? '',
                'on_hand' => $qty,
                'lot_id' => null,
                'lot_code' => null,
                'not_in_warehouse' => abs($qty) < 0.000001,
            ]]);
        }

        if ($q === '') {
            $stocks = InventoryStock::query()
                ->with(['item.role'])
                ->where('warehouse_id', $warehouseId)
                ->where('qty', '!=', 0)
                ->orderBy('item_id')
                ->limit(500)
                ->get();

            $results = [];
            $rmItemIds = [];
            
            foreach ($stocks as $stock) {
                if (($stock->item?->role_code ?? '') === 'RM') {
                    $rmItemIds[] = $stock->item_id;
                } else {
                    $results[] = [
                        'id' => $stock->item_id,
                        'code' => $stock->item?->code ?? '',
                        'name' => $stock->item?->name ?? '',
                        'on_hand' => (float) $stock->qty,
                        'lot_id' => null,
                        'lot_code' => null,
                        'not_in_warehouse' => false,
                    ];
                }
            }

            if (!empty($rmItemIds)) {
                $rmLots = $this->getLotStockRowsForItems($rmItemIds, $warehouseId);
                $results = array_merge($results, $rmLots);
            }

            return response()->json($results);
        }

        $items = Item::query()
            ->with('role')
            ->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            })
            ->orderBy('code')
            ->limit(50)
            ->get();

        $itemIds = $items->pluck('id')->toArray();
        if (empty($itemIds)) {
            return response()->json([]);
        }

        $stocks = InventoryStock::query()
            ->where('warehouse_id', $warehouseId)
            ->whereIn('item_id', $itemIds)
            ->pluck('qty', 'item_id');

        $results = [];
        $rmItemIds = [];

        foreach ($items as $item) {
            if ($item->role_code === 'RM') {
                $rmItemIds[] = $item->id;
            } else {
                $results[] = [
                    'id' => $item->id,
                    'code' => $item->code ?? '',
                    'name' => $item->name ?? '',
                    'on_hand' => (float) ($stocks[$item->id] ?? 0),
                    'lot_id' => null,
                    'lot_code' => null,
                    'not_in_warehouse' => !isset($stocks[$item->id]) || abs((float) ($stocks[$item->id] ?? 0)) < 0.000001,
                ];
            }
        }

        if (!empty($rmItemIds)) {
            $rmLots = $this->getLotStockRowsForItems($rmItemIds, $warehouseId, $items);
            $foundItemIds = array_column($rmLots, 'id');
            foreach ($rmItemIds as $rmId) {
                if (!in_array($rmId, $foundItemIds)) {
                    $item = $items->firstWhere('id', $rmId);
                    $rmLots[] = [
                        'id' => $item->id,
                        'code' => $item->code ?? '',
                        'name' => $item->name ?? '',
                        'on_hand' => 0,
                        'lot_id' => null,
                        'lot_code' => null,
                        'not_in_warehouse' => true,
                    ];
                }
            }
            $results = array_merge($results, $rmLots);
        }

        return response()->json($results);
    }

    public function storeQuickItem(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'code' => ['required', 'string', 'max:100', 'unique:items,code'],
            'name' => ['required', 'string', 'max:255'],
            'unit' => ['required', 'string', 'max:30'],
            'item_category_id' => ['nullable', 'exists:item_categories,id'],
            'item_role_id' => ['nullable', 'exists:item_roles,id'],
        ]);

        $role = !empty($validated['item_role_id'])
            ? ItemRole::find((int) $validated['item_role_id'])
            : null;

        $category = !empty($validated['item_category_id'])
            ? ItemCategory::find((int) $validated['item_category_id'])
            : null;

        $roleCode = $role?->code;
        $categoryKind = $category?->kind;

        $type = match ($roleCode) {
            ItemRole::FG => 'finished_good',
            default => 'material',
        };

        if (!$roleCode && $categoryKind === 'product') {
            $type = 'finished_good';
        }

        $legacyRole = match ($roleCode) {
            ItemRole::FG => 'finished_good',
            ItemRole::PKG => 'shipping_supply',
            ItemRole::SUP => 'production_supply',
            default => 'raw_material',
        };

        $item = Item::create([
            'code' => strtoupper(trim($validated['code'])),
            'name' => trim($validated['name']),
            'unit' => trim($validated['unit'] ?: 'pcs'),
            'type' => $type,
            'item_category_id' => $validated['item_category_id'] ?? null,
            'item_role_id' => $validated['item_role_id'] ?? ItemRole::idByCode(ItemRole::RM),
            'item_role' => $legacyRole,
            'active' => true,
            'is_stocked' => true,
            'production_source' => $type === 'finished_good' ? Item::PRODUCTION_IN_HOUSE : Item::PRODUCTION_BUY,
            'can_buy' => false,
            'can_make' => $type === 'finished_good',
            'default_supply_source' => $type === 'finished_good' ? Item::SUPPLY_MAKE : null,
        ]);

        return response()->json([
            'ok' => true,
            'item' => [
                'id' => $item->id,
                'code' => $item->code,
                'name' => $item->name,
                'on_hand' => 0,
                'not_in_warehouse' => true,
            ],
        ]);
    }

    /**
     * Generate kode dokumen ADJ-YYYYMMDD-###
     * pakai TANGGAL DOKUMEN (bukan now) supaya konsisten.
     */
    protected function generateCodeForDate(string $date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');

        $count = InventoryAdjustment::query()
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->count();

        $seq = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "ADJ-{$ymd}-{$seq}";
    }

    public function post(InventoryAdjustment $inventoryAdjustment): RedirectResponse
    {
        $user = auth()->user();
        abort_unless($user && in_array($user->role ?? null, ['owner', 'admin'], true), 403);

        $adjustmentId = (int) $inventoryAdjustment->getKey();
        if ($adjustmentId <= 0) {
            return redirect()
                ->route('inventory.adjustments.index')
                ->with('status', 'error')
                ->with('message', 'Adjustment tidak valid.');
        }

        if (!$inventoryAdjustment->isDraft()) {
            return redirect("/inventory/adjustments/{$adjustmentId}")
                ->with('status', 'warning')
                ->with('message', 'Hanya draft yang bisa dipost. Dokumen ini sudah berstatus ' . ($inventoryAdjustment->status ?? '-'));
        }

        $inventoryAdjustment->loadMissing(['lines.item', 'source']);

        $posted = false;

        DB::transaction(function () use ($inventoryAdjustment, $user, &$posted) {
            $adjustment = InventoryAdjustment::query()
                ->with(['lines.item', 'source'])
                ->whereKey($inventoryAdjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            if (!$adjustment->isDraft()) {
                return;
            }

            $warehouseId = (int) $adjustment->warehouse_id;
            $date = $adjustment->date?->toDateString() ?? now()->toDateString();

            foreach ($adjustment->lines as $line) {
                $signed = (float) $line->qty_change;
                if (abs($signed) < 0.000001) {
                    continue;
                }

                $qtyBefore = $line->lot_id
                    ? $this->inventory->getLotBalance(
                        warehouseId: $warehouseId,
                        itemId: (int) $line->item_id,
                        lotId: (int) $line->lot_id
                    )
                    : $this->inventory->getOnHandQty(
                        warehouseId: $warehouseId,
                        itemId: (int) $line->item_id
                    );

                $unitCostOverride = $this->resolveUnitCostForAdjustmentApprove(
                    itemId: (int) $line->item_id,
                    warehouseId: $warehouseId,
                    item: $line->item,
                    inventory: $this->inventory
                );

                $mutation = $this->inventory->adjustByDifference(
                    warehouseId: $warehouseId,
                    itemId: (int) $line->item_id,
                    qtyChange: $signed,
                    date: $date,
                    sourceType: InventoryAdjustment::class,
                    sourceId: $adjustment->id,
                    notes: $line->notes ?? $adjustment->reason,
                    lotId: $line->lot_id ?? null,
                    allowNegative: false,
                    unitCostOverride: $unitCostOverride,
                    affectLotCost: false,
                );

                if (!$mutation) {
                    continue;
                }

                $qtyAfter = $line->lot_id
                    ? $this->inventory->getLotBalance(
                        warehouseId: $warehouseId,
                        itemId: (int) $line->item_id,
                        lotId: (int) $line->lot_id
                    )
                    : $this->inventory->getOnHandQty(
                        warehouseId: $warehouseId,
                        itemId: (int) $line->item_id
                    );

                $line->update([
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'direction' => $signed >= 0 ? 'in' : 'out',
                ]);
            }

            $adjustment->update([
                'status' => InventoryAdjustment::STATUS_APPROVED,
                'approved_by' => $user->id,
                'approved_at' => now(),
            ]);

            $posted = true;
        });

        if (!$posted) {
            return redirect("/inventory/adjustments/{$adjustmentId}")
                ->with('status', 'warning')
                ->with('message', 'Adjustment sudah berubah status sebelum dipost. Tidak ada perubahan stok yang dilakukan.');
        }

        try {
            $this->journal->postInventoryAdjustment($inventoryAdjustment->fresh());
        } catch (\Throwable $e) {
            Log::warning('[InventoryAdjustment] Gagal post jurnal setelah post().', [
                'adjustment_id' => $inventoryAdjustment->id,
                'error' => $e->getMessage(),
            ]);
        }

        return redirect("/inventory/adjustments/{$adjustmentId}")
            ->with('status', 'success')
            ->with('message', 'Draft adjustment berhasil diposting dan stok sudah dikoreksi.');
    }
    public function cuttingOverproductionCreate(Request $request): View
    {
        abort_unless(in_array(auth()->user()->role ?? null, ['owner', 'admin']), 403);

        $cuttingJobId = (int) $request->get('cutting_job_id');
        abort_unless($cuttingJobId > 0, 404);

        $job = CuttingJob::query()
            ->with(['warehouse', 'bundles.finishedItem'])
            ->findOrFail($cuttingJobId);

        // ✅ 1) kalau job sudah punya wip_warehouse_id -> pakai itu
        $warehouse = null;
        if (!empty($job->wip_warehouse_id)) {
            $warehouse = Warehouse::find($job->wip_warehouse_id);
        }

        // ✅ 2) fallback aman: cari gudang WIP by code (JANGAN fallback ke warehouse job)
        if (!$warehouse) {
            $warehouse = Warehouse::where('code', 'WIP-CUT')->first(); // <- sesuaikan kode
        }

        // ✅ 3) kalau tetap tidak ketemu -> hard error biar gak salah masuk RM
        abort_if(!$warehouse, 422, 'Gudang WIP target belum diset. Set wip_warehouse_id pada cutting job atau buat gudang code WIP-CUT.');

        $warehouseId = (int) $warehouse->id;

        $lines = $job->bundles
            ->groupBy('finished_item_id')
            ->map(function ($bundles, $itemId) {
                $item = $bundles->first()?->finishedItem;

                return [
                    'item_id' => (int) $itemId,
                    'item_code' => $item?->code ?? '',
                    'item_name' => $item?->name ?? '',
                    'suggest_qty_in' => 0,
                    'notes' => null,
                ];
            })
            ->values();

        // optional: kalau mau owner bisa pilih gudang (tapi tetap default WIP)
        $warehouses = Warehouse::orderBy('code')->get();

        return view('inventory.adjustments.cutting_overproduction.create', [
            'job' => $job,
            'warehouseId' => $warehouseId,
            'warehouse' => $warehouse, // ✅ buat blade tampil kode/nama
            'warehouses' => $warehouses, // ✅ kalau mau dropdown owner
            'lines' => $lines,
        ]);
    }

    public function cuttingOverproductionStore(Request $request): RedirectResponse
    {
        abort_unless(in_array(auth()->user()->role ?? null, ['owner', 'admin']), 403);

        $validated = $request->validate([
            'cutting_job_id' => ['required', 'integer'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.lot_id' => ['nullable', 'exists:lots,id'],
            'lines.*.qty_in' => ['required', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $userId = auth()->id();

        $adjustment = DB::transaction(function () use ($validated, $userId) {

            $adj = new InventoryAdjustment();
            $adj->code = $this->generateCodeForDate($validated['date']);
            $adj->warehouse_id = (int) $validated['warehouse_id'];
            $adj->date = $validated['date'];
            $adj->reason = 'Cutting Overproduction Adjustment';
            $adj->notes = $validated['notes'] ?? null;

            // biar sesuai route post() kamu: draft dulu
            $adj->status = InventoryAdjustment::STATUS_DRAFT;

            $adj->created_by = $userId;

            // optional: simpan relasi sumber ke cutting job
            $adj->source_type = \App\Models\CuttingJob::class;
            $adj->source_id = (int) $validated['cutting_job_id'];

            $adj->save();

            foreach ($validated['lines'] as $l) {
                $qtyIn = (float) $l['qty_in'];
                if ($qtyIn <= 0) {
                    continue;
                }

                $adj->lines()->create([
                    'item_id' => (int) $l['item_id'],
                    'qty_before' => null,
                    'qty_after' => null,
                    'qty_change' => $qtyIn,
                    'direction' => 'in',
                    'notes' => $l['notes'] ?? null,
                    'lot_id' => null,
                ]);
            }

            return $adj;
        });

        return redirect()
            ->route('inventory.adjustments.show', ['inventoryAdjustment' => $adjustment->getKey()])
            ->with('status', 'success')
            ->with('message', 'Overproduction Adjustment berhasil dibuat (draft). Silakan POST untuk eksekusi.');
    }

    public function cuttingOverproductionPost(InventoryAdjustment $adjustment): RedirectResponse
    {
        // reuse method post() yang sudah ada
        return $this->post($adjustment);
    }

// kalau route kamu ada void:
    public function cuttingOverproductionVoid(InventoryAdjustment $adjustment): RedirectResponse
    {
        abort_unless(in_array(auth()->user()->role ?? null, ['owner', 'admin']), 403);

        abort_unless(in_array($adjustment->status, ['draft', 'pending'], true), 422);

        $adjustment->status = InventoryAdjustment::STATUS_VOID;
        $adjustment->save();

        return back()->with('success', 'Adjustment di-VOID.');
    }

}
