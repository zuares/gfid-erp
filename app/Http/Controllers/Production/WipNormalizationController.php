<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\Warehouse;
use App\Services\Production\WipNormalizationService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * WIP Normalization (opname WIP).
 *
 * DRAFT: user input hasil hitung fisik per item WIP → simpan, TANPA ubah stok.
 * APPROVE (owner/admin): WipNormalizationService.generate() → mutasi + jurnal.
 */
class WipNormalizationController extends Controller
{
    public function index(): View
    {
        $rows = InventoryAdjustment::query()
            ->where('purpose', 'wip_normalization')
            ->with(['warehouse', 'creator', 'approver'])
            ->withCount('lines')
            ->latest()
            ->paginate(20);

        return view('production.wip_normalization.index', compact('rows'));
    }

    public function create(Request $request): View
    {
        $warehouses = Warehouse::query()
            ->where('code', 'like', 'WIP-%')
            ->orderBy('code')
            ->get();

        $selectedId = (int) $request->integer('warehouse_id');
        $selected = $selectedId ? $warehouses->firstWhere('id', $selectedId) : null;

        // Item + qty sistem di gudang terpilih (dari inventory_stocks).
        $items = collect();
        if ($selected) {
            $items = DB::table('inventory_stocks as s')
                ->join('items as i', 'i.id', '=', 's.item_id')
                ->where('s.warehouse_id', $selected->id)
                ->whereRaw('COALESCE(s.qty,0) > 0.0001')
                ->selectRaw('s.item_id, i.code as item_code, i.name as item_name, s.qty as qty_system')
                ->orderBy('i.code')
                ->get();
        }

        $employees = \App\Models\Employee::query()
            ->where('active', 1)
            ->orderBy('name')
            ->get(['id', 'name']);

        return view('production.wip_normalization.create', compact('warehouses', 'selected', 'items', 'employees'));
    }

    public function store(Request $request, WipNormalizationService $service): RedirectResponse
    {
        $data = $request->validate([
            'warehouse_id'          => 'required|integer|exists:warehouses,id',
            'reason'                => 'required|string|max:255',
            'process_date'          => 'nullable|date',
            'lines'                 => 'required|array|min:1',
            'lines.*.item_id'       => 'required|integer|exists:items,id',
            'lines.*.qty_physical'  => 'required|numeric|min:0',
            'lines.*.operator_id'   => 'nullable|integer|exists:employees,id',
            'lines.*.notes'         => 'nullable|string|max:255',
        ]);

        $warehouse = Warehouse::findOrFail($data['warehouse_id']);
        if (!str_starts_with((string) $warehouse->code, 'WIP-')) {
            return back()->with('error', 'Gudang harus WIP-*.')->withInput();
        }

        $adj = DB::transaction(function () use ($data, $warehouse, $request) {
            $adj = InventoryAdjustment::create([
                'code'         => $this->generateCode(),
                'date'         => now()->toDateString(),
                'warehouse_id' => $warehouse->id,
                'purpose'      => 'wip_normalization',
                'action'       => InventoryAdjustment::ACTION_NORMALIZE,
                'process_date' => $data['process_date'] ?? null,
                'reason'       => $data['reason'],
                'status'       => InventoryAdjustment::STATUS_PENDING,
                'created_by'   => $request->user()->id,
            ]);

            foreach ($data['lines'] as $ln) {
                // qty_before diambil ULANG dari server (bukan dari input) agar akurat.
                $system = (float) DB::table('inventory_stocks')
                    ->where('warehouse_id', $warehouse->id)
                    ->where('item_id', (int) $ln['item_id'])
                    ->sum('qty');

                $physical = (float) $ln['qty_physical'];

                InventoryAdjustmentLine::create([
                    'inventory_adjustment_id' => $adj->id,
                    'item_id'      => (int) $ln['item_id'],
                    'qty_before'   => $system,
                    'qty_physical' => $physical,
                    'qty_after'    => $physical,
                    'qty_change'   => $physical - $system,
                    'direction'    => ($physical - $system) >= 0 ? 'in' : 'out',
                    'action'       => InventoryAdjustment::ACTION_NORMALIZE,
                    'process_date' => $data['process_date'] ?? null,
                    'operator_id'  => $ln['operator_id'] ?? null,
                    'notes'        => $ln['notes'] ?? null,
                ]);
            }

            return $adj;
        });

        return redirect()
            ->route('production.wip_normalization.show', $adj)
            ->with('success', 'Draft normalisasi WIP tersimpan. Belum mengubah stok — menunggu approval.');
    }

    public function show(InventoryAdjustment $wipNormalization): View
    {
        abort_unless(($wipNormalization->purpose ?? null) === 'wip_normalization', 404);

        $wipNormalization->load(['warehouse', 'creator', 'approver', 'lines.item', 'lines.operator']);

        return view('production.wip_normalization.show', ['adj' => $wipNormalization]);
    }

    public function approve(Request $request, InventoryAdjustment $wipNormalization, WipNormalizationService $service): RedirectResponse
    {
        abort_unless(($wipNormalization->purpose ?? null) === 'wip_normalization', 404);

        $role = $request->user()?->role;
        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya Owner/Admin yang boleh approve normalisasi WIP.');
        }

        try {
            $service->generate($wipNormalization, $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal approve: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.wip_normalization.show', $wipNormalization)
            ->with('success', 'Normalisasi WIP disetujui. Stok dikoreksi + jurnal selisih dibuat.');
    }

    public function void(Request $request, InventoryAdjustment $wipNormalization, WipNormalizationService $service): RedirectResponse
    {
        abort_unless(($wipNormalization->purpose ?? null) === 'wip_normalization', 404);

        $role = $request->user()?->role;
        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya Owner/Admin yang boleh membatalkan normalisasi WIP.');
        }

        $data = $request->validate([
            'void_reason' => 'required|string|max:255',
        ]);

        if ($wipNormalization->status !== InventoryAdjustment::STATUS_APPROVED) {
            return back()->with('error', 'Hanya normalisasi yang sudah approved yang bisa dibatalkan.');
        }

        try {
            $service->void($wipNormalization, $data['void_reason']);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.wip_normalization.show', $wipNormalization)
            ->with('success', 'Normalisasi dibatalkan. Stok dan jurnal dikembalikan (reversal).');
    }

    private function generateCode(): string
    {
        $prefix = 'WIPN-' . now()->format('Ymd');
        $last = InventoryAdjustment::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->value('code');
        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
