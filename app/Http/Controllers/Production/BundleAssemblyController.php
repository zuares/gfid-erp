<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\BundleAssembly;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Production\BundleAssemblyService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class BundleAssemblyController extends Controller
{
    public function index(): View
    {
        $assemblies = BundleAssembly::query()
            ->with(['item', 'warehouse', 'creator'])
            ->withCount('lines')
            ->latest('date')
            ->latest('id')
            ->paginate(20);

        return view('production.bundle_assemblies.index', compact('assemblies'));
    }

    public function create(): View
    {
        $items = Item::query()
            ->where('active', true)
            ->whereIn('type', ['finished_good', 'wip'])
            ->where(function ($query) {
                $query->where('can_make', true)
                    ->orWhere('production_source', Item::PRODUCTION_IN_HOUSE);
            })
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type', 'stock_unit', 'unit']);

        $warehouses = Warehouse::query()
            ->where('active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name']);

        return view('production.bundle_assemblies.create', compact('items', 'warehouses'));
    }

    public function store(Request $request, BundleAssemblyService $service): RedirectResponse
    {
        $data = $request->validate([
            'item_id' => ['required', 'integer', 'exists:items,id'],
            'warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'qty' => ['required', 'numeric', 'gt:0'],
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ]);

        $item = Item::query()->findOrFail($data['item_id']);
        $warehouse = Warehouse::query()->whereKey($data['warehouse_id'])->where('active', true)->firstOrFail();

        try {
            $assembly = $service->createDraft(
                item: $item,
                warehouseId: (int) $warehouse->id,
                qty: $data['qty'],
                date: $data['date'],
                notes: $data['notes'] ?? null,
                createdBy: $request->user()?->id,
            );
        } catch (\Throwable $e) {
            return back()->withInput()->with('error', 'Draft assembly gagal dibuat: '.$e->getMessage());
        }

        return redirect()
            ->route('production.bundle_assemblies.show', $assembly)
            ->with('success', 'Draft assembly tersimpan. Belum mengubah stok.');
    }

    public function show(BundleAssembly $bundleAssembly): View
    {
        $bundleAssembly->load(['item', 'warehouse', 'creator', 'postedBy', 'voidedBy', 'lines.material']);

        return view('production.bundle_assemblies.show', ['assembly' => $bundleAssembly]);
    }

    public function post(Request $request, BundleAssembly $bundleAssembly, BundleAssemblyService $service): RedirectResponse
    {
        $this->ensureCanPost($request);

        try {
            $service->post($bundleAssembly);
        } catch (\Throwable $e) {
            return back()->with('error', 'Assembly gagal diposting: '.$e->getMessage());
        }

        return back()->with('success', 'Assembly diposting. Komponen berkurang dan bundle masuk stok.');
    }

    public function void(Request $request, BundleAssembly $bundleAssembly, BundleAssemblyService $service): RedirectResponse
    {
        $this->ensureCanPost($request);

        try {
            $service->void($bundleAssembly);
        } catch (\Throwable $e) {
            return back()->with('error', 'Assembly gagal dibatalkan: '.$e->getMessage());
        }

        return back()->with('success', 'Assembly dibatalkan dan mutasi stok direversal.');
    }

    private function ensureCanPost(Request $request): void
    {
        abort_unless(in_array($request->user()?->role, ['owner', 'admin'], true), 403);
    }
}
