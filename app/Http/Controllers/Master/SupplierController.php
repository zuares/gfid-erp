<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\Supplier;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class SupplierController extends Controller
{
    // =========================
    // CRUD
    // =========================

    public function index(Request $request): View
    {
        $q = trim((string) $request->input('q', ''));

        $suppliers = Supplier::query()
            ->when($q !== '', function ($query) use ($q) {
                $query->where(function ($w) use ($q) {
                    $w->where('name', 'like', "%{$q}%")
                        ->orWhere('code', 'like', "%{$q}%")
                        ->orWhere('phone', 'like', "%{$q}%")
                        ->orWhere('email', 'like', "%{$q}%");
                });
            })
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('master.suppliers.index', compact('suppliers', 'q'));
    }

    public function create(): View
    {
        return view('master.suppliers.create');
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', 'unique:suppliers,code'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['active'] = (int) ($data['active'] ?? 1);

        $supplier = Supplier::create($data);

        return redirect()
            ->route('master.suppliers.show', $supplier)
            ->with('success', 'Supplier berhasil dibuat.');
    }

    public function show(Supplier $supplier): View
    {
        // Load mapped items for initial render
        $supplier->load(['items' => function ($q) {
            $q->select('items.id', 'items.code', 'items.name', 'items.unit', 'items.type', 'items.last_purchase_price')
                ->orderBy('items.type')
                ->orderBy('items.code');
        }]);

        // item list untuk “Tambah mapping” (bisa kamu batasi: active=1)
        $itemsForPicker = Item::query()
            ->select('id', 'code', 'name', 'type', 'unit')
            ->where('active', 1)
            ->orderBy('type')
            ->orderBy('code')
            ->limit(3000) // aman untuk awal; kalau item kamu banyak banget, nanti kita ganti suggest endpoint
            ->get();

        return view('master.suppliers.show', compact('supplier', 'itemsForPicker'));
    }

    public function edit(Supplier $supplier): View
    {
        return view('master.suppliers.edit', compact('supplier'));
    }

    public function update(Request $request, Supplier $supplier): RedirectResponse
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:50', Rule::unique('suppliers', 'code')->ignore($supplier->id)],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'address' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['active'] = (int) ($data['active'] ?? 0);

        $supplier->update($data);

        return redirect()
            ->route('master.suppliers.show', $supplier)
            ->with('success', 'Supplier berhasil diperbarui.');
    }

    public function destroy(Supplier $supplier): RedirectResponse
    {
        $supplier->delete();

        return redirect()
            ->route('master.suppliers.index')
            ->with('success', 'Supplier berhasil dihapus.');
    }

    // =========================
    // Mapping: JSON for PO + UI
    // =========================

    public function itemsJson(Supplier $supplier): JsonResponse
    {
        $items = $supplier->items()
            ->select(['items.id', 'items.code', 'items.name', 'items.unit', 'items.type', 'items.last_purchase_price'])
            ->withPivot(['last_price'])
            ->orderBy('items.type')
            ->orderBy('items.code')
            ->get();

        $labels = [
            'material' => 'Material / Bahan Baku',
            'accessory' => 'Accessories',
            'finished_good' => 'Finished Goods',
            'other' => 'Lainnya',
        ];

        $groups = $items->groupBy(function ($it) {
            $t = strtolower((string) ($it->type ?? ''));
            return $t !== '' ? $t : 'other';
        })->map(function ($rows) {
            return $rows->map(function ($it) {
                $lp = (float) ($it->pivot?->last_price ?? 0);
                $fp = (float) ($it->last_purchase_price ?? 0);
                $price = $lp > 0 ? $lp : $fp;

                return [
                    'id' => (int) $it->id,
                    'code' => (string) $it->code,
                    'name' => (string) $it->name,
                    'unit' => (string) ($it->unit ?? 'pcs'),
                    'type' => (string) ($it->type ?? 'other'),
                    'last_price' => $lp,
                    'fallback_price' => $fp,
                    'price' => $price,
                ];
            })->values();
        });

        return response()->json([
            'supplier' => ['id' => (int) $supplier->id, 'name' => (string) $supplier->name],
            'labels' => $labels,
            'groups' => $groups,
            'count' => $items->count(),
        ]);
    }

    public function attachItem(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'last_price' => ['nullable', 'numeric', 'min:0'],
        ]);

        DB::table('supplier_items')->updateOrInsert(
            [
                'supplier_id' => (int) $supplier->id,
                'item_id' => (int) $data['item_id'],
            ],
            [
                'last_price' => (float) ($data['last_price'] ?? 0),
                'updated_at' => now(),
                'created_at' => now(),
            ]
        );

        return response()->json(['ok' => true]);
    }

    public function updateItem(Request $request, Supplier $supplier, Item $item): JsonResponse
    {
        $data = $request->validate([
            'last_price' => ['required', 'numeric', 'min:0'],
        ]);

        $supplier->items()->updateExistingPivot($item->id, [
            'last_price' => (float) $data['last_price'],
        ]);

        return response()->json(['ok' => true]);
    }

    public function detachItem(Supplier $supplier, Item $item): JsonResponse
    {
        $supplier->items()->detach($item->id);

        return response()->json(['ok' => true]);
    }

    public function suggestItems(Request $request): \Illuminate\Http\JsonResponse
    {
        $qRaw = trim((string) $request->query('q', ''));
        if ($qRaw === '' || mb_strlen($qRaw) < 2) {
            return response()->json(['q' => $qRaw, 'items' => []]);
        }

        $type = strtolower((string) $request->query('type', ''));

        $items = \App\Models\Item::query()
            ->select('id', 'code', 'name', 'type', 'unit', 'last_purchase_price')
            ->where('active', 1)
            ->when($type !== '', fn($qq) => $qq->where('type', $type))
            ->where(function ($w) use ($qRaw) {
                $w->where('code', 'like', strtoupper($qRaw) . '%')
                    ->orWhere('code', 'like', '%' . strtoupper($qRaw) . '%')
                    ->orWhere('name', 'like', '%' . $qRaw . '%');
            })
            ->orderBy('code')
            ->limit(20)
            ->get()
            ->map(fn($it) => [
                'id' => (int) $it->id,
                'code' => (string) $it->code,
                'name' => (string) $it->name,
                'type' => (string) ($it->type ?? 'other'),
                'unit' => (string) ($it->unit ?? 'pcs'),
                'last_purchase_price' => (float) ($it->last_purchase_price ?? 0),
            ]);

        return response()->json(['q' => $qRaw, 'items' => $items]);
    }

    public function bulkAttach(Request $request, Supplier $supplier): \Illuminate\Http\JsonResponse
    {
        $data = $request->validate([
            'codes' => ['required', 'string'],
            'last_price' => ['nullable', 'numeric', 'min:0'], // optional default for all
        ]);

        $raw = (string) $data['codes'];
        $defaultPrice = (float) ($data['last_price'] ?? 0);

        // ambil codes unik, trim, uppercase
        $codes = collect(preg_split('/[\s,;]+/', $raw))
            ->map(fn($x) => strtoupper(trim((string) $x)))
            ->filter(fn($x) => $x !== '')
            ->unique()
            ->values();

        if ($codes->isEmpty()) {
            return response()->json(['ok' => true, 'added' => 0, 'missing' => [], 'duplicates' => []]);
        }

        $items = Item::query()
            ->select('id', 'code', 'last_purchase_price')
            ->whereIn('code', $codes->all())
            ->get()
            ->keyBy('code');

        $missing = $codes->filter(fn($c) => !$items->has($c))->values()->all();

        // existing mappings untuk detect duplicate
        $existingIds = $supplier->items()->pluck('items.id')->all();
        $existingSet = array_flip($existingIds);

        $attach = [];
        $duplicates = [];

        foreach ($codes as $c) {
            $it = $items->get($c);
            if (!$it) {
                continue;
            }

            $id = (int) $it->id;
            if (isset($existingSet[$id])) {
                $duplicates[] = $c;
                continue;
            }

            $lp = $defaultPrice > 0 ? $defaultPrice : (float) ($it->last_purchase_price ?? 0);
            $attach[$id] = ['last_price' => $lp];
        }

        if (!empty($attach)) {
            $supplier->items()->syncWithoutDetaching($attach);
        }

        return response()->json([
            'ok' => true,
            'added' => count($attach),
            'missing' => $missing,
            'duplicates' => $duplicates,
        ]);
    }

}
