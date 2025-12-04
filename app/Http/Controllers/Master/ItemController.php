<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;

class ItemController extends Controller
{
    public function index(Request $request)
    {
        $query = Item::query()
            ->withCount('barcodes'); // <— ini penting

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

        // kalau kamu punya ItemCategory:
        // $categories = ItemCategory::orderBy('name')->get();
        // return view('items.index', compact('items', 'categories'));

        return view('items.index', compact('items'));
    }

    public function create()
    {
        $item = null;

        // kalau kamu punya list kategori, lempar juga ke view:
        // $categories = ItemCategory::orderBy('name')->get();
        // return view('items.create', compact('item', 'categories'));

        return view('items.create', compact('item'));
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
            ->route('items.edit', $item)
            ->with('success', 'Item baru berhasil dibuat beserta barcode-nya.');
    }

    public function edit(Item $item)
    {
        $item->load('barcodes');

        // kalau ada kategori:
        // $categories = ItemCategory::orderBy('name')->get();
        // return view('items.edit', compact('item', 'categories'));

        return view('items.edit', compact('item'));
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
            ->route('items.edit', $item)
            ->with('success', 'Item & barcode berhasil diperbarui.');
    }

    public function destroy(Item $item)
    {
        $item->delete();

        return redirect()
            ->route('items.index')
            ->with('success', 'Item berhasil dihapus.');
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
}
