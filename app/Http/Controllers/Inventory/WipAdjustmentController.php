<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\InventoryAdjustment;
use App\Models\InventoryMutation;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class WipAdjustmentController extends Controller
{
    /* ============================================================
     * INDEX
     * View: resources/views/inventory/wip_adjustments/index.blade.php
     * Route: inventory.wip_adjustments.index
     * ============================================================ */
    public function index(Request $request): View
    {
        $query = InventoryAdjustment::query()
            ->with(['warehouse', 'creator', 'approver'])
            ->withCount('lines')
            ->where('purpose', 'wip')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($warehouseId = $request->integer('warehouse_id')) {
            $query->where('warehouse_id', $warehouseId);
        }

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

        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', "%{$q}%")
                    ->orWhere('reason', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            });
        }

        $adjustments = $query->paginate(25)->withQueryString();

        $warehouses = Warehouse::query()
            ->where('code', 'like', 'WIP-%')
            ->orderBy('code')
            ->get();

        $filters = [
            'warehouse_id' => $request->input('warehouse_id'),
            'status' => $request->input('status', 'all'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'q' => $request->input('q'),
        ];

        return view('inventory.wip_adjustments.index', compact(
            'adjustments',
            'warehouses',
            'filters'
        ));
    }

    /* ============================================================
     * CREATE
     * View: resources/views/inventory/wip_adjustments/create.blade.php
     * ============================================================ */
    public function create(): View
    {
        $warehouses = Warehouse::query()
            ->where('code', 'like', 'WIP-%')
            ->orderBy('code')
            ->get();

        return view('inventory.wip_adjustments.create', compact('warehouses'));
    }

    /* ============================================================
     * AJAX: Items list for selected warehouse (item-level)
     * Route: inventory.wip_adjustments.items
     * Return: [{id, code, name, on_hand}]
     * ============================================================ */
    public function items(Request $request): JsonResponse
    {
        $warehouseId = (int) $request->get('warehouse_id', 0);
        if ($warehouseId <= 0) {
            return response()->json([]);
        }

        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse || !str_starts_with((string) $warehouse->code, 'WIP-')) {
            return response()->json([]);
        }

        $q = trim((string) $request->get('q', ''));

        // 1) Kalau inventory_stocks ada & kolom qty_on_hand ada → pakai itu (lebih cepat)
        if (Schema::hasTable('inventory_stocks') && Schema::hasColumn('inventory_stocks', 'qty_on_hand')) {
            $rows = DB::table('inventory_stocks as s')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('s.warehouse_id', $warehouseId)
                ->when($q !== '', function ($qq) use ($q) {
                    $term = '%' . $q . '%';
                    $qq->where(function ($w) use ($term) {
                        $w->where('i.code', 'like', $term)
                            ->orWhere('i.name', 'like', $term);
                    });
                })
                ->when($q === '', fn($qq) => $qq->where('s.qty_on_hand', '!=', 0))
                ->orderBy('i.code')
                ->limit(250)
                ->get(['i.id', 'i.code', 'i.name', 's.qty_on_hand']);

            return response()->json(
                $rows->map(fn($r) => [
                    'id' => (int) $r->id,
                    'code' => (string) $r->code,
                    'name' => (string) ($r->name ?? ''),
                    'on_hand' => (float) ($r->qty_on_hand ?? 0),
                ])->values()
            );
        }

        // 2) Fallback: hitung on_hand dari inventory_mutations
        // NOTE: pastikan nama table mutation kamu: inventory_mutations (kamu sudah pakai itu di show)
        $rows = DB::table('inventory_mutations as m')
            ->join('items as i', 'i.id', '=', 'm.item_id')
            ->where('m.warehouse_id', $warehouseId)
            ->when($q !== '', function ($qq) use ($q) {
                $term = '%' . $q . '%';
                $qq->where(function ($w) use ($term) {
                    $w->where('i.code', 'like', $term)
                        ->orWhere('i.name', 'like', $term);
                });
            })
            ->groupBy('m.item_id', 'i.code', 'i.name')
            ->selectRaw('m.item_id as id, i.code, i.name, COALESCE(SUM(m.qty_change),0) as on_hand')
            ->havingRaw($q === '' ? 'COALESCE(SUM(m.qty_change),0) != 0' : '1=1')
            ->orderBy('i.code')
            ->limit(250)
            ->get();

        return response()->json(
            $rows->map(fn($r) => [
                'id' => (int) $r->id,
                'code' => (string) $r->code,
                'name' => (string) ($r->name ?? ''),
                'on_hand' => (float) ($r->on_hand ?? 0),
            ])->values()
        );
    }

    /* ============================================================
     * AJAX: Bundles list for WIP-CUT
     * Route: inventory.wip_adjustments.bundles
     * Return: [{id, bundle_code, item_id, item_code, item_name, wip_qty, sewing_picked_qty, remaining}]
     * ============================================================ */
    public function bundles(Request $request): JsonResponse
    {
        $warehouseId = (int) $request->get('warehouse_id', 0);
        if ($warehouseId <= 0) {
            return response()->json([]);
        }

        $warehouse = Warehouse::find($warehouseId);
        if (!$warehouse || (string) $warehouse->code !== 'WIP-CUT') {
            return response()->json([]);
        }

        $q = trim((string) $request->get('q', ''));

        $bundles = CuttingJobBundle::query()
            ->with(['finishedItem:id,code,name'])
            ->where('wip_warehouse_id', $warehouseId)
            ->when($q !== '', function ($qq) use ($q) {
                $term = '%' . $q . '%';
                $qq->where(function ($w) use ($term) {
                    $w->where('bundle_code', 'like', $term)
                        ->orWhereHas('finishedItem', function ($q2) use ($term) {
                            $q2->where('code', 'like', $term)
                                ->orWhere('name', 'like', $term);
                        });
                });
            })
            ->orderByDesc('id')
            ->limit(250)
            ->get();

        $out = $bundles->map(function (CuttingJobBundle $b) {
            $wip = (float) ($b->wip_qty ?? 0);
            $picked = (float) ($b->sewing_picked_qty ?? 0);
            $rem = max($wip - $picked, 0);
            return [
                'id' => (int) $b->id,
                'bundle_code' => (string) $b->bundle_code,
                'item_id' => (int) $b->finished_item_id,
                'item_code' => (string) ($b->finishedItem?->code ?? ''),
                'item_name' => (string) ($b->finishedItem?->name ?? ''),
                'wip_qty' => $wip,
                'sewing_picked_qty' => $picked,
                'remaining' => $rem,
            ];
        })->values();

        return response()->json($out);
    }

    /* ============================================================
     * STORE (bundle-aware for WIP-CUT)
     * Route: inventory.wip_adjustments.store
     * ============================================================ */
    public function store(Request $request, InventoryService $inventory): RedirectResponse
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'reason' => ['required', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_change' => ['required', 'numeric', 'not_in:0'],
            'lines.*.notes' => ['nullable', 'string'],

            // bundle-aware: optional, but will be REQUIRED for WIP-CUT (we enforce below)
            'lines.*.cutting_job_bundle_id' => ['nullable', 'exists:cutting_job_bundles,id'],
        ], [
            'lines.required' => 'Minimal 1 item harus ada selisih.',
            'lines.min' => 'Minimal 1 item harus ada selisih.',
        ]);

        $warehouse = Warehouse::findOrFail((int) $validated['warehouse_id']);
        if (!str_starts_with((string) $warehouse->code, 'WIP-')) {
            return back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', 'Gudang harus WIP-* untuk WIP Adjustment.');
        }

        $isWipCut = ((string) $warehouse->code === 'WIP-CUT');

        // Enforce: WIP-CUT harus bundle-based (minimal 1 line, dan setiap line wajib punya bundle_id)
        if ($isWipCut) {
            foreach ($validated['lines'] as $i => $line) {
                if (empty($line['cutting_job_bundle_id'])) {
                    throw ValidationException::withMessages([
                        "lines.{$i}.cutting_job_bundle_id" => 'Untuk WIP-CUT, wajib pilih Bundle.',
                    ]);
                }
            }
        }

        $user = $request->user();
        $role = $user?->role ?? null;
        $autoApprove = in_array($role, ['owner', 'admin'], true);

        $adjustment = DB::transaction(function () use ($validated, $inventory, $warehouse, $user, $autoApprove, $isWipCut) {
            $adj = new InventoryAdjustment();
            $adj->code = $this->generateCodeForDate($validated['date']);
            $adj->warehouse_id = (int) $warehouse->id;
            $adj->date = $validated['date'];
            $adj->reason = $validated['reason'];
            $adj->notes = $validated['notes'] ?? null;

            $adj->purpose = 'wip';
            $adj->wip_stage = null;
            $adj->reference_type = null;
            $adj->reference_id = null;

            $adj->source_type = null;
            $adj->source_id = null;

            $adj->status = $autoApprove
            ? InventoryAdjustment::STATUS_APPROVED
            : InventoryAdjustment::STATUS_PENDING;

            $adj->created_by = $user?->id;

            if ($autoApprove) {
                $adj->approved_by = $user?->id;
                $adj->approved_at = now();
            }

            $adj->save();

            $epsilon = 0.000001;

            foreach ($validated['lines'] as $lineData) {
                $itemId = (int) $lineData['item_id'];
                $signed = (float) $lineData['qty_change'];

                if (abs($signed) < $epsilon) {
                    continue;
                }

                $bundleId = !empty($lineData['cutting_job_bundle_id']) ? (int) $lineData['cutting_job_bundle_id'] : null;

                // WIP-CUT: item harus match bundle.finished_item_id
                $bundle = null;
                if ($isWipCut) {
                    $bundle = CuttingJobBundle::lockForUpdate()->findOrFail((int) $bundleId);

                    if ((int) $bundle->wip_warehouse_id !== (int) $warehouse->id) {
                        throw ValidationException::withMessages([
                            'lines' => "Bundle {$bundle->bundle_code} tidak berada di gudang {$warehouse->code}.",
                        ]);
                    }

                    if ((int) $bundle->finished_item_id !== (int) $itemId) {
                        throw ValidationException::withMessages([
                            'lines' => "Item tidak sesuai bundle {$bundle->bundle_code}. (Harus mengikuti finished item bundle)",
                        ]);
                    }
                }

                $qtyBefore = null;
                $qtyAfter = null;

                if ($autoApprove) {
                    $qtyBefore = $inventory->getOnHandQty(
                        warehouseId: (int) $adj->warehouse_id,
                        itemId: $itemId
                    );

                    $inventory->adjustByDifference(
                        warehouseId: (int) $adj->warehouse_id,
                        itemId: $itemId,
                        qtyChange: $signed,
                        date: Carbon::parse($adj->date)->toDateString(),
                        sourceType: InventoryAdjustment::class,
                        sourceId: $adj->id,
                        notes: $lineData['notes'] ?? $adj->reason,
                        lotId: null,
                        allowNegative: false,
                        unitCostOverride: null,
                        affectLotCost: false
                    );

                    $qtyAfter = $inventory->getOnHandQty(
                        warehouseId: (int) $adj->warehouse_id,
                        itemId: $itemId
                    );

                    // ✅ Bundle-aware sync (BIAR PRODUKSI NGIKUT)
                    if ($isWipCut && $bundle) {
                        $newWip = max(((float) $bundle->wip_qty) + $signed, 0.0);

                        // jangan sampai sewing_picked_qty > wip_qty
                        $picked = (float) ($bundle->sewing_picked_qty ?? 0);
                        if ($picked > $newWip) {
                            $bundle->sewing_picked_qty = $newWip;
                        }

                        $bundle->wip_qty = $newWip;
                        $bundle->save();
                    }
                }

                $adj->lines()->create([
                    'item_id' => $itemId,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $signed,
                    'direction' => $signed >= 0 ? 'in' : 'out',
                    'notes' => $lineData['notes'] ?? null,
                    'lot_id' => null,

                    // ✅ bundle reference
                    'cutting_job_bundle_id' => $bundleId,
                ]);
            }

            return $adj;
        });

        return redirect()
            ->route('inventory.wip_adjustments.show', $adjustment)
            ->with('status', 'success')
            ->with('message', $autoApprove
                ? 'WIP Adjustment berhasil dibuat & stok sudah berubah.'
                : 'WIP Adjustment berhasil dibuat (Pending). Stok berubah setelah di-approve.');
    }

    /* ============================================================
     * SHOW
     * View: resources/views/inventory/wip_adjustments/show.blade.php
     * ============================================================ */
    public function show(InventoryAdjustment $inventoryAdjustment): View
    {
        abort_unless(($inventoryAdjustment->purpose ?? null) === 'wip', 404);

        $inventoryAdjustment->load([
            'warehouse',
            'creator',
            'approver',
            'lines' => fn($q) => $q->with('item')->orderBy('id'),
        ]);

        $totalInQty = (float) $inventoryAdjustment->lines->where('direction', 'in')->sum('qty_change');
        $totalOutQtyAbs = abs((float) $inventoryAdjustment->lines->where('direction', 'out')->sum('qty_change'));

        $totalInValue = 0.0;
        $totalOutValueAbs = 0.0;
        $netValue = 0.0;
        $hasValue = false;

        if ($inventoryAdjustment->status === InventoryAdjustment::STATUS_APPROVED) {
            $mutations = InventoryMutation::query()
                ->where('source_type', InventoryAdjustment::class)
                ->where('source_id', $inventoryAdjustment->id)
                ->orderBy('id')
                ->get();

            $totalInValue = (float) $mutations->where('direction', 'in')->sum('total_cost');
            $totalOutValueAbs = abs((float) $mutations->where('direction', 'out')->sum('total_cost'));
            $netValue = $totalInValue - $totalOutValueAbs;
            $hasValue = $mutations->count() > 0;
        }

        return view('inventory.wip_adjustments.show', [
            'adjustment' => $inventoryAdjustment,
            'summary' => [
                'total_in_qty' => $totalInQty,
                'total_out_qty_abs' => $totalOutQtyAbs,
                'total_in_value' => $totalInValue,
                'total_out_value' => $totalOutValueAbs,
                'net_value' => $netValue,
                'has_value' => $hasValue,
            ],
        ]);
    }

    /* ============================================================
     * APPROVE (Owner/Admin)
     * - Eksekusi stok untuk dokumen pending
     * - Update bundle.wip_qty untuk WIP-CUT
     * ============================================================ */
    public function approve(Request $request, InventoryAdjustment $inventoryAdjustment, InventoryService $inventory): RedirectResponse
    {
        abort_unless(($inventoryAdjustment->purpose ?? null) === 'wip', 404);

        $user = $request->user();
        $role = $user?->role ?? null;

        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya Owner/Admin yang boleh approve WIP Adjustment.');
        }

        DB::transaction(function () use ($inventoryAdjustment, $inventory, $user) {

            // 🔒 LOCK HEADER (anti double approve / race)
            $inventoryAdjustment = InventoryAdjustment::query()
                ->whereKey($inventoryAdjustment->id)
                ->lockForUpdate()
                ->firstOrFail();

            // idempotent no-op
            if (!$inventoryAdjustment->canApprove()) {
                return;
            }

            $inventoryAdjustment->load(['lines.item', 'warehouse']);

            $warehouse = $inventoryAdjustment->warehouse;
            if (!$warehouse || !str_starts_with((string) $warehouse->code, 'WIP-')) {
                abort(422, 'Gudang bukan WIP-*');
            }

            $isWipCut = ((string) $warehouse->code === 'WIP-CUT');

            $warehouseId = (int) $inventoryAdjustment->warehouse_id;
            $date = $inventoryAdjustment->date?->toDateString() ?? now()->toDateString();
            $epsilon = 0.000001;

            foreach ($inventoryAdjustment->lines as $line) {
                $signed = (float) ($line->qty_change ?? 0);
                if (abs($signed) < $epsilon) {
                    continue;
                }

                // =========================
                // WIP-CUT (BUNDLE-AWARE)
                // =========================
                if ($isWipCut) {
                    $bundleId = (int) ($line->cutting_job_bundle_id ?? 0);
                    if ($bundleId <= 0) {
                        throw ValidationException::withMessages([
                            'lines' => 'WIP-CUT adjustment line wajib punya bundle.',
                        ]);
                    }

                    // 🔒 lock bundle
                    $bundle = CuttingJobBundle::query()->lockForUpdate()->findOrFail($bundleId);

                    if ((int) $bundle->wip_warehouse_id !== (int) $warehouseId) {
                        throw ValidationException::withMessages([
                            'lines' => "Bundle {$bundle->bundle_code} tidak berada di gudang {$warehouse->code}.",
                        ]);
                    }

                    // kamu pakai finished_item_id, ok
                    if ((int) $bundle->finished_item_id !== (int) $line->item_id) {
                        throw ValidationException::withMessages([
                            'lines' => "Item tidak sesuai bundle {$bundle->bundle_code}.",
                        ]);
                    }

                    // 🔒 lock stock row per bundle (ref)
                    $stock = InventoryStock::query()
                        ->where('warehouse_id', $warehouseId)
                        ->where('item_id', (int) $line->item_id)
                        ->where('ref_type', CuttingJobBundle::class) // atau 'CuttingJobBundle' sesuai implementasi kamu
                        ->where('ref_id', $bundle->id)
                        ->lockForUpdate()
                        ->first();

                    if (!$stock) {
                        $stock = InventoryStock::create([
                            'warehouse_id' => $warehouseId,
                            'item_id' => (int) $line->item_id,
                            'ref_type' => CuttingJobBundle::class, // samakan konsisten
                            'ref_id' => $bundle->id,
                            'qty' => 0,
                        ]);

                        // lock row yg baru dibuat (optional safety)
                        $stock = InventoryStock::query()->whereKey($stock->id)->lockForUpdate()->first();
                    }

                    $qtyBefore = (float) $stock->qty;

                    $newQty = $qtyBefore + $signed;
                    if ($newQty < 0) {
                        throw ValidationException::withMessages([
                            'lines' => "Stok WIP-CUT negatif untuk bundle {$bundle->bundle_code}.",
                        ]);
                    }

                    $stock->qty = $newQty;
                    $stock->save();

                    $qtyAfter = (float) $stock->qty;

                    // ✅ MIRROR: bundle.wip_qty mengikuti stok ref bundle (anti mismatch)
                    $bundle->wip_qty = $qtyAfter;

                    // clamp picked kalau kamu butuh
                    $picked = (float) ($bundle->sewing_picked_qty ?? 0);
                    if ($picked > $qtyAfter) {
                        $bundle->sewing_picked_qty = $qtyAfter;
                    }

                    $bundle->save();

                    // simpan before/after line sesuai bundle-stock
                    $line->qty_before = $qtyBefore;
                    $line->qty_after = $qtyAfter;
                    $line->direction = $signed >= 0 ? 'in' : 'out';
                    $line->save();

                    continue; // penting: jangan jatuh ke adjust global
                }

                // =========================
                // WIP-* lainnya (GLOBAL OK)
                // =========================
                $qtyBefore = $inventory->getOnHandQty(
                    warehouseId: $warehouseId,
                    itemId: (int) $line->item_id
                );

                $inventory->adjustByDifference(
                    warehouseId: $warehouseId,
                    itemId: (int) $line->item_id,
                    qtyChange: $signed,
                    date: $date,
                    sourceType: InventoryAdjustment::class,
                    sourceId: $inventoryAdjustment->id,
                    notes: $line->notes ?? $inventoryAdjustment->reason,
                    lotId: null,
                    allowNegative: false,
                    unitCostOverride: null,
                    affectLotCost: false
                );

                $qtyAfter = $inventory->getOnHandQty(
                    warehouseId: $warehouseId,
                    itemId: (int) $line->item_id
                );

                $line->qty_before = $qtyBefore;
                $line->qty_after = $qtyAfter;
                $line->direction = $signed >= 0 ? 'in' : 'out';
                $line->save();
            }

            $inventoryAdjustment->status = InventoryAdjustment::STATUS_APPROVED;
            $inventoryAdjustment->approved_by = $user->id;
            $inventoryAdjustment->approved_at = now();
            $inventoryAdjustment->save();
        });

        return redirect()
            ->route('inventory.wip_adjustments.show', $inventoryAdjustment)
            ->with('status', 'success')
            ->with('message', 'WIP Adjustment berhasil di-approve dan stok sudah dikoreksi.');
    }

    /* ============================================================
     * HELPERS
     * ============================================================ */

    private function generateCodeForDate(string $date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');

        $count = InventoryAdjustment::query()
            ->where('purpose', 'wip')
            ->whereDate('date', Carbon::parse($date)->toDateString())
            ->count();

        $seq = str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);

        return "WADJ-{$ymd}-{$seq}";
    }
}
