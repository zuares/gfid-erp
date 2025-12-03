<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryStock;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
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
     */
    public function index(Request $request): View
    {
        $query = InventoryAdjustment::query()
            ->with(['warehouse', 'creator', 'approver'])
            ->withCount('lines')
            ->orderByDesc('date')
            ->orderByDesc('id');

        // Filter gudang
        if ($request->filled('warehouse_id')) {
            $query->where('warehouse_id', $request->integer('warehouse_id'));
        }

        // Filter status
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            $query->where('status', $status);
        }

        // Filter tanggal (opsional)
        if ($request->filled('date_from')) {
            $query->whereDate('date', '>=', $request->date('date_from'));
        }
        if ($request->filled('date_to')) {
            $query->whereDate('date', '<=', $request->date('date_to'));
        }

        // Search code / reason / notes
        if ($request->filled('q')) {
            $q = $request->string('q')->toString();
            $query->where(function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('reason', 'like', '%' . $q . '%')
                    ->orWhere('notes', 'like', '%' . $q . '%');
            });
        }

        $adjustments = $query->paginate(25)->appends($request->query());

        $warehouses = Warehouse::orderBy('name')->get();

        $filters = [
            'warehouse_id' => $request->input('warehouse_id'),
            'status' => $request->input('status'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
            'q' => $request->input('q'),
        ];

        return view('inventory.adjustments.index', compact(
            'adjustments',
            'warehouses',
            'filters'
        ));
    }

    /**
     * Detail 1 dokumen adjustment
     */
    public function show(InventoryAdjustment $inventoryAdjustment): View
    {
        $inventoryAdjustment->load([
            'warehouse',
            'lines.item',
            'creator',
            'approver',
        ]);

        return view('inventory.adjustments.show', [
            'adjustment' => $inventoryAdjustment,
        ]);
    }

    /**
     * Form Adjustment Manual (buat koreksi stok manual)
     * Versi: list item + search + qty_change / physical (di Blade kita kirim qty_change hidden).
     */
    public function createManual(): View
    {
        $warehouses = Warehouse::orderBy('code')->get();

        return view('inventory.adjustments.manual_create', compact('warehouses'));
    }

    /**
     * Simpan Adjustment Manual + update stok via InventoryService
     */
    public function storeManual(Request $request, InventoryService $inventory)
    {
        $validated = $request->validate([
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'date' => ['required', 'date'],
            'reason' => ['nullable', 'string', 'max:255'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            // qty_change sudah dihitung di JS (physical_qty - on_hand) → boleh + / -
            'lines.*.qty_change' => ['required', 'numeric'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $userId = $request->user()?->id;

        $adjustment = DB::transaction(function () use ($validated, $userId, $inventory) {
            // ================= HEADER DOKUMEN =================
            $adjustment = new InventoryAdjustment();
            $adjustment->code = $this->generateCode();
            $adjustment->warehouse_id = $validated['warehouse_id'];
            $adjustment->date = $validated['date'];
            $adjustment->reason = $validated['reason'] ?? 'Adjustment Manual';
            $adjustment->notes = $validated['notes'] ?? null;
            $adjustment->status = 'approved';
            $adjustment->created_by = $userId;
            $adjustment->approved_by = $userId;
            $adjustment->approved_at = now();
            $adjustment->save();

            // ================= DETAIL LINES =================
            foreach ($validated['lines'] as $lineData) {
                $itemId = (int) $lineData['item_id'];
                $rawChange = (float) $lineData['qty_change']; // bisa + / -

                // skip baris tanpa perubahan
                if (abs($rawChange) < 0.000001) {
                    continue;
                }

                // stok sebelum adjustment
                $qtyBefore = $inventory->getOnHandQty(
                    warehouseId: $validated['warehouse_id'],
                    itemId: $itemId
                );

                // apply perubahan stok via InventoryService → adjustByDifference
                $mutation = $inventory->adjustByDifference(
                    warehouseId: $validated['warehouse_id'],
                    itemId: $itemId,
                    qtyChange: $rawChange, // langsung signed
                    date: $validated['date'],
                    sourceType: InventoryAdjustment::class,
                    sourceId: $adjustment->id,
                    notes: $lineData['notes'] ?? $adjustment->reason,
                    lotId: null,
                    allowNegative: false,
                    unitCostOverride: null,
                    affectLotCost: false, // manual ADJ biasanya tidak ubah LotCost
                );

                // kalau karena suatu hal tidak ada mutasi → skip (defensive)
                if (!$mutation) {
                    continue;
                }

                // stok sesudah adjustment
                $qtyAfter = $inventory->getOnHandQty(
                    warehouseId: $validated['warehouse_id'],
                    itemId: $itemId
                );

                $direction = $rawChange >= 0 ? 'in' : 'out';

                // Buat baris adjustment (qty_change disimpan SIGNED)
                $adjustment->lines()->create([
                    'item_id' => $itemId,
                    'qty_before' => $qtyBefore,
                    'qty_after' => $qtyAfter,
                    'qty_change' => $rawChange, // boleh plus/minus
                    'direction' => $direction,
                    'notes' => $lineData['notes'] ?? null,
                    // kalau nanti pakai per LOT:
                    // 'lot_id'  => $someLotId,
                ]);
            }

            return $adjustment;
        });

        return redirect()
            ->route('inventory.adjustments.show', $adjustment)
            ->with('success', 'Adjustment Manual berhasil dibuat.');
    }

    /**
     * Endpoint AJAX: ambil daftar item yang punya stok di gudang tertentu
     * GET /inventory/adjustments/items?warehouse_id=xx&q=KODE
     */
    public function itemsForWarehouse(Request $request)
    {
        $warehouseId = $request->integer('warehouse_id');

        if (!$warehouseId) {
            return response()->json([], 400);
        }

        $q = trim((string) $request->get('q', ''));

        $query = InventoryStock::query()
            ->with('item')
            ->where('warehouse_id', $warehouseId)
            ->where('qty', '!=', 0); // kalau mau tampil semua item, hapus kondisi ini

        if ($q !== '') {
            $query->whereHas('item', function ($sub) use ($q) {
                $sub->where('code', 'like', '%' . $q . '%')
                    ->orWhere('name', 'like', '%' . $q . '%');
            });
        }

        $rows = $query
            ->orderBy('item_id')
            ->limit(500)
            ->get();

        $data = $rows->map(function (InventoryStock $row) {
            return [
                'id' => $row->item_id,
                'code' => $row->item?->code ?? '',
                'name' => $row->item?->name ?? '',
                'on_hand' => (float) $row->qty,
            ];
        });

        return response()->json($data);
    }

    /**
     * Generate kode dokumen ADJ-YYYYMMDD-###
     */
    protected function generateCode(): string
    {
        $date = now()->format('Ymd');

        $countToday = InventoryAdjustment::whereDate('date', now()->toDateString())->count();
        $seq = str_pad((string) ($countToday + 1), 3, '0', STR_PAD_LEFT);

        return "ADJ-{$date}-{$seq}";
    }
}
