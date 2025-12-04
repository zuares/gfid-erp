<?php

namespace App\Http\Controllers\Costing;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\Warehouse;
use App\Services\Costing\FgHppAutoService;
use Illuminate\Http\Request;

class HppController extends Controller
{
    public function __construct(
        protected FgHppAutoService $fgHpp,
    ) {}

    /**
     * Halaman index:
     * - form generate HPP
     * - filter history snapshot
     * - tabel snapshot
     */
    public function index(Request $request)
    {
        // Dropdown item FG
        $items = Item::query()
            ->where('type', 'finished_good') // sesuaikan kalau field/type beda
            ->orderBy('code')
            ->get();

        // Dropdown gudang (misal WH-RTS dll)
        $warehouses = Warehouse::orderBy('code')->get();

        // Kumpulkan filter dari query string
        $filters = [
            'item_id' => $request->input('item_id'),
            'warehouse_id' => $request->input('warehouse_id'),
            'date_from' => $request->input('date_from'),
            'date_to' => $request->input('date_to'),
        ];

        $query = ItemCostSnapshot::query()
            ->with(['item', 'warehouse', 'creator'])
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id');

        // Filter item
        if (!empty($filters['item_id'])) {
            $query->where('item_id', $filters['item_id']);
        }

        // Filter gudang
        if (!empty($filters['warehouse_id'])) {
            $query->where('warehouse_id', $filters['warehouse_id']);
        }

        // Filter tanggal
        if (!empty($filters['date_from'])) {
            $query->whereDate('snapshot_date', '>=', $filters['date_from']);
        }

        if (!empty($filters['date_to'])) {
            $query->whereDate('snapshot_date', '<=', $filters['date_to']);
        }

        // Pagination
        $snapshots = $query->paginate(25)->withQueryString();

        return view('costing.hpp.index', [
            'items' => $items,
            'warehouses' => $warehouses,
            'snapshots' => $snapshots,
            'filters' => $filters,
        ]);
    }

    /**
     * Proses generate snapshot HPP baru berdasarkan periode.
     * Route: POST costing.hpp.generate
     */
    public function generate(Request $request)
    {
        $data = $request->validate([
            'item_id' => ['required', 'exists:items,id'],
            'warehouse_id' => ['nullable', 'exists:warehouses,id'],
            'date_from' => ['required', 'date'],
            'date_to' => ['required', 'date', 'after_or_equal:date_from'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $itemId = (int) $data['item_id'];
        $warehouseId = $data['warehouse_id'] ? (int) $data['warehouse_id'] : null;
        $dateFrom = $data['date_from'];
        $dateTo = $data['date_to'];
        $notes = $data['notes'] ?? null;

        // Panggil service sesuai definisi yg kamu kirim:
        // calculateAndSnapshotForFinishedItem(
        //     int $itemId, string $dateFrom, string $dateTo,
        //     ?int $warehouseId = null, ?string $notes = null
        // )
        $snapshot = $this->fgHpp->calculateAndSnapshotForFinishedItem(
            itemId: $itemId,
            dateFrom: $dateFrom,
            dateTo: $dateTo,
            warehouseId: $warehouseId,
            notes: $notes,
        );

        return redirect()
            ->route('costing.hpp.index', [
                'item_id' => $itemId,
                'warehouse_id' => $warehouseId,
            ])
            ->with('success', 'Snapshot HPP berhasil digenerate.');
    }

    /**
     * Set satu snapshot sebagai HPP aktif untuk item (dan opsional per gudang).
     * Route: POST costing.hpp.set_active
     */
    public function setActive(ItemCostSnapshot $snapshot, Request $request)
    {
        // Matikan semua snapshot lain untuk item yang sama (dan gudang yang sama kalau diisi)
        ItemCostSnapshot::where('item_id', $snapshot->item_id)
            ->when($snapshot->warehouse_id, function ($q) use ($snapshot) {
                $q->where('warehouse_id', $snapshot->warehouse_id);
            })
            ->update(['is_active' => false]);

        // Set yang ini jadi aktif
        $snapshot->update([
            'is_active' => true,
        ]);

        return back()->with('success', 'Snapshot HPP berhasil di-set sebagai HPP aktif.');
    }
}
