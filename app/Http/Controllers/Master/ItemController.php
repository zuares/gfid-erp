<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\ItemCostSnapshot;
use App\Models\ItemRole;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query()
            ->with('category')
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

        if ($categoryKind = $request->input('category_kind')) {
            $query->whereHas('category', fn($q) => $q->where('kind', $categoryKind));
        }

        $items = $query
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        $categories = $this->categoryOptions();
        $categoryKinds = ItemCategory::kindLabels();

        return view('master.items.index', compact('items', 'categories', 'categoryKinds'));
    }

    public function create()
    {
        $item = null;

        // kalau kamu punya list kategori, lempar juga ke view:
        $categories = $this->categoryOptions();
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
            $classification = $this->classificationFor(
                $data['type'] ?? 'material',
                $data['item_category_id'] ?? null,
            );
            $productionSource = $this->productionSourceFor(
                $data['type'] ?? 'material',
                $data['production_source'] ?? null,
            );

            $item = Item::create([
                'code' => $data['code'],
                'name' => $data['name'],
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $data['type'] ?? 'material',
                'item_category_id' => $data['item_category_id'] ?? null,
                'item_role_id' => $classification['item_role_id'],
                'item_role' => $classification['item_role'],
                'is_stocked' => $classification['is_stocked'],
                'hpp_behavior' => $classification['hpp_behavior'],
                'production_source' => $productionSource,
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
        $categories = $this->categoryOptions();
        return view('master.items.edit', compact('item', 'categories'));

        return view('master.items.edit', compact('item'));
    }

    public function update(Request $request, Item $item)
    {
        $data = $this->validateRequest($request, $item);

        DB::transaction(function () use ($data, $item) {
            $classification = $this->classificationFor(
                $data['type'],
                $data['item_category_id'] ?? null,
            );
            $productionSource = $this->productionSourceFor(
                $data['type'],
                $data['production_source'] ?? null,
            );

            $item->update([
                'code' => $data['code'],
                'name' => $data['name'],
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $data['type'] ?? 'material',
                'item_category_id' => $data['item_category_id'] ?? null,
                'item_role_id' => $classification['item_role_id'],
                'item_role' => $classification['item_role'],
                'is_stocked' => $classification['is_stocked'],
                'hpp_behavior' => $classification['hpp_behavior'],
                'production_source' => $productionSource,
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
                $categoryId = $data['item_category_id'] ?? null;
                foreach (Item::whereIn('id', $ids)->get(['id', 'type', 'production_source']) as $item) {
                    $this->validateCategoryForType($item->type, $categoryId);
                    $classification = $this->classificationFor($item->type, $categoryId);
                    $productionSource = $this->productionSourceFor($item->type, $item->production_source ?? null);

                    Item::whereKey($item->id)->update([
                        'item_category_id' => $categoryId,
                        'item_role_id' => $classification['item_role_id'],
                        'item_role' => $classification['item_role'],
                        'is_stocked' => $classification['is_stocked'],
                        'hpp_behavior' => $classification['hpp_behavior'],
                        'production_source' => $productionSource,
                    ]);
                    $count++;
                }
                return;
            }

            if ($action === 'set_type') {
                if (empty($data['type'])) {
                    abort(422, 'Tipe wajib dipilih untuk aksi ubah tipe.');
                }
                foreach (Item::whereIn('id', $ids)->get(['id', 'item_category_id', 'production_source']) as $item) {
                    $this->validateCategoryForType($data['type'], $item->item_category_id);
                    $classification = $this->classificationFor($data['type'], $item->item_category_id);
                    $productionSource = $this->productionSourceFor($data['type'], $item->production_source ?? null);

                    Item::whereKey($item->id)->update([
                        'type' => $data['type'],
                        'item_role_id' => $classification['item_role_id'],
                        'item_role' => $classification['item_role'],
                        'is_stocked' => $classification['is_stocked'],
                        'hpp_behavior' => $classification['hpp_behavior'],
                        'production_source' => $productionSource,
                    ]);
                    $count++;
                }
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

        $data = $request->validate([
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
            'production_source' => ['nullable', 'string', Rule::in(array_keys(Item::productionSourceLabels()))],

            'active' => ['nullable'],

            // Barcodes
            'barcodes' => ['array'],
            'barcodes.*.id' => ['nullable', 'integer'],
            'barcodes.*.barcode' => ['nullable', 'string', 'max:190'],
            'barcodes.*.type' => ['nullable', 'string', 'max:30'],
            'barcodes.*.notes' => ['nullable', 'string', 'max:190'],
            'barcodes.*.is_active' => ['nullable'],
        ]);

        $this->validateCategoryForType($data['type'], $data['item_category_id'] ?? null);

        return $data;
    }

    protected function validateCategoryForType(string $type, ?int $categoryId): void
    {
        if (!$categoryId) {
            return;
        }

        $kind = ItemCategory::whereKey($categoryId)->value('kind');
        $allowed = $this->allowedCategoryKindsForType($type);

        if (!in_array($kind, $allowed, true)) {
            throw ValidationException::withMessages([
                'item_category_id' => 'Kategori tidak sesuai dengan tipe item. Finished Good wajib kategori produk; Material wajib kategori bahan/pendukung/accessories/packaging.',
            ]);
        }
    }

    protected function allowedCategoryKindsForType(string $type): array
    {
        return match ($type) {
            'finished_good', 'wip' => ['product'],
            'material' => ['material', 'support', 'accessory', 'packaging', 'other'],
            default => ['product', 'material', 'support', 'accessory', 'packaging', 'other'],
        };
    }

    protected function classificationFor(string $type, ?int $categoryId): array
    {
        $category = $categoryId ? ItemCategory::find($categoryId) : null;
        $roleCode = match (true) {
            $type === 'finished_good' || $type === 'wip' => ItemRole::FG,
            $category?->kind === 'support' || $category?->kind === 'accessory' => ItemRole::SUP,
            $category?->kind === 'packaging' => ItemRole::PKG,
            default => ItemRole::RM,
        };

        return match ($roleCode) {
            ItemRole::FG => [
                'item_role_id' => ItemRole::idByCode(ItemRole::FG),
                'item_role' => 'finished_good',
                'is_stocked' => true,
                'hpp_behavior' => 'hpp',
            ],
            ItemRole::SUP => [
                'item_role_id' => ItemRole::idByCode(ItemRole::SUP),
                'item_role' => 'production_supply',
                'is_stocked' => true,
                'hpp_behavior' => 'hpp',
            ],
            ItemRole::PKG => [
                'item_role_id' => ItemRole::idByCode(ItemRole::PKG),
                'item_role' => 'shipping_supply',
                'is_stocked' => false,
                'hpp_behavior' => 'non_hpp',
            ],
            default => [
                'item_role_id' => ItemRole::idByCode(ItemRole::RM),
                'item_role' => 'raw_material',
                'is_stocked' => true,
                'hpp_behavior' => 'hpp',
            ],
        };
    }

    protected function productionSourceFor(string $type, ?string $productionSource): ?string
    {
        if (!in_array($type, ['finished_good', 'wip'], true)) {
            return null;
        }

        if ($productionSource && array_key_exists($productionSource, Item::productionSourceLabels())) {
            return $productionSource;
        }

        return Item::PRODUCTION_BUY;
    }

    protected function categoryOptions()
    {
        return ItemCategory::query()
            ->orderByRaw("CASE kind
                WHEN 'product' THEN 1
                WHEN 'material' THEN 2
                WHEN 'support' THEN 3
                WHEN 'accessory' THEN 4
                WHEN 'packaging' THEN 5
                ELSE 9
            END")
            ->orderBy('name')
            ->get();
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
