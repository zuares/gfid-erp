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

    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'all'); // submitted|shipped|partial|completed|pending|all
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

        // Outstanding RTS = request - (received + picked)
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

    public function create(Request $request): View
    {
        $date = $request->input('date', Carbon::today()->toDateString());
        $edit = (bool) $request->boolean('edit');

        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        $prefillRequest = null;
        $prefillLines = null;
        $prefillDate = $date;

        if ($edit) {
            $prefillRequest = StockRequest::rtsReplenish()
                ->whereDate('date', $date)
                ->where('source_warehouse_id', $prdWarehouse->id)
                ->where('destination_warehouse_id', $rtsWarehouse->id)
                ->where('status', 'submitted')
                ->latest('id')
                ->with(['lines.item'])
                ->first();

            if ($prefillRequest) {
                $prefillDate = $prefillRequest->date?->toDateString() ?? $date;

                $prefillLines = $prefillRequest->lines
                    ->sortBy('line_no')
                    ->values()
                    ->map(fn($l) => [
                        'item_id' => $l->item_id,
                        'qty_request' => (float) $l->qty_request,
                    ])->toArray();
            }
        }

        $finishedGoodsItems = Item::query()
            ->select('id', 'code', 'name')
            ->where('type', 'finished_good') // sesuaikan dengan schema kamu
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

        $date = Carbon::parse($validated['date'])->startOfDay();
        $srcId = (int) $validated['source_warehouse_id'];
        $dstId = (int) $validated['destination_warehouse_id'];

        $stockRequest = null;
        $wasUpdated = false;

        DB::transaction(function () use (&$stockRequest, &$wasUpdated, $validated, $date, $srcId, $dstId) {
            $existing = StockRequest::rtsReplenish()
                ->whereDate('date', $date->toDateString())
                ->where('source_warehouse_id', $srcId)
                ->where('destination_warehouse_id', $dstId)
                ->where('status', 'submitted') // ✅ hanya bisa edit ketika masih menunggu PRD
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                $wasUpdated = true;
                $stockRequest = $existing;
                $stockRequest->date = $date->toDateString();
                $stockRequest->notes = $validated['notes'] ?? null;
                $stockRequest->save();

                $stockRequest->lines()->delete();
            } else {
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
            }

            foreach ($validated['lines'] as $i => $lineData) {
                $available = $this->inventory->getAvailableStock($srcId, (int) $lineData['item_id']);

                StockRequestLine::create([
                    'stock_request_id' => $stockRequest->id,
                    'line_no' => $i + 1,
                    'item_id' => (int) $lineData['item_id'],
                    'qty_request' => (float) $lineData['qty_request'],
                    'stock_snapshot_at_request' => (float) $available,

                    'qty_dispatched' => 0,
                    'qty_received' => 0,
                    'qty_picked' => 0,

                    'notes' => $lineData['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with('status', $wasUpdated
                ? 'Permintaan RTS berhasil diperbarui (menunggu PRD).'
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

            // refresh lines biar hitungan konsisten
            $stockRequest->load('lines');

            $anyOutstanding = $stockRequest->lines->contains(function ($l) {
                $req = (float) $l->qty_request;
                $rec = (float) $l->qty_received;
                $pick = (float) $l->qty_picked;
                return max($req - $rec - $pick, 0) > 0;
            });

            $totalDispatched = (float) $stockRequest->lines->sum('qty_dispatched');
            $totalFulfilled = (float) $stockRequest->lines->sum(fn($l) => (float) $l->qty_received + (float) $l->qty_picked);

            if ($totalDispatched <= 0 && $totalFulfilled <= 0) {
                $stockRequest->status = 'submitted';
            } elseif ($totalFulfilled <= 0) {
                $stockRequest->status = 'shipped';
            } elseif ($anyOutstanding) {
                $stockRequest->status = 'partial';
            } else {
                $stockRequest->status = 'completed';
                $stockRequest->received_by_user_id = $stockRequest->received_by_user_id ?? Auth::id();
                $stockRequest->received_at = $stockRequest->received_at ?? now();
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

    public function directPickup(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if($stockRequest->status === 'completed', 404);

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_picked' => ['nullable', 'numeric', 'gte:0'],
            'notes' => ['nullable', 'string'],
        ]);

        $prd = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rts = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        $any = false;

        DB::transaction(function () use (&$any, $validated, $stockRequest, $prd, $rts) {
            $stockRequest->load('lines');

            if (!empty($validated['notes'])) {
                $append = trim($validated['notes']);
                $stockRequest->notes = trim(($stockRequest->notes ?? '') . "\n" . $append);
            }

            foreach ($stockRequest->lines as $line) {
                $input = $validated['lines'][$line->id] ?? null;
                $qty = $input && isset($input['qty_picked']) ? (float) $input['qty_picked'] : 0.0;

                if ($qty <= 0) {
                    continue;
                }

                $already = (float) $line->qty_received + (float) $line->qty_picked;
                $max = max((float) $line->qty_request - $already, 0);

                if ($qty > $max + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$line->id}.qty_picked" => "Qty melebihi sisa request (maks: {$max}).",
                    ]);
                }

                $any = true;

                try {
                    $this->inventory->move(
                        itemId: (int) $line->item_id,
                        fromWarehouseId: (int) $prd->id,
                        toWarehouseId: (int) $rts->id,
                        qty: $qty,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'RTS direct pickup (PRD → RTS)',
                        date: $stockRequest->date ?? now(),
                        allowNegative: true
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages(['stock' => $e->getMessage()]);
                }

                $line->qty_picked = (float) $line->qty_picked + $qty;
                $line->save();
            }

            if (!$any) {
                return;
            }

            $stockRequest->load('lines');

            $anyOutstanding = $stockRequest->lines->contains(function ($l) {
                $req = (float) $l->qty_request;
                $rec = (float) $l->qty_received;
                $pick = (float) $l->qty_picked;
                return max($req - $rec - $pick, 0) > 0;
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
            ->with('status', 'Direct pickup berhasil (PRD → RTS).');
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

        // ✅ kalau belum ada → create baru (isi tanggal hari ini)
        if (!$todayRequest) {
            return redirect()->route('rts.stock-requests.create', [
                'date' => $today,
            ]);
        }

        // ✅ kalau masih "Menunggu PRD" → masuk create (edit)
        if ($todayRequest->status === 'submitted') {
            return redirect()->route('rts.stock-requests.create', [
                'date' => $today,
                'edit' => 1,
            ]);
        }

        // lainnya → show
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
