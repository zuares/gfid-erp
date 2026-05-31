<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemCostSnapshot;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query()
            ->withCount('barcodes')
            ->with(['costSnapshots' => function ($q) {
                $q->active()
                    ->orderByDesc('snapshot_date')
                    ->orderByDesc('id')
                    ->limit(1);
            }]);

        if ($search = $request->input('q')) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('name', 'like', '%' . $search . '%');
            });
        }

        if ($type = $request->input('type')) {
            $query->where('type', $type);
        }

        if ($categoryId = $request->input('item_category_id')) {
            $query->where('item_category_id', $categoryId);
        }

        $items = $query
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        $categories = ItemCategory::orderBy('name')->get();

        return view('master.items.index', compact('items', 'categories'));
    }

    public function create()
    {
        $item = null;

        // kalau kamu punya list kategori, lempar juga ke view:
        $categories = ItemCategory::orderBy('name')->get();
        return view('master.items.create', compact('item', 'categories'));

    }

    public function show(Item $item)
    {
        $item->load('barcodes');

        // snapshot HPP aktif (kalau ada)
        $activeSnapshot = ItemCostSnapshot::getActiveForItem($item->id, null);

        return view('master.items.show', [
            'item' => $item,
            'activeSnapshot' => $activeSnapshot,
        ]);
    }

    /**
     * Form set / edit HPP sementara dari Master Item.
     */
    public function editHppTemp(Item $item)
    {
        // snapshot aktif kalau ada
        $snapshot = ItemCostSnapshot::getActiveForItem($item->id, null);

        return view('master.items.hpp_temp', [
            'item' => $item,
            'snapshot' => $snapshot,
        ]);
    }

    /**
     * Simpan HPP sementara (buat snapshot baru dan matikan snapshot lama).
     */
    public function storeHppTemp(Request $request, Item $item)
    {
        $validated = $request->validate([
            'unit_cost' => ['required', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $now = Carbon::now();

        DB::transaction(function () use ($item, $validated, $now) {

            // Nonaktifkan snapshot aktif sebelumnya
            ItemCostSnapshot::where('item_id', $item->id)
                ->active()
                ->update(['is_active' => 0]);

            // Sinkronkan kolom items.hpp agar valuasi Inventory Stock ikut.
            // (HPP referensi/statis — tidak menyentuh Lot.avg_cost / jurnal.)
            $item->update(['hpp' => $validated['unit_cost']]);

            // Buat snapshot baru sebagai HPP sementara dari master
            ItemCostSnapshot::create([
                'item_id' => $item->id,
                'warehouse_id' => null, // global HPP
                'snapshot_date' => $now->toDateString(),
                'reference_type' => 'master_temp',
                'reference_id' => null,
                'qty_basis' => 0,
                'rm_unit_cost' => $validated['unit_cost'], // sementara samakan
                'cutting_unit_cost' => 0,
                'sewing_unit_cost' => 0,
                'finishing_unit_cost' => 0,
                'packaging_unit_cost' => 0,
                'overhead_unit_cost' => 0,
                'unit_cost' => $validated['unit_cost'],
                'notes' => $validated['notes'] ?? 'HPP sementara dari Master Item',
                'is_active' => 1,
                'created_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('master.items.edit', $item)
            ->with('success', 'HPP sementara item berhasil disimpan.');
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        DB::transaction(function () use ($data, &$item) {
            $item = Item::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $data['type'] ?? 'material',
                'item_category_id' => $data['item_category_id'] ?? null,
                'active' => isset($data['active']) ? (bool) $data['active'] : true,
                // last_purchase_price & hpp biarkan default (0) dari DB
            ]);

            $this->syncBarcodes($item, $data['barcodes'] ?? []);
        });

        return redirect()
            ->route('master.items.edit', $item)
            ->with('success', 'Item baru berhasil dibuat beserta barcode-nya.');
    }

    public function edit(Item $item)
    {
        $item->load('barcodes');

        // kalau ada kategori:
        $categories = ItemCategory::orderBy('name')->get();
        return view('master.items.edit', compact('item', 'categories'));

        return view('master.items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $data = $this->validateRequest($request, $item);

        DB::transaction(function () use ($data, $item) {
            $item->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $data['type'] ?? 'material',
                'item_category_id' => $data['item_category_id'] ?? null,
                'active' => isset($data['active']) ? (bool) $data['active'] : true,
                // last_purchase_price & hpp tetap dikelola proses lain
            ]);

            $this->syncBarcodes($item, $data['barcodes'] ?? []);
        });

        return redirect()
            ->route('master.items.edit', $item)
            ->with('success', 'Item & barcode berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return redirect()
            ->route('master.items.index')
            ->with('success', 'Item berhasil dihapus.');
    }

    /**
     * Bulk update beberapa item sekaligus.
     * Aksi yang didukung: set_category, set_type, set_hpp.
     *
     * Catatan biaya: set_hpp memakai mekanisme snapshot 'master_temp' yang
     * sama dengan storeHppTemp() — menonaktifkan snapshot aktif lalu membuat
     * snapshot baru. Tidak menyentuh ledger / jurnal / lot cost.
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['set_category', 'set_type', 'set_hpp'])],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:items,id'],

            // Bergantung action:
            'item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'type' => ['nullable', 'string', Rule::in(['material', 'finished_good', 'wip'])],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
        ]);

        $ids = array_values(array_unique($data['item_ids']));
        $action = $data['action'];
        $count = 0;

        DB::transaction(function () use ($action, $ids, $data, &$count) {

            if ($action === 'set_category') {
                // item_category_id boleh null (artinya kosongkan kategori)
                $count = Item::whereIn('id', $ids)
                    ->update(['item_category_id' => $data['item_category_id'] ?? null]);
                return;
            }

            if ($action === 'set_type') {
                if (empty($data['type'])) {
                    abort(422, 'Tipe wajib dipilih untuk aksi ubah tipe.');
                }
                $count = Item::whereIn('id', $ids)
                    ->update(['type' => $data['type']]);
                return;
            }

            if ($action === 'set_hpp') {
                if (!isset($data['unit_cost'])) {
                    abort(422, 'Nilai HPP wajib diisi untuk aksi set HPP.');
                }

                $now = Carbon::now();
                $unitCost = $data['unit_cost'];
                $notes = $data['notes'] ?? 'HPP sementara (bulk) dari Master Item';

                // Sinkronkan kolom items.hpp agar valuasi Inventory Stock ikut.
                // (HPP referensi/statis — tidak menyentuh Lot.avg_cost / jurnal.)
                Item::whereIn('id', $ids)->update(['hpp' => $unitCost]);

                foreach ($ids as $itemId) {
                    // Nonaktifkan snapshot aktif sebelumnya
                    ItemCostSnapshot::where('item_id', $itemId)
                        ->active()
                        ->update(['is_active' => 0]);

                    // Buat snapshot baru sebagai HPP sementara dari master
                    ItemCostSnapshot::create([
                        'item_id' => $itemId,
                        'warehouse_id' => null,
                        'snapshot_date' => $now->toDateString(),
                        'reference_type' => 'master_temp',
                        'reference_id' => null,
                        'qty_basis' => 0,
                        'rm_unit_cost' => $unitCost,
                        'cutting_unit_cost' => 0,
                        'sewing_unit_cost' => 0,
                        'finishing_unit_cost' => 0,
                        'packaging_unit_cost' => 0,
                        'overhead_unit_cost' => 0,
                        'unit_cost' => $unitCost,
                        'notes' => $notes,
                        'is_active' => 1,
                        'created_by' => Auth::id(),
                    ]);
                    $count++;
                }
                return;
            }
        });

        $labels = [
            'set_category' => 'kategori',
            'set_type' => 'tipe',
            'set_hpp' => 'HPP',
        ];

        return redirect()
            ->route('master.items.index')
            ->with('success', "Berhasil memperbarui {$labels[$action]} untuk {$count} item.");
    }

    /**
     * Validasi request untuk store & update.
     */
    protected function validateRequest(Request $request, ?Item $item = null): array
    {
        $idToIgnore = $item?->id;

        return $request->validate([
            'code' => [
                'required',
                'string',
                'max:50',
                Rule::unique('items', 'code')->ignore($idToIgnore),
            ],
            'name' => ['required', 'string', 'max:190'],

            'unit' => ['required', 'string', 'max:20'],

            'type' => [
                'required',
                'string',
                'max:50',
                // sesuaikan kalau kamu punya type lain
                Rule::in(['material', 'finished_good', 'wip']),
            ],

            'item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],

            'active' => ['nullable'],

            // Barcodes
            'barcodes' => ['array'],
            'barcodes.*.id' => ['nullable', 'integer'],
            'barcodes.*.barcode' => ['nullable', 'string', 'max:190'],
            'barcodes.*.type' => ['nullable', 'string', 'max:30'],
            'barcodes.*.notes' => ['nullable', 'string', 'max:190'],
            'barcodes.*.is_active' => ['nullable'],
        ]);
    }

    /**
     * Sinkronisasi barcodes berdasarkan array dari form.
     * Versi simple: hapus semua & insert ulang non-empty.
     */
    protected function syncBarcodes(Item $item, array $rows): void
    {
        // Hapus semua barcode lama item ini (simple & aman)
        $item->barcodes()->delete();

        $seen = [];

        foreach ($rows as $row) {
            $barcode = trim($row['barcode'] ?? '');

            if ($barcode === '') {
                continue;
            }

            // hindari duplikat di form yang sama
            if (in_array($barcode, $seen, true)) {
                continue;
            }
            $seen[] = $barcode;

            $type = $row['type'] ?? 'main';

            $item->barcodes()->create([
                'barcode' => $barcode,
                'type' => $type,
                'notes' => $row['notes'] ?? null,
                'is_active' => isset($row['is_active']) && (int) $row['is_active'] === 1,
            ]);
        }
    }

    public function meta(Request $request)
    {
        $id = (int) $request->get('item_id');
        $item = \App\Models\Item::select('id', 'default_allocation', 'default_expense_account_id')
            ->where('id', $id)
            ->first();

        if (!$item) {
            return response()->json(['ok' => false], 404);
        }

        return response()->json([
            'ok' => true,
            'default_allocation' => $item->default_allocation ?: 'hpp',
            'default_expense_account_id' => $item->default_expense_account_id,
        ]);
    }
}
