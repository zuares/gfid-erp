<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\StockOpname;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class InventoryAdjustmentController extends Controller
{
    public function __construct()
    {
        // $this->middleware('auth');
    }

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

        // ========= SUMMARY QTY =========
        $totalInQty = (float) $inventoryAdjustment->lines->where('direction', 'in')->sum('qty_change');
        $totalOutQtyAbs = abs((float) $inventoryAdjustment->lines->where('direction', 'out')->sum('qty_change'));

        // ========= (A) APPROVED -> ambil nilai dari MUTATIONS (LOCKED) =========
        if ($inventoryAdjustment->status === InventoryAdjustment::STATUS_APPROVED) {
            $mutations = InventoryMutation::query()
                ->where('source_type', InventoryAdjustment::class)
                ->where('source_id', $inventoryAdjustment->id)
                ->orderBy('id')
                ->get();

            $totalInValue = (float) $mutations->where('direction', 'in')->sum('total_cost'); // positif
            $totalOutValueAbs = abs((float) $mutations->where('direction', 'out')->sum('total_cost')); // total_cost out negatif
            $netValue = $totalInValue - $totalOutValueAbs;

            $mutByItem = $mutations->groupBy('item_id');

            $lineTotals = [];
            foreach ($inventoryAdjustment->lines as $line) {
                $muts = $mutByItem->get($line->item_id, collect());

                $itemTotalCostAbs = abs((float) $muts->sum('total_cost'));

                $lastWithCost = $muts->whereNotNull('unit_cost')->last();
                $unitCost = $lastWithCost?->unit_cost !== null ? (float) $lastWithCost->unit_cost : null;

                $lineTotals[$line->id] = [
                    'unit_cost' => $unitCost,
                    'value' => $itemTotalCostAbs,
                    'unit_cost_fmt' => ($unitCost !== null && $unitCost > 0) ? $this->formatRupiah($unitCost) : '-',
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

        // ========= (B) BELUM APPROVED -> estimasi (snapshot/base/SO unit cost) =========

        // map unit cost dari SO (opening)
        $soUnitCostByItemId = collect();
        if ($inventoryAdjustment->source_type === StockOpname::class && $inventoryAdjustment->source) {
            $inventoryAdjustment->source->loadMissing('lines');
            $soUnitCostByItemId = $inventoryAdjustment->source->lines->pluck('unit_cost', 'item_id');
        }

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
                'unit_cost_fmt' => ($unitCost !== null && $unitCost > 0) ? $this->formatRupiah($unitCost) : '-',
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
     * - Prioritas 1: SO Opening line unit_cost (kalau ada)
     * - Prioritas 2: Snapshot aktif item+warehouse
     * - Prioritas 3: Item base_unit_cost
     */
    protected function resolveUnitCostForLine($line, int $warehouseId, $soUnitCostByItemId): ?float
    {
        if ($soUnitCostByItemId && $soUnitCostByItemId->has($line->item_id)) {
            $uc = (float) $soUnitCostByItemId[$line->item_id];
            if ($uc > 0) {
                return $uc;
            }
        }

        $snap = ItemCostSnapshot::getActiveForItem($line->item_id, $warehouseId);
        if ($snap && (float) $snap->unit_cost > 0) {
            return (float) $snap->unit_cost;
        }

        if ($line->item && (float) $line->item->base_unit_cost > 0) {
            return (float) $line->item->base_unit_cost;
        }

        return null;
    }

    /**
     * Resolve unit cost yang dipakai SAAT APPROVE manual (biar mutation IN juga punya cost)
     * - Snapshot aktif
     * - base_unit_cost
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

        if ($item && (float) $item->base_unit_cost > 0) {
            return (float) $item->base_unit_cost;
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
        return view('inventory.adjustments.manual_create', compact('warehouses'));
    }

    /**
     * Simpan Adjustment Manual.
     *
     * Owner   -> status APPROVED + eksekusi stok langsung (mutasi + total_cost)
     * NonOwner-> status PENDING  + belum eksekusi stok
     */
    public function storeManual(Request $request, InventoryService $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_change' => ['required', 'numeric'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $user = $request->user();
        $userId = $user?->id;
        $isOwner = $user && (($user->role ?? null) === 'owner');

        $adjustment = DB::transaction(function () use ($validated, $userId, $inventory, $isOwner) {
            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateCodeForDate($validated['date']);
            $adjustment->warehouse_id = (int) $validated['warehouse_id'];
            $adjustment->date = $validated['date'];
            $adjustment->reason = $validated['reason'] ?? 'Adjustment Manual';
            $adjustment->notes = $validated['notes'] ?? null;
            $adjustment->status = $isOwner ? InventoryAdjustment::STATUS_APPROVED : InventoryAdjustment::STATUS_PENDING;
            $adjustment->created_by = $userId;

            if ($isOwner) {
                $adjustment->approved_by = $userId;
                $adjustment->approved_at = now();
            }

            $adjustment->save();

            foreach ($validated['lines'] as $lineData) {
                $itemId = (int) $lineData['item_id'];
                $signedChange = (float) $lineData['qty_change'];

                if (abs($signedChange) < 0.000001) {
                    continue;
                }

                $direction = $signedChange >= 0 ? 'in' : 'out';
                $qtyBefore = null;
                $qtyAfter = null;

                if ($isOwner) {
                    $qtyBefore = $inventory->getOnHandQty(
                        warehouseId: $adjustment->warehouse_id,
                        itemId: $itemId
                    );

                    // ✅ resolve cost saat eksekusi langsung (owner)
                    $item = Item::find($itemId);
                    $unitCostOverride = $this->resolveUnitCostForAdjustmentApprove(
                        itemId: $itemId,
                        warehouseId: (int) $adjustment->warehouse_id,
                        item: $item,
                        inventory: $inventory
                    );

                    $mutation = $inventory->adjustByDifference(
                        warehouseId: (int) $adjustment->warehouse_id,
                        itemId: $itemId,
                        qtyChange: $signedChange,
                        date: $adjustment->date,
                        sourceType: InventoryAdjustment::class,
                        sourceId: $adjustment->id,
                        notes: $lineData['notes'] ?? $adjustment->reason,
                        lotId: null,
                        allowNegative: false,
                        unitCostOverride: $unitCostOverride,
                        affectLotCost: false,
                    );

                    if (!$mutation) {
                        continue;
                    }

                    $qtyAfter = $inventory->getOnHandQty(
                        warehouseId: (int) $adjustment->warehouse_id,
                        itemId: $itemId
                    );
                }

                $adjustment->lines()->create([
                    'item_id' => $itemId,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $signedChange,
                    'direction' => $direction,
                    'notes' => $lineData['notes'] ?? null,
                    'lot_id' => null,
                ]);
            }

            return $adjustment;
        });

        return redirect()
            ->route('inventory.adjustments.show', $adjustment)
            ->with('status', 'success')
            ->with('message', 'Adjustment Manual berhasil dibuat.');
    }

    /**
     * APPROVE dokumen Adjustment (khusus Owner).
     */
    public function approve(Request $request, InventoryAdjustment $inventoryAdjustment, InventoryService $inventory): RedirectResponse
    {
        $user = $request->user();

        if (!$user || ($user->role ?? null) !== 'owner') {
            abort(403, 'Hanya Owner yang boleh approve adjustment.');
        }

        if (!$inventoryAdjustment->canApprove()) {
            return redirect()
                ->route('inventory.adjustments.show', $inventoryAdjustment)
                ->with('status', 'error')
                ->with('message', 'Hanya dokumen dengan status pending yang bisa di-approve.');
        }

        DB::transaction(function () use ($inventoryAdjustment, $inventory, $user) {
            $inventoryAdjustment->load(['lines.item', 'source']);

            $warehouseId = (int) $inventoryAdjustment->warehouse_id;
            $date = $inventoryAdjustment->date?->toDateString() ?? now()->toDateString();

            if ($inventoryAdjustment->source_type === StockOpname::class && $inventoryAdjustment->source_id) {
                $this->approveFromStockOpname($inventoryAdjustment, $inventory, $warehouseId, $date);
            } else {
                $this->approveManualAdjustment($inventoryAdjustment, $inventory, $warehouseId, $date);
            }

            $inventoryAdjustment->status = InventoryAdjustment::STATUS_APPROVED;
            $inventoryAdjustment->approved_by = $user->id;
            $inventoryAdjustment->approved_at = now();
            $inventoryAdjustment->save();
        });

        return redirect()
            ->route('inventory.adjustments.show', $inventoryAdjustment)
            ->with('status', 'success')
            ->with('message', 'Adjustment berhasil di-approve dan stok sudah dikoreksi.');
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
                lotId: $line->lot_id ?? null,
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
                lotId: null,
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

            // (opsional) buat snapshot aktif + update base_unit_cost seperti yang sudah kamu punya
            // ... (biarkan kode snapshot kamu tetap)
        }
    }

    protected function approveFromStockOpnamePeriodic(
        InventoryAdjustment $adjustment,
        InventoryService $inventory,
        int $warehouseId,
        string $date,
        ?StockOpname $opname
    ): void {
        foreach ($adjustment->lines as $line) {
            if ($line->qty_after === null) {
                continue;
            }

            $itemId = (int) $line->item_id;
            $physicalQty = (float) $line->qty_after;

            $qtyBefore = $inventory->getOnHandQty(warehouseId: $warehouseId, itemId: $itemId);

            // resolve unit cost periodic
            $activeSnapshot = ItemCostSnapshot::getActiveForItem($itemId, $warehouseId);
            $unitCost = null;

            if ($activeSnapshot && (float) $activeSnapshot->unit_cost > 0) {
                $unitCost = (float) $activeSnapshot->unit_cost;
            } elseif ($line->item && (float) $line->item->base_unit_cost > 0) {
                $unitCost = (float) $line->item->base_unit_cost;
            }

            $inventory->adjustTo(
                warehouseId: $warehouseId,
                itemId: $itemId,
                newQty: $physicalQty,
                date: $date,
                sourceType: InventoryAdjustment::class,
                sourceId: $adjustment->id,
                notes: $adjustment->reason ?? ('Penyesuaian stok dari stock opname ' . ($opname?->code ?? '')),
                lotId: null,
                unitCostOverride: $unitCost,
                affectLotCost: false,
            );

            $qtyAfter = $physicalQty;
            $qtyChange = $qtyAfter - (float) $qtyBefore;

            $line->qty_before = $qtyBefore;
            $line->qty_after = $qtyAfter;
            $line->qty_change = $qtyChange;
            $line->direction = $qtyChange >= 0 ? 'in' : 'out';
            $line->save();

            // snapshot periodic (opsional; kamu sudah ada)
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
    public function itemsForWarehouse(Request $request): JsonResponse
    {
        $warehouseId = $request->integer('warehouse_id');
        if (!$warehouseId) {
            return response()->json([], 400);
        }

        $q = trim((string) $request->get('q', ''));

        $query = InventoryStock::query()
            ->with('item')
            ->where('warehouse_id', $warehouseId)
            ->where('qty', '!=', 0);

        if ($q !== '') {
            $query->whereHas('item', function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            });
        }

        $rows = $query->orderBy('item_id')->limit(500)->get();

        return response()->json(
            $rows->map(fn(InventoryStock $row) => [
                'id' => $row->item_id,
                'code' => $row->item?->code ?? '',
                'name' => $row->item?->name ?? '',
                'on_hand' => (float) $row->qty,
            ])
        );
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
}
