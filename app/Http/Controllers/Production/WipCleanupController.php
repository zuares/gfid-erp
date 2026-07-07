<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\InventoryAdjustment;
use App\Models\InventoryAdjustmentLine;
use App\Models\Warehouse;
use App\Models\SewingPickupLine;
use App\Services\Production\WipActionService;
use App\Services\Production\WipHangingService;
use App\Services\Production\WipPickupCloseService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

/**
 * WipCleanupController — tahap awal: PREVIEW READ-ONLY WIP menggantung.
 *
 * Sesuai rencana implementasi (docs/AUDIT_WIP_PRODUCTION.md langkah 3-4):
 * halaman ini hanya menampilkan kandidat WIP menggantung dari data existing.
 * BELUM ada aksi apa pun yang mengubah stok. Aksi cleanup/normalisasi
 * (move, finish, reject, write-off, legacy) menyusul setelah angka di sini
 * diverifikasi.
 */
class WipCleanupController extends Controller
{
    public function __construct(private WipHangingService $wip)
    {
    }

    public function index(): View
    {
        $summary = $this->wip->summary();

        return view('production.wip_cleanup.index', [
            'summary'         => $summary,
            'cutOutstanding'  => $this->wip->cutBundlesOutstanding(),
            'pickupsOpen'     => $this->wip->pickupsNotReturned(),
            'wipStock'        => $this->wip->wipStockResidual(),
            'qcPending'       => $this->wip->qcPending(),
            'ageWarnDays'     => WipHangingService::AGE_WARN_DAYS,
        ]);
    }

    /** Form aksi cleanup untuk satu stok WIP (dari preview). */
    public function actionForm(Request $request): View
    {
        $warehouse = Warehouse::findOrFail($request->integer('warehouse_id'));
        abort_unless(str_starts_with((string) $warehouse->code, 'WIP-'), 404);

        $itemId = (int) $request->integer('item_id');
        $system = (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $warehouse->id)->where('item_id', $itemId)->sum('qty');
        $item = \App\Models\Item::findOrFail($itemId);

        // Gudang tujuan yang valid untuk "move" (WIP lain).
        $wipTargets = Warehouse::where('code', 'like', 'WIP-%')
            ->where('id', '!=', $warehouse->id)->orderBy('code')->get();

        return view('production.wip_cleanup.action', compact('warehouse', 'item', 'system', 'wipTargets'));
    }

    public function storeAction(Request $request, WipActionService $service): RedirectResponse
    {
        $role = $request->user()?->role;
        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya Owner/Admin yang boleh menjalankan aksi WIP cleanup.');
        }

        $data = $request->validate([
            'warehouse_id'       => 'required|integer|exists:warehouses,id',
            'item_id'            => 'required|integer|exists:items,id',
            'action'             => 'required|string|in:move,finish,reject,write_off,close_legacy,keep_open',
            'qty'                => 'required|numeric|min:0.001',
            'reason'             => 'required|string|max:255',
            'process_date'       => 'nullable|date',
            'target_warehouse_id' => 'nullable|integer|exists:warehouses,id',
        ]);

        $warehouse = Warehouse::findOrFail($data['warehouse_id']);
        $system = (float) DB::table('inventory_stocks')
            ->where('warehouse_id', $warehouse->id)->where('item_id', $data['item_id'])->sum('qty');

        if ($data['qty'] > $system + 1e-6) {
            return back()->with('error', "Qty melebihi stok sistem ({$system}).")->withInput();
        }

        // Resolusi gudang tujuan per action.
        $toLocationId = match ($data['action']) {
            'finish' => (int) Warehouse::where('code', 'WH-PRD')->value('id'),
            'reject' => (int) Warehouse::where('code', 'REJECT')->value('id'),
            'move'   => (int) ($data['target_warehouse_id'] ?? 0),
            default  => null,
        };
        if (in_array($data['action'], ['move', 'finish', 'reject'], true) && !$toLocationId) {
            return back()->with('error', 'Gudang tujuan tidak ditemukan untuk aksi ini.')->withInput();
        }

        $adj = DB::transaction(function () use ($data, $warehouse, $system, $toLocationId, $request) {
            $adj = InventoryAdjustment::create([
                'code'             => $this->generateCode(),
                'date'             => now()->toDateString(),
                'warehouse_id'     => $warehouse->id,
                'from_location_id' => $warehouse->id,
                'to_location_id'   => $toLocationId,
                'purpose'          => 'wip_cleanup',
                'action'           => $data['action'],
                'process_date'     => $data['process_date'] ?? null,
                'reason'           => $data['reason'],
                'status'           => InventoryAdjustment::STATUS_PENDING,
                'created_by'       => $request->user()->id,
            ]);

            $qty = (float) $data['qty'];
            InventoryAdjustmentLine::create([
                'inventory_adjustment_id' => $adj->id,
                'item_id'      => (int) $data['item_id'],
                'qty_before'   => $system,
                'qty_after'    => $system - $qty,
                'qty_change'   => -$qty,
                'direction'    => 'out',
                'action'       => $data['action'],
                'process_date' => $data['process_date'] ?? null,
            ]);

            return $adj;
        });

        try {
            $service->generate($adj, $request->user()->id);
        } catch (\Throwable $e) {
            return redirect()
                ->route('production.wip_cleanup.show', $adj)
                ->with('error', 'Aksi gagal diproses: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.wip_cleanup.show', $adj)
            ->with('success', 'Aksi WIP cleanup berhasil: stok dipindah/dikeluarkan + jurnal dibuat.');
    }

    /** Form tutup pickup menggantung (3 opsi: settle/write_off/cancel). */
    public function pickupCloseForm(Request $request): View
    {
        $line = SewingPickupLine::with(['pickup', 'bundle', 'finishedItem'])
            ->findOrFail($request->integer('pickup_line_id'));

        $closed = (float) ($line->qty_closed ?? 0);
        $outstanding = max(0, (float) $line->qty_bundle - (float) $line->qty_returned_ok - (float) $line->qty_returned_reject - $closed);

        return view('production.wip_cleanup.pickup_close', compact('line', 'outstanding'));
    }

    public function pickupCloseStore(Request $request, WipPickupCloseService $service): RedirectResponse
    {
        $role = $request->user()?->role;
        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403, 'Hanya Owner/Admin yang boleh menutup pickup.');
        }

        $data = $request->validate([
            'pickup_line_id' => 'required|integer|exists:sewing_pickup_lines,id',
            'action'         => 'required|string|in:settle,write_off,cancel',
            'qty'            => 'required|numeric|min:0.001',
            'reason'         => 'required|string|max:255',
        ]);

        try {
            $adj = $service->generate($data['action'], (int) $data['pickup_line_id'], (float) $data['qty'], $data['reason'], $request->user()->id);
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal menutup pickup: ' . $e->getMessage())->withInput();
        }

        return redirect()->route('production.wip_cleanup.show', $adj)
            ->with('success', 'Pickup ditutup: stok dipindah/dikeluarkan' . ($data['action'] === 'write_off' ? ' + jurnal dibuat' : '') . '.');
    }

    public function show(InventoryAdjustment $wipCleanup): View
    {
        abort_unless(($wipCleanup->purpose ?? null) === 'wip_cleanup', 404);
        $wipCleanup->load(['warehouse', 'creator', 'approver', 'lines.item']);
        $target = $wipCleanup->to_location_id ? Warehouse::find($wipCleanup->to_location_id) : null;

        return view('production.wip_cleanup.show', ['adj' => $wipCleanup, 'target' => $target]);
    }

    public function void(Request $request, InventoryAdjustment $wipCleanup, WipActionService $service): RedirectResponse
    {
        abort_unless(($wipCleanup->purpose ?? null) === 'wip_cleanup', 404);
        $role = $request->user()?->role;
        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403);
        }
        $data = $request->validate(['void_reason' => 'required|string|max:255']);

        if ($wipCleanup->status !== InventoryAdjustment::STATUS_APPROVED) {
            return back()->with('error', 'Hanya aksi yang sudah diproses yang bisa dibatalkan.');
        }

        // Pickup-close (settle/cancel/write-off atas pickup line) punya reversal
        // sendiri karena juga mengembalikan penanda di sewing_pickup_lines/bundle.
        $isPickupClose = $wipCleanup->reference_type === SewingPickupLine::class
            || in_array($wipCleanup->action, [InventoryAdjustment::ACTION_PICKUP_SETTLE, InventoryAdjustment::ACTION_PICKUP_CANCEL], true);

        try {
            if ($isPickupClose) {
                app(WipPickupCloseService::class)->void($wipCleanup, $data['void_reason']);
            } else {
                $service->void($wipCleanup, $data['void_reason']);
            }
        } catch (\Throwable $e) {
            return back()->with('error', 'Gagal membatalkan: ' . $e->getMessage());
        }

        return redirect()->route('production.wip_cleanup.show', $wipCleanup)
            ->with('success', 'Aksi dibatalkan. Stok & jurnal dikembalikan.');
    }

    private function generateCode(): string
    {
        $prefix = 'WIPC-' . now()->format('Ymd');
        $last = InventoryAdjustment::where('code', 'like', $prefix . '%')->orderByDesc('code')->value('code');
        $seq = $last ? ((int) substr($last, -3)) + 1 : 1;

        return $prefix . '-' . str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
    }
}
