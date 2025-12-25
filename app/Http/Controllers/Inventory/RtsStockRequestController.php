<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Item;
use App\Models\SewingPickupLine;
use App\Models\StockRequest;
use App\Models\StockRequestLine;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RtsStockRequestController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function index(Request $request): View
    {
        $user = $request->user();
        $role = $user?->role;
        $isOperating = $role === 'operating';

        // Status filter:
        // - Operating: default = submitted (Menunggu)
        // - Non-operating: default = all
        $statusFilter = $request->input('status', $isOperating ? 'submitted' : 'all'); // submitted|shipped|partial|completed|pending|all
        $period = $request->input('period', 'today'); // today|week|month|all

        // Batasi status yang boleh dipakai oleh role operating
        if ($isOperating) {
            $allowedOperatingStatuses = ['submitted', 'shipped', 'completed'];
            if (!in_array($statusFilter, $allowedOperatingStatuses, true)) {
                $statusFilter = 'submitted';
            }
        }

        $dateFrom = null;
        $dateTo = null;

        switch ($period) {
            case 'week':
                $dateFrom = Carbon::now()->startOfWeek();
                $dateTo = Carbon::now()->endOfWeek();
                break;
            case 'month':
                $dateFrom = Carbon::now()->startOfMonth();
                $dateTo = Carbon::now()->endOfMonth();
                break;
            case 'all':
                break;
            case 'today':
            default:
                $dateFrom = Carbon::today();
                $dateTo = Carbon::today();
                $period = 'today';
                break;
        }

        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom && $dateTo) {
                $query->whereBetween('date', [
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ]);
            }
            return $query;
        };

        $baseQuery = StockRequest::rtsReplenish()
            ->with(['sourceWarehouse', 'destinationWarehouse', 'requestedBy'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_dispatched_qty', 'qty_dispatched')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->withSum('lines as total_picked_qty', 'qty_picked');

        $baseQuery = $applyDateFilter($baseQuery);

        $statsBase = StockRequest::rtsReplenish();
        $statsBase = $applyDateFilter($statsBase);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->where('status', 'submitted')->count(),
            'shipped' => (clone $statsBase)->where('status', 'shipped')->count(),
            'partial' => (clone $statsBase)->where('status', 'partial')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
        ];
        $stats['pending'] = $stats['submitted'] + $stats['shipped'] + $stats['partial'];

        // ✅ Outstanding RTS = request - (received + picked)
        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->withSum('lines as total_picked_qty', 'qty_picked')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $recv = (float) ($req->total_received_qty ?? 0);
                $pick = (float) ($req->total_picked_qty ?? 0);
                return max($reqQty - $recv - $pick, 0);
            });

        $listQuery = clone $baseQuery;

        switch ($statusFilter) {
            case 'submitted':
            case 'shipped':
            case 'partial':
            case 'completed':
                $listQuery->where('status', $statusFilter);
                break;
            case 'pending':
                $listQuery->whereIn('status', ['submitted', 'shipped', 'partial']);
                break;
            case 'all':
            default:
                $statusFilter = $isOperating ? 'submitted' : 'all'; // jaga-jaga kalau masuk sini
                if (!$isOperating) {
                    // hanya non-operating yang boleh benar-benar lihat semua
                    // operating sudah dibatasi di atas
                }
                break;
        }

        $stockRequests = $listQuery
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.rts_stock_requests.index', compact(
            'stockRequests',
            'stats',
            'outstandingQty',
            'statusFilter',
            'period'
        ));
    }

    public function create(Request $request): View
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $edit = (bool) $request->boolean('edit', false);

        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        $prefillRequest = null;
        $prefillLines = [];
        $prefillDate = $date;

        if ($edit) {
            // Ambil dokumen existing hari ini yang masih berjalan (bukan completed)
            $prefillRequest = StockRequest::rtsReplenish()
                ->whereDate('date', $date)
                ->where('source_warehouse_id', $prdWarehouse->id)
                ->where('destination_warehouse_id', $rtsWarehouse->id)
                ->whereIn('status', ['submitted', 'shipped', 'partial'])
                ->latest('id')
                ->with(['lines.item'])
                ->first();

            if ($prefillRequest) {
                $prefillDate = $prefillRequest->date?->toDateString() ?? $date;

                // Prefill: hanya item yang masih outstanding (req - received - picked)
                $prefillLines = $prefillRequest->lines
                    ->sortBy('line_no')
                    ->values()
                    ->map(function ($l) {
                        $req = (float) ($l->qty_request ?? 0);
                        $recv = (float) ($l->qty_received ?? 0);
                        $pick = (float) ($l->qty_picked ?? 0);
                        $outstanding = max($req - $recv - $pick, 0);

                        return [
                            'item_id' => (int) $l->item_id,
                            'qty_request' => (float) $outstanding,
                        ];
                    })
                    ->filter(fn($row) => (float) $row['qty_request'] > 0)
                    ->values()
                    ->toArray();
            }
        }

        $finishedGoodsItems = Item::query()
            ->select('id', 'code', 'name')
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        return view('inventory.rts_stock_requests.create', compact(
            'prdWarehouse',
            'rtsWarehouse',
            'finishedGoodsItems',
            'prefillRequest',
            'prefillLines',
            'prefillDate',
        ));
    }

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'source_warehouse_id' => ['required', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'exists:warehouses,id'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'exists:items,id'],
            'lines.*.qty_request' => ['required', 'numeric', 'gt:0'],
            'lines.*.notes' => ['nullable', 'string'],
        ]);

        $ids = collect($validated['lines'] ?? [])
            ->pluck('item_id')
            ->map(fn($v) => (int) $v)
            ->filter()
            ->values();

        $dups = $ids->duplicates()->values();
        if ($dups->isNotEmpty()) {
            throw \Illuminate\Validation\ValidationException::withMessages([
                'lines' => 'Ada item duplikat. 1 item hanya boleh 1 baris.',
            ]);
        }

        $date = Carbon::parse($validated['date'])->startOfDay();
        $srcId = (int) $validated['source_warehouse_id'];
        $dstId = (int) $validated['destination_warehouse_id'];

        $stockRequest = null;
        $wasUpdated = false;

        DB::transaction(function () use (&$stockRequest, &$wasUpdated, $validated, $date, $srcId, $dstId) {

            // ✅ Cari dokumen existing yang masih berjalan (submitted/shipped/partial)
            $existing = StockRequest::rtsReplenish()
                ->whereDate('date', $date->toDateString())
                ->where('source_warehouse_id', $srcId)
                ->where('destination_warehouse_id', $dstId)
                ->whereIn('status', ['submitted', 'shipped', 'partial'])
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                $wasUpdated = true;
                $stockRequest = $existing;

                // notes: append rapi
                if (!empty($validated['notes'])) {
                    $append = trim($validated['notes']);
                    $stockRequest->notes = trim(($stockRequest->notes ?? '') . "\n" . $append);
                }

                $stockRequest->date = $date->toDateString();
                $stockRequest->save();

                // load lines buat upsert
                $stockRequest->load('lines');

                // map line by item_id
                $byItem = $stockRequest->lines->keyBy('item_id');

                // untuk line_no baru
                $maxLineNo = (int) ($stockRequest->lines->max('line_no') ?? 0);

                foreach ($validated['lines'] as $payload) {
                    $itemId = (int) $payload['item_id'];
                    $addQty = (float) $payload['qty_request'];

                    /** @var StockRequestLine|null $line */
                    $line = $byItem->get($itemId);

                    if ($line) {
                        // ✅ APPEND request: qty_request = lama + input
                        $oldReq = (float) ($line->qty_request ?? 0);
                        $newReq = $oldReq + $addQty;

                        // ✅ guard: tidak boleh < komit
                        $dispatched = (float) ($line->qty_dispatched ?? 0);
                        $received = (float) ($line->qty_received ?? 0);
                        $picked = (float) ($line->qty_picked ?? 0);
                        $minReq = max($dispatched, $received + $picked);

                        if ($newReq + 0.0000001 < $minReq) {
                            throw ValidationException::withMessages([
                                "lines" => "Qty request untuk item {$itemId} tidak boleh lebih kecil dari yang sudah terkirim/terpenuhi (min: {$minReq}).",
                            ]);
                        }

                        $available = $this->inventory->getAvailableStock($srcId, $itemId);

                        $line->qty_request = $newReq;
                        $line->stock_snapshot_at_request = (float) $available;
                        if (!empty($payload['notes'])) {
                            $line->notes = trim(($line->notes ?? '') . "\n" . trim($payload['notes']));
                        }
                        $line->save();
                    } else {
                        // ✅ line baru
                        $maxLineNo++;
                        $available = $this->inventory->getAvailableStock($srcId, $itemId);

                        StockRequestLine::create([
                            'stock_request_id' => $stockRequest->id,
                            'line_no' => $maxLineNo,
                            'item_id' => $itemId,
                            'qty_request' => $addQty,
                            'stock_snapshot_at_request' => (float) $available,

                            'qty_dispatched' => 0,
                            'qty_received' => 0,
                            'qty_picked' => 0,

                            'notes' => $payload['notes'] ?? null,
                        ]);
                    }
                }

            } else {
                // ✅ tidak ada existing -> create dokumen baru
                $wasUpdated = false;

                $stockRequest = StockRequest::create([
                    'code' => $this->generateCodeForDate($date),
                    'date' => $date->toDateString(),
                    'purpose' => 'rts_replenish',
                    'source_warehouse_id' => $srcId,
                    'destination_warehouse_id' => $dstId,
                    'status' => 'submitted',
                    'requested_by_user_id' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                ]);

                foreach ($validated['lines'] as $i => $payload) {
                    $itemId = (int) $payload['item_id'];
                    $available = $this->inventory->getAvailableStock($srcId, $itemId);

                    StockRequestLine::create([
                        'stock_request_id' => $stockRequest->id,
                        'line_no' => $i + 1,
                        'item_id' => $itemId,
                        'qty_request' => (float) $payload['qty_request'],
                        'stock_snapshot_at_request' => (float) $available,

                        'qty_dispatched' => 0,
                        'qty_received' => 0,
                        'qty_picked' => 0,

                        'notes' => $payload['notes'] ?? null,
                    ]);
                }
            }
        });

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', $wasUpdated
                ? 'Permintaan RTS berhasil di-update (append ke dokumen yang sama).'
                : 'Permintaan RTS berhasil dibuat dan dikirim ke PRD.'
            );
    }

    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse', 'requestedBy']);

        return view('inventory.rts_stock_requests.show', [
            'stockRequest' => $stockRequest,
        ]);
    }

    public function confirmReceive(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if(!in_array($stockRequest->status, ['submitted', 'shipped', 'partial']), 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $transit = Warehouse::where('code', 'WH-TRANSIT')->firstOrFail();
        $transitId = (int) $transit->id;

        $liveStocks = [];
        foreach ($stockRequest->lines as $line) {
            $liveStocks[$line->id] = $this->inventory->getAvailableStock($transitId, $line->item_id);
        }

        return view('inventory.rts_stock_requests.confirm', [
            'stockRequest' => $stockRequest,
            'liveStocks' => $liveStocks,
        ]);
    }

    /**
     * RTS RECEIVE: TRANSIT → RTS
     * TRANSIT tidak boleh minus.
     *
     * ✅ NEW BEHAVIOR:
     * - Kalau ada qty diterima (anyReceived = true) maka dokumen LANGSUNG completed.
     * - Tidak peduli masih ada selisih antara request vs (received + picked),
     *   dokumen dianggap selesai dan tidak akan dipakai lagi untuk append request.
     */
    public function finalize(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if(!in_array($stockRequest->status, ['submitted', 'shipped', 'partial']), 404);

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_received' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $transit = Warehouse::where('code', 'WH-TRANSIT')->firstOrFail();
        $srcId = (int) $transit->id; // TRANSIT
        $dstId = (int) $stockRequest->destination_warehouse_id; // RTS

        $anyReceived = false;

        DB::transaction(function () use (&$anyReceived, $stockRequest, $validated, $srcId, $dstId) {
            $stockRequest->load('lines');

            foreach ($stockRequest->lines as $line) {
                $input = $validated['lines'][$line->id] ?? null;
                $qty = $input && isset($input['qty_received']) ? (float) $input['qty_received'] : 0.0;

                if ($qty <= 0) {
                    continue;
                }

                // Guard: tidak boleh terima melebihi sisa dispatched yang belum diterima
                $maxReceivable = max((float) $line->qty_dispatched - (float) $line->qty_received, 0);
                if ($qty > $maxReceivable + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}.qty_received" => "Qty diterima melebihi sisa yang tersedia di Transit (maks: {$maxReceivable}).",
                    ]);
                }

                $anyReceived = true;

                try {
                    $this->inventory->move(
                        itemId: (int) $line->item_id,
                        fromWarehouseId: $srcId,
                        toWarehouseId: $dstId,
                        qty: $qty,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'RTS receive (TRANSIT → RTS)',
                        date: $stockRequest->date ?? now(),
                        allowNegative: false// ✅ TRANSIT tidak boleh minus
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                }

                $line->qty_received = (float) $line->qty_received + $qty;
                $line->save();
            }

            if (!$anyReceived) {
                // Tidak ada qty diisi → jangan ubah status apa pun
                return;
            }

            // =======================
            // ✅ NEW: sekali RTS terima → dokumen ditutup (completed)
            // =======================
            $stockRequest->load('lines'); // kalau mau dicek/ dipakai logika lain

            $stockRequest->status = 'completed';

            // Isi siapa & kapan terima pertama kali
            $stockRequest->received_by_user_id = $stockRequest->received_by_user_id ?? Auth::id();
            $stockRequest->received_at = $stockRequest->received_at ?? now();

            $stockRequest->save();
        });

        if (!$anyReceived) {
            return back()->with('warning', 'Tidak ada qty diterima yang diisi.');
        }

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', 'Penerimaan RTS berhasil. Dokumen ini sudah selesai (completed) dan permintaan baru akan memakai nomor baru.');
    }

    /**
     * Direct pickup dari PENJAHIT/WIP (bukan PRD).
     * Catatan: ganti code gudang sesuai sistemmu.
     */
    public function directPickup(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if($stockRequest->status === 'completed', 404);

        $validated = $request->validate([
            'operator_id' => ['required', 'integer', 'exists:employees,id'], // ✅ operator penjahit sumber
            'lines' => ['required', 'array'],
            'lines.*.qty_picked' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
        ]);

        // ✅ gudang sumber = WIP-SEW / WH-SEWING / WH-SEW
        $sewingWh = Warehouse::query()
            ->whereIn('code', ['WIP-SEW', 'WH-SEWING', 'WH-SEW'])
            ->first();

        if (!$sewingWh) {
            throw ValidationException::withMessages([
                'warehouse' => 'Gudang sewing/WIP (WIP-SEW / WH-SEWING / WH-SEW) belum diset.',
            ]);
        }

        $rtsWh = Warehouse::query()->where('code', 'WH-RTS')->firstOrFail();

        $opId = (int) $validated['operator_id'];

        $any = false;

        DB::transaction(function () use (&$any, $validated, $stockRequest, $sewingWh, $rtsWh, $opId) {

            $stockRequest->load('lines');

            // Append notes (optional)
            if (!empty($validated['notes'])) {
                $append = trim((string) $validated['notes']);
                $stockRequest->notes = trim(($stockRequest->notes ?? '') . "\n" . $append);
            }

            // =============================
            // 0) Parse input -> wanted per SR line + wanted per item
            // =============================
            $wantedBySrLineId = []; // srLineId => qty
            $wantedByItem = []; // itemId => total qty (untuk inventory move + FIFO allocation)

            foreach ($stockRequest->lines as $srLine) {
                $input = $validated['lines'][$srLine->id] ?? null;
                $qty = $input && isset($input['qty_picked']) ? (float) $input['qty_picked'] : 0.0;

                if ($qty <= 0.000001) {
                    continue;
                }

                // Guard: tidak boleh > sisa request (req - received - picked)
                $already = (float) $srLine->qty_received + (float) $srLine->qty_picked;
                $max = max((float) $srLine->qty_request - $already, 0);

                if ($qty > $max + 0.000001) {
                    throw ValidationException::withMessages([
                        "lines.{$srLine->id}.qty_picked" => "Qty melebihi sisa request (maks: {$max}).",
                    ]);
                }

                $wantedBySrLineId[$srLine->id] = $qty;

                $itemId = (int) $srLine->item_id;
                $wantedByItem[$itemId] = ($wantedByItem[$itemId] ?? 0) + $qty;
            }

            if (empty($wantedByItem)) {
                return; // nanti di luar transaction -> warning
            }

            // =============================
            // 1) Lock FIFO pickup lines (operator + item)
            // =============================
            $itemIds = array_keys($wantedByItem);
            $fifoGrouped = $this->loadLockedFifoPickupLinesByOperator($opId, $itemIds);

            // =============================
            // 2) Clamp: FIFO availability harus cukup
            // =============================
            $this->assertFifoAvailability($fifoGrouped, $wantedByItem, $opId);

            // =============================
            // 3) Mutasi inventory + update SR lines + allocate FIFO (qty_direct_picked)
            // =============================
            foreach ($wantedByItem as $itemId => $needQty) {

                // 3a) Inventory move sekali per item
                try {
                    $this->inventory->move(
                        itemId: (int) $itemId,
                        fromWarehouseId: (int) $sewingWh->id,
                        toWarehouseId: (int) $rtsWh->id,
                        qty: (float) $needQty,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'RTS direct pickup (SEWING/WIP → RTS) — FIFO by pickup date + operator',
                        date: $stockRequest->date ?? now(),
                        allowNegative: false
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                }

                // 3b) Update qty_picked pada StockRequestLine sesuai input UI (per-line SR)
                foreach ($stockRequest->lines->where('item_id', $itemId) as $srLine) {
                    $qtyLine = (float) ($wantedBySrLineId[$srLine->id] ?? 0);
                    if ($qtyLine <= 0.000001) {
                        continue;
                    }

                    $srLine->qty_picked = (float) $srLine->qty_picked + $qtyLine;
                    $srLine->save();

                    $any = true;
                }

                // 3c) FIFO allocation: update qty_direct_picked di SewingPickupLine (ngunci sumber)
                $fifoLines = $fifoGrouped->get($itemId, collect());
                $this->allocateFifoDirectPicked($fifoLines, (float) $needQty);
            }

            if (!$any) {
                return;
            }

            // =============================
            // 4) Update status StockRequest
            // =============================
            $stockRequest->refresh()->load('lines');

            $anyOutstanding = $stockRequest->lines->contains(function ($l) {
                $req = (float) $l->qty_request;
                $rec = (float) $l->qty_received;
                $pick = (float) $l->qty_picked;
                return max($req - $rec - $pick, 0) > 0.000001;
            });

            $stockRequest->status = $anyOutstanding ? 'partial' : 'completed';

            if ($stockRequest->status === 'completed') {
                $stockRequest->received_by_user_id = $stockRequest->received_by_user_id ?? Auth::id();
                $stockRequest->received_at = $stockRequest->received_at ?? now();
            }

            $stockRequest->save();
        });

        if (!$any) {
            return back()->with('warning', 'Tidak ada qty direct pickup yang diisi.');
        }

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', 'Direct pickup berhasil (SEWING/WIP → RTS) + terkunci FIFO per Sewing Pickup Line (operator+tanggal).');
    }

    public function quickToday(): RedirectResponse
    {
        $today = Carbon::today()->toDateString();

        $prd = Warehouse::where('code', 'WH-PRD')->first();
        $rts = Warehouse::where('code', 'WH-RTS')->first();

        $query = StockRequest::rtsReplenish()->whereDate('date', $today);

        if ($prd && $rts) {
            $query->where('source_warehouse_id', $prd->id)
                ->where('destination_warehouse_id', $rts->id);
        }

        $todayRequest = $query
            ->whereIn('status', ['submitted', 'shipped', 'partial', 'completed'])
            ->orderByDesc('id')
            ->first();

        if (!$todayRequest) {
            return redirect()->route('rts.stock-requests.create', [
                'date' => $today,
            ]);
        }

        // ✅ kalau masih berjalan: arahkan ke create(edit=1) supaya prefill outstanding
        if (in_array($todayRequest->status, ['submitted', 'shipped', 'partial'], true)) {
            return redirect()->route('rts.stock-requests.create', [
                'date' => $today,
                'edit' => 1,
            ]);
        }

        // completed -> show
        return redirect()->route('rts.stock-requests.show', $todayRequest);
    }

    protected function generateCodeForDate(Carbon $date): string
    {
        $prefix = 'SR-' . $date->format('Ymd');

        $last = StockRequest::where('code', 'like', $prefix . '%')
            ->orderByDesc('code')
            ->first();

        $next = 1;
        if ($last) {
            $next = ((int) substr($last->code, -3)) + 1;
        }

        return sprintf('%s-%03d', $prefix, $next);
    }

    /**
     * Ambil & lock FIFO pickup lines untuk operator + itemIds.
     * FIFO = sewing_pickups.date, sewing_pickups.id, sewing_pickup_lines.id
     */
    private function loadLockedFifoPickupLinesByOperator(
        int $operatorId,
        array $itemIds
    ): \Illuminate\Support\Collection {
        return SewingPickupLine::query()
            ->join('sewing_pickups', 'sewing_pickups.id', '=', 'sewing_pickup_lines.sewing_pickup_id')
            ->where('sewing_pickups.operator_id', $operatorId)
            ->whereIn('sewing_pickup_lines.finished_item_id', $itemIds)
            ->whereRaw('(sewing_pickup_lines.qty_bundle - (sewing_pickup_lines.qty_returned_ok + sewing_pickup_lines.qty_returned_reject + sewing_pickup_lines.qty_direct_picked)) > 0.000001')
            ->orderBy('sewing_pickups.date')
            ->orderBy('sewing_pickups.id')
            ->orderBy('sewing_pickup_lines.id')
            ->lockForUpdate()
            ->get([
                'sewing_pickup_lines.*',
                'sewing_pickups.date as pickup_date',
                'sewing_pickups.id as pickup_id_join',
                'sewing_pickups.operator_id as pickup_operator_id',
            ])
            ->groupBy('finished_item_id');
    }

/** Hitung sisa pickup line (remaining = bundle - returned_ok - returned_reject - direct_picked) */
    private function calcPickupLineRemaining(SewingPickupLine $pl): float
    {
        $qtyBundle = (float) ($pl->qty_bundle ?? 0);
        $retOk = (float) ($pl->qty_returned_ok ?? 0);
        $retRj = (float) ($pl->qty_returned_reject ?? 0);
        $dp = (float) ($pl->qty_direct_picked ?? 0);

        return max($qtyBundle - ($retOk + $retRj + $dp), 0);
    }

/**
 * Clamp: pastikan ketersediaan FIFO cukup untuk wantedByItem.
 * $fifoGrouped = hasil loadLockedFifoPickupLinesByOperator()->groupBy(item_id)
 */
    private function assertFifoAvailability(
        \Illuminate\Support\Collection $fifoGrouped,
        array $wantedByItem,
        int $operatorId
    ): void {
        foreach ($wantedByItem as $itemId => $need) {
            $lines = $fifoGrouped->get($itemId, collect());

            $avail = (float) $lines->sum(fn($pl) => $this->calcPickupLineRemaining($pl));

            if ((float) $need > $avail + 0.000001) {
                throw ValidationException::withMessages([
                    'lines' => "Sisa Sewing Pickup (operator #{$operatorId}) tidak cukup untuk item #{$itemId}. Butuh {$need}, tersedia {$avail}.",
                ]);
            }
        }
    }

/**
 * Alokasi FIFO: update qty_direct_picked pada pickup lines, dan (opsional) update bundle sewing_picked_qty.
 * Dipanggil di dalam DB::transaction + sudah lock FIFO lines (lockForUpdate).
 */
    private function allocateFifoDirectPicked(
        \Illuminate\Support\Collection $fifoLinesForItem,
        float $needQty
    ): void {
        $remaining = (float) $needQty;

        foreach ($fifoLinesForItem as $pl) {
            if ($remaining <= 0.000001) {
                break;
            }

            $avail = $this->calcPickupLineRemaining($pl);
            if ($avail <= 0.000001) {
                continue;
            }

            $take = min($avail, $remaining);

            $pl->qty_direct_picked = (float) ($pl->qty_direct_picked ?? 0) + $take;
            $pl->save();

            if (!empty($pl->cutting_job_bundle_id)) {
                CuttingJobBundle::query()
                    ->where('id', (int) $pl->cutting_job_bundle_id)
                    ->lockForUpdate()
                    ->update([
                        'sewing_picked_qty' => DB::raw('COALESCE(sewing_picked_qty,0) + ' . (float) $take),
                    ]);
            }

            $remaining -= $take;
        }

        if ($remaining > 0.000001) {
            throw ValidationException::withMessages([
                'lines' => "Alokasi FIFO pickup line gagal. Kurang {$remaining}.",
            ]);
        }
    }

}
