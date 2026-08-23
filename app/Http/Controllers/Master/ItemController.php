<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Item;
use App\Models\ItemBom;
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
        $filteredQuery = $this->itemQueryFor($request);
        $query = (clone $filteredQuery)
            ->with('category')
            ->withCount('barcodes')
            ->with(['costSnapshots' => function ($q) {
                $q->active()
                    ->orderByDesc('snapshot_date')
                    ->orderByDesc('id')
                    ->limit(1);
            }])
            ->withCount(['boms as active_boms_count' => fn ($q) => $q->where('active', true)]);

        $items = $query
            ->orderBy('code')
            ->paginate(25)
            ->withQueryString();

        $categories = $this->categoryOptions();
        $categoryKinds = ItemCategory::kindLabels();
        $typeLabels = $this->typeLabels();
        $itemStats = [
            'total' => (clone $filteredQuery)->count(),
            'active' => (clone $filteredQuery)->where('active', true)->count(),
            'can_buy' => (clone $filteredQuery)->where('can_buy', true)->count(),
            'can_make' => (clone $filteredQuery)->where('can_make', true)->count(),
            'hybrid' => (clone $filteredQuery)->where('can_buy', true)->where('can_make', true)->count(),
            'missing_hpp' => (clone $filteredQuery)->where(function ($q) {
                $q->where(function ($q) {
                    $q->whereNull('hpp')->orWhere('hpp', '<=', 0);
                })->where(function ($q) {
                    $q->whereNull('base_unit_cost')->orWhere('base_unit_cost', '<=', 0);
                });
            })->whereDoesntHave('costSnapshots', function ($q) {
                $q->where('is_active', true)->where('unit_cost', '>', 0);
            })->count(),
        ];
        $supplyItems = Item::query()->whereIn('type', ['finished_good', 'wip']);
        $supplySummary = [
            'total' => (clone $supplyItems)->count(),
            'hybrid' => (clone $supplyItems)->where('can_buy', true)->where('can_make', true)->count(),
            'make_only' => (clone $supplyItems)->where('can_buy', false)->where('can_make', true)->count(),
            'buy_only' => (clone $supplyItems)->where('can_buy', true)->where('can_make', false)->count(),
            'review' => (clone $supplyItems)->where('can_buy', false)->where('can_make', false)->count(),
        ];

        return view('master.items.index', compact('items', 'categories', 'categoryKinds', 'typeLabels', 'itemStats', 'supplySummary'));
    }

    public function create()
    {
        $item = null;

        $categories = $this->categoryOptions();
        $expenseAccounts = \App\Models\Account::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();
        $activeSnapshot = null;
        $typeLabels = $this->typeLabels();
        return view('master.items.create', compact('item', 'categories', 'expenseAccounts', 'activeSnapshot', 'typeLabels'));
    }

    public function show(Item $item)
    {
        $item->load(['category', 'barcodes']);

        // snapshot HPP aktif (kalau ada)
        $activeSnapshot = ItemCostSnapshot::getActiveForItem($item->id, null);

        return view('master.items.show', [
            'item' => $item,
            'activeSnapshot' => $activeSnapshot,
            'typeLabels' => $this->typeLabels(),
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
            $supplyPolicy = $this->supplyPolicyFor($data['type'] ?? 'material', $data);

            $item = Item::create([
                'code' => $data['code'],
                'sku' => empty($data['sku']) ? $data['code'] : $data['sku'],
                'name' => $data['name'],
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $data['type'] ?? 'material',
                'item_category_id' => $data['item_category_id'] ?? null,
                'item_role_id' => $classification['item_role_id'],
                'item_role' => $classification['item_role'],
                'is_stocked' => $classification['is_stocked'],
                'hpp_behavior' => $classification['hpp_behavior'],
                'production_source' => $this->legacyProductionSourceFor(
                    $data['type'] ?? 'material',
                    $supplyPolicy['default_supply_source'],
                ),
                'can_buy' => $supplyPolicy['can_buy'],
                'can_make' => $supplyPolicy['can_make'],
                'default_supply_source' => $supplyPolicy['default_supply_source'],
                'active' => isset($data['active']) ? (bool) $data['active'] : true,
                'default_allocation' => $data['default_allocation'] ?? 'hpp',
                'default_expense_account_id' => $data['default_expense_account_id'] ?? null,
                'last_purchase_price' => $data['last_purchase_price'] ?? 0,
            ]);

            $this->syncBarcodes($item, $data['barcodes'] ?? []);

            if (isset($data['unit_cost'])) {
                $item->update(['hpp' => $data['unit_cost']]);
                ItemCostSnapshot::create([
                    'item_id' => $item->id,
                    'warehouse_id' => null,
                    'snapshot_date' => Carbon::now()->toDateString(),
                    'reference_type' => 'master_temp',
                    'reference_id' => null,
                    'qty_basis' => 0,
                    'rm_unit_cost' => $data['unit_cost'],
                    'cutting_unit_cost' => 0,
                    'sewing_unit_cost' => 0,
                    'finishing_unit_cost' => 0,
                    'packaging_unit_cost' => 0,
                    'overhead_unit_cost' => 0,
                    'unit_cost' => $data['unit_cost'],
                    'notes' => $data['hpp_notes'] ?? 'HPP awal dari Master Item',
                    'is_active' => 1,
                    'created_by' => Auth::id(),
                ]);
            }
        });

        return redirect()
            ->route('master.items.edit', $item)
            ->with('success', 'Item baru berhasil dibuat beserta barcode-nya.');
    }

    public function edit(Item $item)
    {
        $item->load('barcodes');

        $categories = $this->categoryOptions();
        $expenseAccounts = \App\Models\Account::where('type', 'expense')->where('is_active', true)->orderBy('name')->get();
        $activeSnapshot = ItemCostSnapshot::getActiveForItem($item->id, null);
        $itemBom = ItemBom::query()->where('item_id', $item->id)->first();
        $typeLabels = $this->typeLabels();
        
        return view('master.items.edit', compact('item', 'categories', 'expenseAccounts', 'activeSnapshot', 'itemBom', 'typeLabels'));
    }

    public function update(Request $request, Item $item)
    {
        $data = $this->validateRequest($request, $item);

        DB::transaction(function () use ($data, $item) {
            $classification = $this->classificationFor(
                $data['type'],
                $data['item_category_id'] ?? null,
            );
            $supplyPolicy = $this->supplyPolicyFor($data['type'], $data, $item);

            $item->update([
                'code' => $data['code'],
                'sku' => empty($data['sku']) ? $data['code'] : $data['sku'],
                'name' => $data['name'],
                'unit' => $data['unit'] ?? 'pcs',
                'type' => $data['type'] ?? 'material',
                'item_category_id' => $data['item_category_id'] ?? null,
                'item_role_id' => $classification['item_role_id'],
                'item_role' => $classification['item_role'],
                'is_stocked' => $classification['is_stocked'],
                'hpp_behavior' => $classification['hpp_behavior'],
                'production_source' => $this->legacyProductionSourceFor(
                    $data['type'],
                    $supplyPolicy['default_supply_source'],
                ),
                'can_buy' => $supplyPolicy['can_buy'],
                'can_make' => $supplyPolicy['can_make'],
                'default_supply_source' => $supplyPolicy['default_supply_source'],
                'active' => isset($data['active']) ? (bool) $data['active'] : true,
                'default_allocation' => $data['default_allocation'] ?? 'hpp',
                'default_expense_account_id' => $data['default_expense_account_id'] ?? null,
                'last_purchase_price' => array_key_exists('last_purchase_price', $data) ? $data['last_purchase_price'] : $item->last_purchase_price,
            ]);

            $this->syncBarcodes($item, $data['barcodes'] ?? []);

            if (isset($data['unit_cost'])) {
                $unitCost = $data['unit_cost'];
                $hppNotes = $data['hpp_notes'] ?? 'HPP diperbarui dari form Master Item';
                $currentSnapshot = ItemCostSnapshot::getActiveForItem($item->id, null);
                
                if (!$currentSnapshot || (float)$currentSnapshot->unit_cost !== (float)$unitCost || $currentSnapshot->notes !== $hppNotes) {
                    ItemCostSnapshot::where('item_id', $item->id)->active()->update(['is_active' => 0]);
                    $item->update(['hpp' => $unitCost]);
                    
                    ItemCostSnapshot::create([
                        'item_id' => $item->id,
                        'warehouse_id' => null,
                        'snapshot_date' => Carbon::now()->toDateString(),
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
                        'notes' => $hppNotes,
                        'is_active' => 1,
                        'created_by' => Auth::id(),
                    ]);
                }
            }
        });

        return redirect()
            ->route('master.items.index')
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
     * Aksi yang didukung: set_category, set_type, set_hpp, set_supply_policy.
     *
     * Catatan biaya: set_hpp memakai mekanisme snapshot 'master_temp' yang
     * sama dengan storeHppTemp() — menonaktifkan snapshot aktif lalu membuat
     * snapshot baru. Tidak menyentuh ledger / jurnal / lot cost.
     */
    public function bulkUpdate(Request $request)
    {
        $data = $request->validate([
            'action' => ['required', 'string', Rule::in(['set_category', 'set_type', 'set_hpp', 'set_supply_policy'])],
            'item_ids' => ['required', 'array', 'min:1'],
            'item_ids.*' => ['integer', 'exists:items,id'],

            // Bergantung action:
            'item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'type' => ['nullable', 'string', Rule::in(['material', 'finished_good', 'wip'])],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'notes' => ['nullable', 'string', 'max:255'],
            'can_buy' => ['nullable', 'boolean'],
            'can_make' => ['nullable', 'boolean'],
            'default_supply_source' => ['nullable', 'string', Rule::in(array_keys(Item::supplySourceLabels()))],
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
                foreach (Item::whereIn('id', $ids)->get([
                    'id',
                    'type',
                    'item_category_id',
                    'production_source',
                    'can_buy',
                    'can_make',
                    'default_supply_source',
                ]) as $item) {
                    $this->validateCategoryForType($data['type'], $item->item_category_id);
                    $classification = $this->classificationFor($data['type'], $item->item_category_id);
                    $isSupplyItem = in_array($item->type, ['finished_good', 'wip'], true);
                    $supplyPolicy = $this->supplyPolicyFor(
                        $data['type'],
                        [
                            'can_buy' => $isSupplyItem ? (bool) $item->can_buy : true,
                            'can_make' => $isSupplyItem ? (bool) $item->can_make : false,
                            'default_supply_source' => $isSupplyItem
                                ? $item->default_supply_source
                                : Item::SUPPLY_BUY,
                        ],
                        $item,
                    );

                    Item::whereKey($item->id)->update([
                        'type' => $data['type'],
                        'item_role_id' => $classification['item_role_id'],
                        'item_role' => $classification['item_role'],
                        'is_stocked' => $classification['is_stocked'],
                        'hpp_behavior' => $classification['hpp_behavior'],
                        'production_source' => $this->legacyProductionSourceFor(
                            $data['type'],
                            $supplyPolicy['default_supply_source'],
                        ),
                        'can_buy' => $supplyPolicy['can_buy'],
                        'can_make' => $supplyPolicy['can_make'],
                        'default_supply_source' => $supplyPolicy['default_supply_source'],
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

            if ($action === 'set_supply_policy') {
                $canBuy = (bool) ($data['can_buy'] ?? false);
                $canMake = (bool) ($data['can_make'] ?? false);
                $defaultSource = $data['default_supply_source'] ?? null;

                foreach (Item::whereIn('id', $ids)->get(['id', 'type', 'production_source']) as $item) {
                    if (!in_array($item->type, ['finished_good', 'wip'], true)) {
                        continue;
                    }

                    $supplyPolicy = $this->supplyPolicyFor($item->type, [
                        'can_buy' => $canBuy,
                        'can_make' => $canMake,
                        'default_supply_source' => $defaultSource,
                    ], $item);

                    Item::whereKey($item->id)->update([
                        'production_source' => $this->legacyProductionSourceFor(
                            $item->type,
                            $supplyPolicy['default_supply_source'],
                        ),
                        'can_buy' => $supplyPolicy['can_buy'],
                        'can_make' => $supplyPolicy['can_make'],
                        'default_supply_source' => $supplyPolicy['default_supply_source'],
                    ]);
                    $count++;
                }

                if ($count === 0) {
                    abort(422, 'Pilih item Finished Good atau WIP untuk mengubah metode pasok.');
                }

                return;
            }
        });

        $labels = [
            'set_category' => 'kategori',
            'set_type' => 'tipe',
            'set_hpp' => 'HPP',
            'set_supply_policy' => 'metode pasok',
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
            'sku' => ['nullable', 'string', 'max:100'],

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
            'can_buy' => ['nullable', 'boolean'],
            'can_make' => ['nullable', 'boolean'],
            'default_supply_source' => ['nullable', 'string', Rule::in(array_keys(Item::supplySourceLabels()))],

            'active' => ['nullable'],
            
            'default_allocation' => ['nullable', 'string', Rule::in(['hpp', 'expense'])],
            'default_expense_account_id' => ['nullable', 'integer', 'exists:accounts,id'],

            'last_purchase_price' => ['nullable', 'numeric', 'min:0'],
            'unit_cost' => ['nullable', 'numeric', 'min:0'],
            'hpp_notes' => ['nullable', 'string', 'max:255'],

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

    /**
     * Normalisasi kebijakan pasok item untuk FG/WIP.
     * production_source tetap diisi sebagai compatibility layer untuk service lama.
     */
    protected function supplyPolicyFor(string $type, array $data, ?Item $item = null): array
    {
        if (!in_array($type, ['finished_good', 'wip'], true)) {
            return [
                'can_buy' => false,
                'can_make' => false,
                'default_supply_source' => null,
            ];
        }

        $legacySource = $item?->production_source
            ?? ($data['production_source'] ?? Item::PRODUCTION_BUY);

        $canBuy = array_key_exists('can_buy', $data)
            ? (bool) $data['can_buy']
            : $legacySource === Item::PRODUCTION_BUY;
        $canMake = array_key_exists('can_make', $data)
            ? (bool) $data['can_make']
            : $legacySource === Item::PRODUCTION_IN_HOUSE;

        $defaultSource = $data['default_supply_source'] ?? null;
        if (!array_key_exists($defaultSource, Item::supplySourceLabels())) {
            $defaultSource = match ($legacySource) {
                Item::PRODUCTION_IN_HOUSE => Item::SUPPLY_MAKE,
                Item::PRODUCTION_OUTSOURCE => Item::SUPPLY_OUTSOURCE,
                default => Item::SUPPLY_BUY,
            };
        }

        if ($defaultSource === Item::SUPPLY_MAKE && !$canMake) {
            $defaultSource = $canBuy ? Item::SUPPLY_BUY : null;
        } elseif ($defaultSource === Item::SUPPLY_BUY && !$canBuy) {
            $defaultSource = $canMake ? Item::SUPPLY_MAKE : null;
        } elseif (!$canBuy && !$canMake && $defaultSource !== Item::SUPPLY_OUTSOURCE) {
            $defaultSource = null;
        }

        return [
            'can_buy' => $canBuy,
            'can_make' => $canMake,
            'default_supply_source' => $defaultSource,
        ];
    }

    protected function legacyProductionSourceFor(string $type, ?string $defaultSource): ?string
    {
        if (!in_array($type, ['finished_good', 'wip'], true)) {
            return null;
        }

        return match ($defaultSource) {
            Item::SUPPLY_MAKE => Item::PRODUCTION_IN_HOUSE,
            Item::SUPPLY_OUTSOURCE => Item::PRODUCTION_OUTSOURCE,
            default => Item::PRODUCTION_BUY,
        };
    }

    protected function typeLabels(): array
    {
        return [
            'material' => 'Material / Bahan',
            'wip' => 'Setengah Jadi (WIP)',
            'finished_good' => 'Barang Jadi (FG)',
        ];
    }

    protected function itemQueryFor(Request $request)
    {
        $query = Item::query();

        if ($search = trim((string) $request->input('q'))) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('sku', 'like', '%' . $search . '%')
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

        $supplyMode = $request->input('supply_mode');
        if (in_array($supplyMode, ['buy', 'make', 'hybrid', 'undefined', 'buy_only', 'make_only', 'review'], true)) {
            $query->whereIn('type', ['finished_good', 'wip']);

            match ($supplyMode) {
                'buy', 'buy_only' => $query->where('can_buy', true)->where('can_make', false),
                'make', 'make_only' => $query->where('can_buy', false)->where('can_make', true),
                'hybrid' => $query->where('can_buy', true)->where('can_make', true),
                'undefined', 'review' => $query->where('can_buy', false)->where('can_make', false),
            };
        }

        return $query;
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

    public function updateExpenseAccount(Request $request, Item $item)
    {
        $request->validate([
            'expense_account_id' => 'required|exists:accounts,id',
        ]);

        $item->default_expense_account_id = $request->expense_account_id;
        $item->save();

        return response()->json([
            'ok' => true,
            'message' => 'Akun biaya berhasil diperbarui',
            'expense_account_id' => $item->default_expense_account_id
        ]);
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
