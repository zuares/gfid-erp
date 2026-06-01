<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\RtsDirectReceive;
use App\Models\RtsDirectReceiveLine;
use App\Models\SewingPickup;
use App\Models\SewingPickupLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class RtsDirectReceiveController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
    ) {}

    public function index(Request $request): View
    {
        $q = trim((string) $request->get('q', ''));

        $rows = RtsDirectReceive::query()
            ->with([
                'fromWarehouse',
                'toWarehouse',
                'operator',
                // ✅ chip detail barang di index
                'lines.item:id,code,name',
            ])
            ->when($q, function ($qq) use ($q) {
                $qq->where('code', 'like', "%{$q}%")
                    ->orWhere('notes', 'like', "%{$q}%");
            })
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.rts_direct_receives.index', compact('rows', 'q'));
    }

    public function create(Request $request): View
    {
        $date = $request->input('date', now()->toDateString());

        // to: RTS
        $toWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        // normal source: WIP-FIN
        $fromWarehouse = Warehouse::where('code', 'WIP-FIN')->firstOrFail();

        // auto_sr source: WIP-SEW (fallback WH-SEWING)
        $wipSewWarehouse = Warehouse::query()
            ->where('code', 'WIP-SEW')
            ->orWhere('code', 'WH-SEWING')
            ->firstOrFail();

        $operators = Employee::orderBy('code')->get();

        $items = Item::query()
            ->select('id', 'code', 'name')
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        return view('inventory.rts_direct_receives.create', compact(
            'date',
            'fromWarehouse', // WIP-FIN
            'toWarehouse', // WH-RTS
            'wipSewWarehouse', // WIP-SEW / WH-SEWING
            'operators',
            'items'
        ));
    }

    /**
     * ✅ JSON: ambil WIP-SEW outstanding per operator
     * dipakai oleh create blade mode auto_sr
     */
    public function operatorWip(Request $request): JsonResponse
    {
        $operatorId = (int) $request->get('operator_id', 0);
        if ($operatorId <= 0) {
            return response()->json(['rows' => []]);
        }

        $wipSewWarehouse = Warehouse::query()
            ->where('code', 'WIP-SEW')
            ->orWhere('code', 'WH-SEWING')
            ->firstOrFail();

        // Ambil semua pickup operator (yang tidak void), urut terbaru
        $pickups = SewingPickup::query()
            ->whereNull('voided_at')
            ->where('operator_id', $operatorId)
            ->with([
                // ✅ samakan dgn SewingReturn: pickup LINE yang voided tidak boleh muncul
                //    (cegah "kartu hantu" yang memicu selisih)
                'lines' => fn($q) => $q->whereNull('voided_at'),
                'lines.bundle.finishedItem:id,code,name',
            ])
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->get();

        if ($pickups->isEmpty()) {
            return response()->json(['rows' => []]);
        }

        // Kumpulkan pickup lines outstanding (remaining > 0)
        $rows = $pickups->flatMap(function (SewingPickup $p) {
            return ($p->lines ?? collect())->map(function (SewingPickupLine $l) use ($p) {
                $qtyBundle = (float) ($l->qty_bundle ?? 0);
                $returnedOk = (float) ($l->qty_returned_ok ?? 0);
                $returnedRej = (float) ($l->qty_returned_reject ?? 0);
                $directPick = (float) ($l->qty_direct_picked ?? 0);
                $progressAdj = (float) ($l->qty_progress_adjusted ?? 0);

                $remaining = max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);

                $item = $l->bundle?->finishedItem;
                $itemId = (int) ($item?->id ?? 0);

                return [
                    'pickup_id' => (int) $p->id,
                    'pickup_code' => (string) ($p->code ?? ''),
                    'pickup_date' => (string) optional($p->date)->toDateString(),
                    'pickup_line_id' => (int) $l->id,

                    'item_id' => $itemId,
                    'item_code' => (string) ($item?->code ?? ''),
                    'item_name' => (string) ($item?->name ?? ''),

                    'remaining' => (float) $remaining,
                ];
            })->filter(fn($row) => (float) $row['remaining'] > 0.000001 && (int) $row['item_id'] > 0);
        })->values();

        if ($rows->isEmpty()) {
            return response()->json(['rows' => []]);
        }

        // Ambil stok WIP-SEW untuk item terkait
        $itemIds = $rows->pluck('item_id')->unique()->values()->all();

        $wipStockByItemId = InventoryStock::query()
            ->where('warehouse_id', $wipSewWarehouse->id)
            ->whereIn('item_id', $itemIds)
            ->pluck('qty', 'item_id')
            ->map(fn($v) => (float) $v)
            ->toArray();

        // Attach wip_stock dan filter yg stoknya > 0
        $out = $rows->map(function ($row) use ($wipStockByItemId) {
            $row['wip_stock'] = (float) ($wipStockByItemId[$row['item_id']] ?? 0);
            return $row;
        })->filter(fn($row) => (float) $row['wip_stock'] > 0.000001)->values();

        return response()->json(['rows' => $out]);
    }

    public function store(Request $request): RedirectResponse
    {
        $mode = $request->input('mode', 'normal');
        if ($mode === 'auto_sr') {
            return $this->storeAutoSr($request);
        }
        return $this->storeNormal($request);
    }

    private function storeNormal(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['nullable', 'in:normal,auto_sr'],
            'date' => ['required', 'date'],
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'operator_id' => ['nullable', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty' => ['required', 'numeric', 'gt:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'lines.required' => 'Minimal 1 item.',
            'lines.*.qty.gt' => 'Qty harus > 0.',
        ]);

        // anti duplicate item server-side
        $itemIds = array_map(fn($r) => (int) $r['item_id'], $data['lines'] ?? []);
        if (count($itemIds) !== count(array_unique($itemIds))) {
            throw ValidationException::withMessages(['lines' => 'Item tidak boleh duplikat.']);
        }

        if ((int) $data['from_warehouse_id'] === (int) $data['to_warehouse_id']) {
            throw ValidationException::withMessages(['to_warehouse_id' => 'Gudang tujuan harus berbeda dari gudang sumber.']);
        }

        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['date'])->toDateString();
            $code = $this->generateCode($date);

            $doc = RtsDirectReceive::create([
                'date' => $date,
                'code' => $code,
                'from_warehouse_id' => (int) $data['from_warehouse_id'],
                'to_warehouse_id' => (int) $data['to_warehouse_id'],
                'operator_id' => $data['operator_id'] ? (int) $data['operator_id'] : null,
                'notes' => !empty($data['notes']) ? trim((string) $data['notes']) : null,
                'created_by_user_id' => auth()->id(),
            ]);

            $fromId = (int) $data['from_warehouse_id'];
            $toId = (int) $data['to_warehouse_id'];

            $uniqueItemIds = collect($data['lines'])->pluck('item_id')->map(fn($v) => (int) $v)->unique()->values()->all();

            $stocks = InventoryStock::query()
                ->where('warehouse_id', $fromId)
                ->whereIn('item_id', $uniqueItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('item_id');

            foreach ($data['lines'] as $idx => $row) {
                $itemId = (int) $row['item_id'];
                $qty = (float) $row['qty'];

                $avail = (float) ($stocks[$itemId]->qty ?? 0);
                if ($qty > $avail + 0.000001) {
                    throw ValidationException::withMessages([
                        'lines' => "Stok sumber tidak cukup untuk item #{$itemId}. Butuh {$qty}, stok {$avail}.",
                    ]);
                }

                RtsDirectReceiveLine::create([
                    'rts_direct_receive_id' => $doc->id,
                    'line_no' => $idx + 1,
                    'item_id' => $itemId,
                    'qty' => $qty,
                    'notes' => !empty($row['notes']) ? trim((string) $row['notes']) : null,
                ]);

                $this->inventory->move(
                    itemId: $itemId,
                    fromWarehouseId: $fromId,
                    toWarehouseId: $toId,
                    qty: $qty,
                    referenceType: 'rts_direct_receive',
                    referenceId: $doc->id,
                    notes: "RTS Direct Receive {$doc->code}",
                    date: $date,
                    allowNegative: false,
                    lotId: null,
                );
            }

            return redirect()
                ->route('rts.direct-receives.show', $doc)
                ->with('success', 'Dadakan ke RTS berhasil dicatat.');
        });
    }

    private function storeAutoSr(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'mode' => ['required', 'in:auto_sr'],
            'date' => ['required', 'date'],

            // from harus WIP-SEW (ditentukan di UI switch)
            'from_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'to_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],

            // ✅ wajib pilih operator agar bisa load outstanding
            'operator_id' => ['required', 'integer', 'exists:employees,id'],
            'notes' => ['nullable', 'string', 'max:2000'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.sewing_pickup_line_id' => ['required', 'integer', 'exists:sewing_pickup_lines,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_ok' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['nullable', 'numeric', 'min:0'],
            'lines.*.notes' => ['nullable', 'string', 'max:255'],
        ], [
            'operator_id.required' => 'Operator wajib dipilih untuk Auto-SR.',
            'lines.required' => 'Minimal 1 baris.',
        ]);

        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['date'])->toDateString();
            $code = $this->generateCode($date);

            $fromWipSewId = (int) $data['from_warehouse_id'];
            $toRtsId = (int) $data['to_warehouse_id'];
            $operatorId = (int) $data['operator_id'];

            // Warehouses penting
            $wipFin = Warehouse::where('code', 'WIP-FIN')->firstOrFail();
            $rejectWh = Warehouse::where('code', 'REJECT')->first(); // optional

            // Normalize input lines → ambil yang total > 0
            $raw = collect($data['lines'])
                ->map(function ($r) {
                    $ok = (float) ($r['qty_ok'] ?? 0);
                    $rj = (float) ($r['qty_reject'] ?? 0);
                    return [
                        'sewing_pickup_line_id' => (int) $r['sewing_pickup_line_id'],
                        'item_id' => (int) $r['item_id'],
                        'qty_ok' => $ok,
                        'qty_reject' => $rj,
                        'notes' => trim((string) ($r['notes'] ?? '')),
                        'total' => $ok + $rj,
                    ];
                })
                ->filter(fn($r) => (float) $r['total'] > 0.000001)
                ->values();

            if ($raw->isEmpty()) {
                throw ValidationException::withMessages([
                    'lines' => 'Minimal isi 1 baris dengan OK atau RJ > 0.',
                ]);
            }

            // Anti duplicate item (strict)
            $itemIds = $raw->pluck('item_id')->all();
            if (count($itemIds) !== count(array_unique($itemIds))) {
                throw ValidationException::withMessages([
                    'lines' => 'Item tidak boleh duplikat (Auto-SR).',
                ]);
            }

            // Lock pickup lines
            $pickupLineIds = $raw->pluck('sewing_pickup_line_id')->unique()->values()->all();

            $pickupLines = SewingPickupLine::query()
                ->with(['bundle.finishedItem', 'sewingPickup'])
                ->whereIn('id', $pickupLineIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('id');

            // Validasi: pickup line harus milik operator + tidak voided
            foreach ($pickupLines as $pl) {
                // ✅ tolak line yang sudah void (cegah selisih dari form basi)
                if ($pl->voided_at) {
                    throw ValidationException::withMessages([
                        'lines' => "Pickup line #{$pl->id} sudah void, tidak bisa diproses.",
                    ]);
                }

                $pickup = $pl->sewingPickup;
                if (!$pickup || (int) $pickup->operator_id !== $operatorId) {
                    throw ValidationException::withMessages([
                        'lines' => 'Ada baris yang bukan milik operator yang dipilih (bypass).',
                    ]);
                }
            }

            // Hitung remaining per pickup line (rumus sama dengan SewingReturnController)
            $remainingByPlId = [];
            foreach ($pickupLines as $pl) {
                $qtyBundle = (float) ($pl->qty_bundle ?? 0);
                $returnedOk = (float) ($pl->qty_returned_ok ?? 0);
                $returnedRej = (float) ($pl->qty_returned_reject ?? 0);
                $directPick = (float) ($pl->qty_direct_picked ?? 0);
                $progressAdj = (float) ($pl->qty_progress_adjusted ?? 0);

                $remainingByPlId[$pl->id] = max($qtyBundle - ($returnedOk + $returnedRej + $directPick + $progressAdj), 0);
            }

            // Clamp per pickup line: total <= remaining
            foreach ($raw as $r) {
                $plId = (int) $r['sewing_pickup_line_id'];
                $rem = (float) ($remainingByPlId[$plId] ?? 0);
                if ((float) $r['total'] > $rem + 0.000001) {
                    throw ValidationException::withMessages([
                        'lines' => "Qty OK+RJ melebihi sisa pickup line #{$plId}. Sisa: {$rem}.",
                    ]);
                }
            }

            // Lock stok WIP-SEW per item
            $uniqueItemIds = $raw->pluck('item_id')->unique()->values()->all();

            $stocks = InventoryStock::query()
                ->where('warehouse_id', $fromWipSewId)
                ->whereIn('item_id', $uniqueItemIds)
                ->lockForUpdate()
                ->get()
                ->keyBy('item_id');

            // Clamp per item total <= stok WIP-SEW
            $needByItem = [];
            foreach ($raw as $r) {
                $itemId = (int) $r['item_id'];
                $needByItem[$itemId] = ($needByItem[$itemId] ?? 0) + (float) $r['total'];
            }
            foreach ($needByItem as $itemId => $need) {
                $avail = (float) ($stocks[$itemId]->qty ?? 0);
                if ($need > $avail + 0.000001) {
                    throw ValidationException::withMessages([
                        'lines' => "Stok WIP-SEW tidak cukup untuk item #{$itemId}. Butuh {$need}, stok {$avail}.",
                    ]);
                }
            }

            // Create doc header (audit)
            $doc = RtsDirectReceive::create([
                'date' => $date,
                'code' => $code,
                'from_warehouse_id' => $fromWipSewId,
                'to_warehouse_id' => $toRtsId,
                'operator_id' => $operatorId,
                'notes' => !empty($data['notes']) ? trim((string) $data['notes']) : 'AUTO-SR + Direct RTS',
                'created_by_user_id' => auth()->id(),
            ]);

            // Process each line
            $lineNo = 1;

            foreach ($raw as $r) {
                $plId = (int) $r['sewing_pickup_line_id'];
                $pl = $pickupLines->get($plId);

                $itemId = (int) $r['item_id'];
                $ok = (float) $r['qty_ok'];
                $rj = (float) $r['qty_reject'];
                $total = (float) $r['total'];
                $bundleId = $pl?->cutting_job_bundle_id ? (int) $pl->cutting_job_bundle_id : null; // FASE 1: tag bundle

                // save line (qty total)
                RtsDirectReceiveLine::create([
                    'rts_direct_receive_id' => $doc->id,
                    'line_no' => $lineNo++,
                    'item_id' => $itemId,
                    'qty' => $total,
                    'notes' => $r['notes'] !== '' ? $r['notes'] : null,
                ]);

                // Reject: WIP-SEW OUT (+ optional IN REJECT)
                if ($rj > 0.000001) {
                    $this->inventory->stockOut(
                        warehouseId: $fromWipSewId,
                        itemId: $itemId,
                        qty: $rj,
                        date: $date,
                        sourceType: 'auto_sr_reject',
                        sourceId: $doc->id,
                        notes: "AUTO-SR {$doc->code} (RJ)",
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: null,
                        affectLotCost: false,
                        cuttingJobBundleId: $bundleId,
                    );

                    if ($rejectWh) {
                        $this->inventory->stockIn(
                            warehouseId: $rejectWh->id,
                            itemId: $itemId,
                            qty: $rj,
                            date: $date,
                            sourceType: 'auto_sr_reject',
                            sourceId: $doc->id,
                            notes: "AUTO-SR {$doc->code} (RJ) → REJECT",
                            lotId: null,
                            unitCost: null,
                            affectLotCost: false,
                            cuttingJobBundleId: $bundleId,
                        );
                    }
                }

                // OK: WIP-SEW → WIP-FIN → RTS
                if ($ok > 0.000001) {
                    $this->inventory->move(
                        itemId: $itemId,
                        fromWarehouseId: $fromWipSewId,
                        toWarehouseId: $wipFin->id,
                        qty: $ok,
                        referenceType: 'auto_sr_ok',
                        referenceId: $doc->id,
                        notes: "AUTO-SR {$doc->code} (OK) WIP-SEW → WIP-FIN",
                        date: $date,
                        allowNegative: false,
                        lotId: null,
                        cuttingJobBundleId: $bundleId,
                    );

                    $this->inventory->move(
                        itemId: $itemId,
                        fromWarehouseId: $wipFin->id,
                        toWarehouseId: $toRtsId,
                        qty: $ok,
                        referenceType: 'auto_sr_ok_rts',
                        referenceId: $doc->id,
                        notes: "AUTO-SR {$doc->code} (OK) WIP-FIN → RTS",
                        date: $date,
                        allowNegative: false,
                        lotId: null,
                        cuttingJobBundleId: $bundleId,
                    );
                }

                // ✅ marker: qty_direct_picked bertambah agar remaining turun & tidak muncul lagi
                $pl->qty_direct_picked = (float) ($pl->qty_direct_picked ?? 0) + $total;
                $pl->save();
            }

            return redirect()
                ->route('rts.direct-receives.show', $doc)
                ->with('success', 'Berhasil: Auto-SR + Direct RTS (1 langkah).');
        });
    }

    public function show(RtsDirectReceive $directReceive): View
    {
        $directReceive->load(['fromWarehouse', 'toWarehouse', 'operator', 'lines.item']);
        return view('inventory.rts_direct_receives.show', ['doc' => $directReceive]);
    }

    private function generateCode(string $date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');
        $prefix = "DRRTS-{$ymd}-";

        $last = DB::table('rts_direct_receives')
            ->where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($last && str_starts_with((string) $last, $prefix)) {
            $tail = substr((string) $last, strlen($prefix));
            $n = (int) ltrim($tail, '0');
            $next = max($n + 1, 1);
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
}
