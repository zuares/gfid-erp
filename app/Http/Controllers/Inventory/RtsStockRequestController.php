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
     * Status: submitted | completed | all
     * Outstanding: requested - received
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'submitted'); // submitted|completed|all
        $period = $request->input('period', 'today'); // today|week|month|all

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
            ->withSum('lines as total_received_qty', 'qty_received');

        $baseQuery = $applyDateFilter($baseQuery);

        $statsBase = StockRequest::rtsReplenish();
        $statsBase = $applyDateFilter($statsBase);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->where('status', 'submitted')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
        ];

        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $recvQty = (float) ($req->total_received_qty ?? 0);
                return max($reqQty - $recvQty, 0);
            });

        $listQuery = clone $baseQuery;

        switch ($statusFilter) {
            case 'submitted':
            case 'completed':
                $listQuery->where('status', $statusFilter);
                break;
            case 'all':
            default:
                $statusFilter = 'all';
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

        // ✅ selalu dokumen baru
        $prefillDate = $date;
        $prefillLines = [];

        return view('inventory.rts_stock_requests.create', compact(
            'prdWarehouse',
            'rtsWarehouse',
            'finishedGoodsItems',
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

        return DB::transaction(function () use ($data) {
            $date = Carbon::parse($data['date'])->toDateString();

            $code = $this->generateRtsCode($date);

            $sr = new StockRequest();
            $sr->code = $code;
            $sr->date = $date;
            $sr->purpose = 'rts_replenish';
            $sr->status = 'submitted';
            $sr->source_warehouse_id = (int) $data['source_warehouse_id'];
            $sr->destination_warehouse_id = (int) $data['destination_warehouse_id'];

            // sesuaikan kolom creator kamu:
            if (property_exists($sr, 'created_by')) {
                $sr->created_by = auth()->id();
            } elseif (property_exists($sr, 'created_by_id')) {
                $sr->created_by_id = auth()->id();
            }

            $sr->save();

            $lines = [];
            $lineNo = 1;

            foreach ($data['lines'] as $row) {
                $lines[] = [
                    'stock_request_id' => $sr->id,
                    'line_no' => $lineNo++,
                    'item_id' => (int) $row['item_id'],
                    'qty_request' => (float) $row['qty_request'],
                    'qty_dispatched' => 0,
                    'qty_received' => 0,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }

            StockRequestLine::insert($lines);

            return redirect()
                ->route('rts.stock-requests.show', $sr)
                ->with('success', 'Permintaan RTS berhasil dibuat.');
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
     * Receive form: input qty yang datang.
     * Live stock ambil dari source warehouse SR (PRD).
     */
    public function confirmReceive(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if($stockRequest->status !== 'submitted', 404);

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
        abort_if($stockRequest->status !== 'submitted', 404);

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

                // ✅ NEW RULE: BOLEH terima melebihi request
                // Jadi TIDAK ADA guard maxReceivable lagi.

                $anyReceived = true;

                try {
                    $this->inventory->move(
                        itemId: (int) $line->item_id,
                        fromWarehouseId: $srcId,
                        toWarehouseId: $dstId,
                        qty: $qty,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'RTS receive (PRD → RTS) — allow over-receive',
                        date: $stockRequest->date ?? now(),
                        allowNegative: true, // ✅ PRD boleh minus
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

            $stockRequest->status = 'completed';
            $stockRequest->received_by_user_id = $stockRequest->received_by_user_id ?? Auth::id();
            $stockRequest->received_at = $stockRequest->received_at ?? now();
            $stockRequest->save();
        });

        if (!$anyReceived) {
            return back()->with('warning', 'Tidak ada qty diterima yang diisi.');
        }

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', 'Penerimaan berhasil. PRD→RTS diposting (PRD boleh minus) dan dokumen ditutup (completed).');
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
            ->whereIn('status', ['submitted', 'completed'])
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
