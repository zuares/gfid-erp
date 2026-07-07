<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\Item;
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

    /**
     * List RTS Requests
     * Status: submitted | shipped | partial | completed | all
     * Outstanding RTS: requested - received - picked
     */
    public function index(Request $request): View
    {
        // ✅ Default tab: ALL untuk status & period
        $statusFilter = (string) $request->input('status', 'all');
        $period       = (string) $request->input('period', 'all');
        $search       = trim((string) $request->input('search', ''));
        $dateFrom     = $request->input('date_from');
        $dateTo       = $request->input('date_to');

        if (!in_array($statusFilter, ['submitted', 'shipped', 'partial', 'completed', 'all'], true)) {
            $statusFilter = 'all';
        }
        if (!in_array($period, ['today', 'week', 'month', 'all'], true)) {
            $period = 'all';
        }

        $now = Carbon::now();

        // period preset overrides manual date only when no manual date given
        if (!$dateFrom && !$dateTo) {
            switch ($period) {
                case 'today':
                    $dateFrom = $now->copy()->startOfDay()->toDateString();
                    $dateTo   = $now->copy()->endOfDay()->toDateString();
                    break;
                case 'week':
                    $dateFrom = $now->copy()->startOfWeek()->toDateString();
                    $dateTo   = $now->copy()->endOfWeek()->toDateString();
                    break;
                case 'month':
                    $dateFrom = $now->copy()->startOfMonth()->toDateString();
                    $dateTo   = $now->copy()->endOfMonth()->toDateString();
                    break;
            }
        } else {
            // manual date → reset period to 'all'
            $period = 'all';
        }

        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom) $query->where('date', '>=', $dateFrom);
            if ($dateTo)   $query->where('date', '<=', $dateTo);
            return $query;
        };

        $baseQuery = StockRequest::rtsReplenish()
            ->with(['sourceWarehouse', 'destinationWarehouse', 'requestedBy', 'lines.item'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_picked_qty', 'qty_picked')
            ->withSum('lines as total_received_qty', 'qty_received');

        $baseQuery = $applyDateFilter($baseQuery);

        $statsBase = StockRequest::rtsReplenish();
        $statsBase = $applyDateFilter($statsBase);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->whereIn('status', ['submitted', 'shipped'])->count(),
            'partial' => (clone $statsBase)->where('status', 'partial')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
        ];

        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->withSum('lines as total_picked_qty', 'qty_picked')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $recvQty = (float) ($req->total_received_qty ?? 0);
                $pickQty = (float) ($req->total_picked_qty ?? 0);
                return max($reqQty - $recvQty - $pickQty, 0);
            });

        $listQuery = clone $baseQuery;

        // status filter
        if ($statusFilter === 'submitted') {
            $listQuery->whereIn('status', ['submitted', 'shipped']);
        } elseif (in_array($statusFilter, ['shipped', 'partial', 'completed'], true)) {
            $listQuery->where('status', $statusFilter);
        } else {
            $statusFilter = 'all';
        }

        // search: by code or item code
        if ($search !== '') {
            $listQuery->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhereHas('lines.item', fn($qi) => $qi->where('code', 'like', "%{$search}%"));
            });
        }

        $stockRequests = $listQuery
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.rts_stock_requests.index', compact(
            'stockRequests',
            'stats',
            'outstandingQty',
            'statusFilter',
            'period',
            'search',
            'dateFrom',
            'dateTo',
        ));
    }

    /**
     * Create RTS Request
     * edit=1 -> prefill outstanding dari dokumen submitted hari yang sama (PRD->RTS)
     */
    public function create(Request $request): View
    {
        $date = $request->input('date', Carbon::today()->toDateString());

        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        $finishedGoodsItems = Item::query()
            ->select('id', 'code', 'name')
            ->where('type', 'finished_good')
            ->orderBy('code')
            ->get();

        $prdStockByItem = DB::table('inventory_stocks')
            ->where('warehouse_id', $prdWarehouse->id)
            ->whereIn('item_id', $finishedGoodsItems->pluck('id'))
            ->pluck('qty', 'item_id');

        // ✅ selalu dokumen baru
        $prefillDate = $date;
        $prefillLines = [];

        return view('inventory.rts_stock_requests.create', compact(
            'prdWarehouse',
            'rtsWarehouse',
            'finishedGoodsItems',
            'prdStockByItem',
            'prefillDate',
            'prefillLines',
        ));
    }

    /**
     * Store RTS Request
     * - kalau ada dokumen submitted yang sama (date+src+dst) -> append qty_request
     * - kalau sudah completed -> bikin dokumen baru
     */
    public function store(Request $request): RedirectResponse
    {
        $role = strtolower((string) (auth()->user()?->role ?? ''));
        if (!in_array($role, ['owner', 'admin'], true)) {
            abort(403);
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'source_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],
            'destination_warehouse_id' => ['required', 'integer', 'exists:warehouses,id'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_request' => ['required', 'numeric', 'gt:0'], // ✅ qty harus > 0
        ], [
            'lines.required' => 'Minimal 1 baris item.',
            'lines.*.qty_request.gt' => 'Qty harus > 0.',
        ]);

        // ✅ anti duplicate server-side
        $itemIds = array_map(fn($r) => (int) $r['item_id'], $data['lines'] ?? []);
        if (count($itemIds) !== count(array_unique($itemIds))) {
            throw ValidationException::withMessages([
                'lines' => 'Item tidak boleh duplikat. Hapus salah satu baris yang sama.',
            ]);
        }

        // optional: jangan boleh PRD=RTS
        if ((int) $data['source_warehouse_id'] === (int) $data['destination_warehouse_id']) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'Gudang tujuan harus berbeda dari gudang sumber.',
            ]);
        }

        // ✅ Tolak jika stok WH-PRD kosong untuk salah satu item
        $srcWhId = (int) $data['source_warehouse_id'];
        $prdStocks = DB::table('inventory_stocks')
            ->where('warehouse_id', $srcWhId)
            ->whereIn('item_id', $itemIds)
            ->pluck('qty', 'item_id');

        $emptyErrors = [];
        foreach ($data['lines'] as $idx => $row) {
            $iid = (int) $row['item_id'];
            $stock = (float) ($prdStocks[$iid] ?? 0);
            if ($stock <= 0) {
                $itemCode = DB::table('items')->where('id', $iid)->value('code') ?? "item #{$iid}";
                $emptyErrors["lines.{$idx}.qty_request"] = "{$itemCode}: stok WH-PRD kosong (0), permintaan ditolak.";
            }
        }
        if (!empty($emptyErrors)) {
            throw ValidationException::withMessages($emptyErrors);
        }

        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['date'])->toDateString();

            $code = $this->generateRtsCode($date);

            $sr = new StockRequest();
            $sr->code = $code;
            $sr->date = $date;
            $sr->purpose = 'rts_replenish';
            $sr->status = 'completed';
            $sr->source_warehouse_id = (int) $data['source_warehouse_id'];
            $sr->destination_warehouse_id = (int) $data['destination_warehouse_id'];
            $sr->received_by_user_id = Auth::id();
            $sr->received_at = now();

            // sesuaikan kolom creator kamu:
            if (property_exists($sr, 'created_by')) {
                $sr->created_by = auth()->id();
            } elseif (property_exists($sr, 'created_by_id')) {
                $sr->created_by_id = auth()->id();
            }

            $sr->save();

            $lineNo = 1;

            foreach ($data['lines'] as $row) {
                $qty = (float) $row['qty_request'];
                $itemId = (int) $row['item_id'];

                try {
                    $this->inventory->move(
                        itemId: $itemId,
                        fromWarehouseId: (int) $data['source_warehouse_id'],
                        toWarehouseId: (int) $data['destination_warehouse_id'],
                        qty: $qty,
                        referenceType: 'stock_request',
                        referenceId: $sr->id,
                        notes: 'RTS direct receive (PRD → RTS)',
                        date: $date,
                        allowNegative: true,
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                }

                StockRequestLine::create([
                    'stock_request_id' => $sr->id,
                    'line_no' => $lineNo++,
                    'item_id' => $itemId,
                    'qty_request' => $qty,
                    'qty_dispatched' => 0,
                    'qty_received' => $qty,
                ]);
            }

            return redirect()
                ->route('rts.stock-requests.show', $sr)
                ->with('success', 'Terima Jadi berhasil. Stok WH-PRD langsung berpindah ke WH-RTS.');
        });
    }

/**
 * Generate code RTS yang aman dari race-condition.
 * Format: RTS-YYYYMMDD-#### (increment per tanggal)
 */
    private function generateRtsCode(string $date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');
        $prefix = "RTS-$ymd-";

        // Lock row set by prefix (paling aman pakai lockForUpdate)
        // Cari code terbesar untuk tanggal yang sama
        $lastCode = DB::table('stock_requests')
            ->where('purpose', 'rts_replenish')
            ->where('code', 'like', $prefix . '%')
            ->lockForUpdate()
            ->orderByDesc('code')
            ->value('code');

        $next = 1;
        if ($lastCode && str_starts_with((string) $lastCode, $prefix)) {
            $tail = substr((string) $lastCode, strlen($prefix));
            $n = (int) ltrim($tail, '0');
            $next = max($n + 1, 1);
        }

        return $prefix . str_pad((string) $next, 4, '0', STR_PAD_LEFT);
    }
    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse', 'requestedBy']);

        return view('inventory.rts_stock_requests.show', compact('stockRequest'));
    }

    /**
     * Halaman cetak barcode stock request.
     * Default jumlah label per item = qty diterima (jika ada), fallback qty diminta.
     */
    public function barcode(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'destinationWarehouse']);

        $lines = $stockRequest->lines
            ->filter(fn ($l) => $l->item && $l->item->code)
            ->map(function ($l) {
                $received = (float) ($l->qty_received ?? 0);
                $request  = (float) ($l->qty_request ?? 0);
                $qty      = $received > 0 ? $received : $request;
                return [
                    'id'   => $l->item->id,
                    'code' => $l->item->code,
                    'name' => $l->item->name,
                    'qty'  => max(1, (int) round($qty)),
                ];
            })
            ->values();

        return view('inventory.rts_stock_requests.barcode', compact('stockRequest', 'lines'));
    }

    /**
     * Receive form: input qty yang diambil langsung dari WH-PRD ke WH-RTS.
     */
    public function confirmReceive(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_unless(in_array($stockRequest->status, ['submitted', 'shipped', 'partial'], true), 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $srcId = (int) $stockRequest->source_warehouse_id;

        $liveStocks = [];
        foreach ($stockRequest->lines as $line) {
            $liveStocks[$line->id] = $this->inventory->getAvailableStock($srcId, $line->item_id);
        }

        return view('inventory.rts_stock_requests.confirm', compact('stockRequest', 'liveStocks'));
    }

    /**
     * FINALIZE RECEIVE: PRD → RTS
     */
    public function finalize(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_unless(in_array($stockRequest->status, ['submitted', 'shipped', 'partial'], true), 404);

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_received' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $srcId = (int) $stockRequest->source_warehouse_id; // PRD
        $dstId = (int) $stockRequest->destination_warehouse_id; // RTS

        $anyReceived = false;

        DB::transaction(function () use (&$anyReceived, $stockRequest, $validated, $srcId, $dstId) {
            $stockRequest->load('lines');

            foreach ($stockRequest->lines as $line) {
                $input = $validated['lines'][$line->id] ?? null;
                $qty = ($input && isset($input['qty_received'])) ? (float) $input['qty_received'] : 0.0;

                if ($qty <= 0.000001) {
                    continue;
                }

                $alreadyReceived = (float) ($line->qty_received ?? 0);
                $picked = (float) ($line->qty_picked ?? 0);
                $requested = (float) ($line->qty_request ?? 0);
                $maxReceivable = max($requested - $alreadyReceived - $picked, 0);

                if ($qty > $maxReceivable + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}.qty_received" => "Qty terima melebihi sisa permintaan untuk item ini (maks: {$maxReceivable}).",
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
                        notes: 'RTS receive (PRD → RTS)',
                        date: $stockRequest->date ?? now(),
                        allowNegative: true,
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                }

                $line->qty_received = (float) ($line->qty_received ?? 0) + $qty;
                $line->save();
            }

            if (!$anyReceived) {
                return;
            }

            $stockRequest->load('lines');

            $reqTotal = (float) $stockRequest->lines->sum('qty_request');
            $recvTotal = (float) $stockRequest->lines->sum('qty_received');
            $pickTotal = (float) $stockRequest->lines->sum('qty_picked');

            $stockRequest->status = ($recvTotal + $pickTotal) + 0.0000001 >= $reqTotal
                ? 'completed'
                : 'partial';
            $stockRequest->received_by_user_id = $stockRequest->received_by_user_id ?? Auth::id();
            $stockRequest->received_at = $stockRequest->received_at ?? now();
            $stockRequest->save();
        });

        if (!$anyReceived) {
            return back()->with('warning', 'Tidak ada qty diterima yang diisi.');
        }

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', 'Penerimaan berhasil. Stok dipindahkan dari WH-PRD ke WH-RTS.');
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

        if ($todayRequest->status === 'submitted') {
            return redirect()->route('rts.stock-requests.create', [
                'date' => $today,
                'edit' => 1,
            ]);
        }

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
}
