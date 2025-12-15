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
     * Daftar Stock Request RTS.
     */
    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'all'); // submitted | shipped | partial | completed | pending | all
        $period = $request->input('period', 'today'); // today | week | month | all

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
            ->with(['sourceWarehouse', 'destinationWarehouse', 'lines.item', 'requestedBy'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_dispatched_qty', 'qty_dispatched')
            ->withSum('lines as total_received_qty', 'qty_received');

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

        // Outstanding dihitung dari RECEIVED (karena tujuan akhirnya: stok masuk RTS)
        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $recv = (float) ($req->total_received_qty ?? 0);
                return max($reqQty - $recv, 0);
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
                $statusFilter = 'all';
                break;
        }

        $stockRequests = $listQuery
            ->orderBy('date', 'DESC')
            ->orderBy('id', 'DESC')
            ->paginate(20)
            ->withQueryString();

        return view('inventory.rts_stock_requests.index', [
            'stockRequests' => $stockRequests,
            'stats' => $stats,
            'outstandingQty' => $outstandingQty,
            'statusFilter' => $statusFilter,
            'period' => $period,
        ]);
    }

    /**
     * Form buat Stock Request dari RTS ke PRD.
     */
    public function create(Request $request): View
    {
        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        $dateParam = $request->input('date');
        $targetDate = $dateParam
        ? Carbon::parse($dateParam)->startOfDay()
        : Carbon::today()->startOfDay();

        $prefillRequest = StockRequest::rtsReplenish()
            ->whereDate('date', $targetDate->toDateString())
            ->where('source_warehouse_id', $prdWarehouse->id)
            ->where('destination_warehouse_id', $rtsWarehouse->id)
            ->with(['lines'])
            ->orderByDesc('id')
            ->first();

        $prefillDate = $targetDate->toDateString();
        $prefillLines = null;
        $prefillStatus = null;
        $prefillFromCompleted = false;

        if ($prefillRequest) {
            $prefillDate = $prefillRequest->date?->toDateString() ?? $prefillDate;
            $prefillStatus = $prefillRequest->status;
            $prefillFromCompleted = $prefillRequest->status === 'completed';

            $prefillLines = $prefillRequest->lines->map(function ($line) {
                return [
                    'item_id' => $line->item_id,
                    'qty_request' => $line->qty_request,
                ];
            })->toArray();
        }

        $finishedGoodsItems = Item::whereHas('inventoryStocks', function ($q) use ($prdWarehouse) {
            $q->where('warehouse_id', $prdWarehouse->id);
        })
            ->orderBy('name')
            ->get();

        return view('inventory.rts_stock_requests.create', [
            'prdWarehouse' => $prdWarehouse,
            'rtsWarehouse' => $rtsWarehouse,
            'finishedGoodsItems' => $finishedGoodsItems,
            'prefillDate' => $prefillDate,
            'prefillLines' => $prefillLines,
            'prefillRequest' => $prefillRequest,
            'prefillStatus' => $prefillStatus,
            'prefillFromCompleted' => $prefillFromCompleted,
        ]);
    }

    /**
     * Simpan / UPDATE Stock Request RTS (submitted).
     */
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
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $srcId = (int) $validated['source_warehouse_id'];
        $dstId = (int) $validated['destination_warehouse_id'];

        $stockRequest = null;
        $wasUpdated = false;

        DB::transaction(function () use (&$stockRequest, &$wasUpdated, $validated, $date, $srcId, $dstId) {
            $existing = StockRequest::rtsReplenish()
                ->whereDate('date', $date)
                ->where('source_warehouse_id', $srcId)
                ->where('destination_warehouse_id', $dstId)
                ->where('status', 'submitted')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                $wasUpdated = true;
                $stockRequest = $existing;
                $stockRequest->date = $date;
                $stockRequest->notes = $validated['notes'] ?? null;
                $stockRequest->save();
                $stockRequest->lines()->delete();
            } else {
                $wasUpdated = false;
                $stockRequest = StockRequest::create([
                    'code' => $this->generateCodeForDate($date),
                    'date' => $date,
                    'purpose' => 'rts_replenish',
                    'source_warehouse_id' => $srcId,
                    'destination_warehouse_id' => $dstId,
                    'status' => 'submitted',
                    'requested_by_user_id' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            foreach ($validated['lines'] as $i => $lineData) {
                $available = $this->inventory->getAvailableStock($srcId, (int) $lineData['item_id']);

                StockRequestLine::create([
                    'stock_request_id' => $stockRequest->id,
                    'line_no' => $i + 1,
                    'item_id' => (int) $lineData['item_id'],
                    'qty_request' => (float) $lineData['qty_request'],
                    'stock_snapshot_at_request' => $available,
                    'qty_dispatched' => 0,
                    'qty_received' => 0,
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', $wasUpdated
                ? 'Permintaan RTS diperbarui (status: submitted).'
                : 'Stock Request RTS berhasil dibuat dan dikirim ke PRD.'
            );
    }

    /**
     * Detail Stock Request (RTS).
     */
    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse', 'requestedBy']);

        $lines = $stockRequest->lines;

        $summary = [
            'total_lines' => $lines->count(),
            'total_requested_qty' => (float) $lines->sum('qty_request'),
            'total_dispatched' => (float) $lines->sum('qty_dispatched'),
            'total_received' => (float) $lines->sum('qty_received'),
            'outstanding_total' => (float) $lines->sum(function ($l) {
                return max((float) $l->qty_request - (float) $l->qty_received, 0);
            }),
        ];

        return view('inventory.rts_stock_requests.show', [
            'stockRequest' => $stockRequest,
            'summary' => $summary,
        ]);
    }

    /**
     * FORM RTS TERIMA (dari TRANSIT).
     */
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
            'totalRequested' => (float) $stockRequest->lines->sum('qty_request'),
            'totalDispatched' => (float) $stockRequest->lines->sum('qty_dispatched'),
            'totalReceived' => (float) $stockRequest->lines->sum('qty_received'),
        ]);
    }

    /**
     * RTS TERIMA: TRANSIT → RTS (akumulatif).
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

                // Guard: tidak boleh terima melebihi yang sudah dikirim ke transit
                $maxReceivable = max((float) $line->qty_dispatched - (float) $line->qty_received, 0);
                if ($qty > $maxReceivable + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}.qty_received" => "Qty diterima melebihi sisa yang tersedia di Transit (maks: {$maxReceivable}).",
                    ]);
                }

                $anyReceived = true;

                try {
                    $this->inventory->move(
                        $line->item_id,
                        $srcId,
                        $dstId,
                        $qty,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'RTS receive from TRANSIT',
                        date: $stockRequest->date ?? now(),
                        allowNegative: false
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                }

                $line->qty_received = (float) $line->qty_received + $qty;
                $line->save();
            }

            if (!$anyReceived) {
                return;
            }

            // Status berdasarkan dispatched + received
            $totalDispatched = (float) $stockRequest->lines->sum('qty_dispatched');
            $totalReceived = (float) $stockRequest->lines->sum('qty_received');

            $anyOutstanding = $stockRequest->lines->contains(function ($l) {
                $req = (float) $l->qty_request;
                $rec = (float) $l->qty_received;
                return max($req - $rec, 0) > 0;
            });

            if ($totalDispatched <= 0) {
                $stockRequest->status = 'submitted';
            } elseif ($totalReceived <= 0) {
                $stockRequest->status = 'shipped';
            } elseif ($anyOutstanding) {
                $stockRequest->status = 'partial';
            } else {
                $stockRequest->status = 'completed';
            }

            $stockRequest->save();
        });

        if (!$anyReceived) {
            return back()->with('warning', 'Tidak ada qty diterima yang diisi.');
        }

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', 'Penerimaan RTS berhasil (TRANSIT → RTS).');
    }

    /**
     * QUICK TODAY.
     */
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
            ->whereIn('status', ['submitted', 'shipped', 'partial'])
            ->orderByDesc('id')
            ->first();

        if ($todayRequest) {
            return redirect()->route('rts.stock-requests.show', $todayRequest);
        }

        return redirect()->route('rts.stock-requests.create');
    }

    /**
     * Generate kode dokumen: SR-YYYYMMDD-###.
     */
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
