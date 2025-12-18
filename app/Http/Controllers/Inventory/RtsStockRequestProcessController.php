<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\StockRequest;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class RtsStockRequestProcessController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    public function index(Request $request): View
    {
        $statusFilter = $request->input('status', 'all');
        $period = $request->input('period', 'today');

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
            ->with(['destinationWarehouse', 'sourceWarehouse'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_dispatched_qty', 'qty_dispatched')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->withSum('lines as total_picked_qty', 'qty_picked'); // boleh tetap ditampilkan, tapi PRD outstanding tidak pakai picked

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

        /**
         * ✅ FINAL (sesuai jawaban kamu #10 = C)
         * Outstanding PRD = requested - dispatched - received
         * - dispatched: PRD -> TRANSIT
         * - received: TRANSIT -> RTS (barang benar-benar sudah sampai)
         */
        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_dispatched_qty', 'qty_dispatched')
            ->withSum('lines as total_received_qty', 'qty_received')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $dispQty = (float) ($req->total_dispatched_qty ?? 0);
                $recvQty = (float) ($req->total_received_qty ?? 0);

                return max($reqQty - $dispQty - $recvQty, 0);
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

        return view('inventory.prd_stock_requests.index', [
            'stockRequests' => $stockRequests,
            'stats' => $stats,
            'outstandingQty' => $outstandingQty,
            'statusFilter' => $statusFilter,
            'period' => $period,
        ]);
    }

    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse', 'requestedBy']);

        $movementHistory = InventoryMutation::with(['item', 'warehouse'])
            ->where('source_type', 'stock_request')
            ->where('source_id', $stockRequest->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return view('inventory.prd_stock_requests.show', [
            'stockRequest' => $stockRequest,
            'movementHistory' => $movementHistory,
        ]);
    }

    public function edit(StockRequest $stockRequest): View | RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        if ($stockRequest->status === 'completed') {
            return redirect()
                ->route('prd.stock-requests.show', $stockRequest)
                ->with('status', 'Dokumen sudah selesai, tidak bisa diproses lagi.');
        }

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $sourceWarehouseId = (int) $stockRequest->source_warehouse_id;

        $liveStocks = [];
        foreach ($stockRequest->lines as $line) {
            $liveStocks[$line->id] = $this->inventory->getAvailableStock(
                $sourceWarehouseId,
                $line->item_id
            );
        }

        // ✅ Kamu pilih history lebih cocok di laporan mutasi, bukan di edit.
        // Jadi edit() tidak perlu bentuk historyRows lagi.

        return view('inventory.prd_stock_requests.edit', [
            'stockRequest' => $stockRequest,
            'liveStocks' => $liveStocks,
        ]);
    }

    /**
     * PRD KIRIM: PRD → TRANSIT
     * allowNegative=true (PRD boleh minus) sesuai jawaban kamu #7 = A
     *
     * Input: lines[line_id][qty_issued]
     */
    public function confirm(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        if ($stockRequest->status === 'completed') {
            return back()->with('warning', 'Dokumen sudah completed.');
        }

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_issued' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $linesInput = $validated['lines'] ?? [];

        $transit = Warehouse::where('code', 'WH-TRANSIT')->first();
        if (!$transit) {
            return back()->with('warning', 'Gudang WH-TRANSIT belum dibuat.');
        }

        $sourceWarehouseId = (int) $stockRequest->source_warehouse_id; // PRD
        $transitWarehouseId = (int) $transit->id; // TRANSIT

        $anyDispatched = false;

        DB::transaction(function () use (
            &$anyDispatched,
            $stockRequest,
            $linesInput,
            $sourceWarehouseId,
            $transitWarehouseId
        ) {
            $stockRequest->load('lines');

            foreach ($stockRequest->lines as $line) {
                $lineId = $line->id;
                $input = $linesInput[$lineId] ?? null;

                $qtyToDispatch = 0.0;
                if ($input !== null && isset($input['qty_issued'])) {
                    $qtyToDispatch = (float) $input['qty_issued'];
                }

                if ($qtyToDispatch <= 0) {
                    continue;
                }

                /**
                 * ✅ GUARD:
                 * Dispatch tidak boleh melebihi sisa kebutuhan yang belum terpenuhi.
                 * Karena status single, kita tetap proteksi dari over-fulfill.
                 *
                 * maxDispatch = request - (dispatched + received + picked)
                 * - dispatched: sudah dikirim ke transit
                 * - received: sudah sampai RTS
                 * - picked: sudah terpenuhi via jalur pickup (dari sewing/WIP)
                 */
                $alreadyDispatched = (float) ($line->qty_dispatched ?? 0);
                $alreadyReceived = (float) ($line->qty_received ?? 0);
                $picked = (float) ($line->qty_picked ?? 0);
                $requested = (float) ($line->qty_request ?? 0);

                $maxDispatch = max($requested - $alreadyDispatched - $alreadyReceived - $picked, 0);

                if ($qtyToDispatch > $maxDispatch + 0.0000001) {
                    throw ValidationException::withMessages([
                        "lines.{$lineId}.qty_issued" => "Qty kirim melebihi sisa kebutuhan (maks: {$maxDispatch}). Sudah ada receive / pickup / dispatch sebelumnya.",
                    ]);
                }

                $anyDispatched = true;

                try {
                    $this->inventory->move(
                        itemId: (int) $line->item_id,
                        fromWarehouseId: $sourceWarehouseId,
                        toWarehouseId: $transitWarehouseId,
                        qty: $qtyToDispatch,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'PRD dispatch to TRANSIT',
                        date: $stockRequest->date ?? now(),
                        allowNegative: true, // ✅ PRD boleh minus
                    );
                } catch (\RuntimeException $e) {
                    throw ValidationException::withMessages([
                        'stock' => $e->getMessage(),
                    ]);
                }

                $line->qty_dispatched = (float) ($line->qty_dispatched ?? 0) + $qtyToDispatch;
                $line->save();
            }

            if (!$anyDispatched) {
                return;
            }

            /**
             * ✅ RULE FINAL:
             * PRD confirm() TIDAK BOLEH set completed.
             * PRD hanya menandai bahwa sudah ada dispatch -> shipped.
             * Status partial/completed akan ditangani RTS ketika receive/pick.
             */
            if (in_array($stockRequest->status, ['submitted', 'shipped'])) {
                $stockRequest->status = 'shipped';
            }

            $stockRequest->save();
        });

        if (!$anyDispatched) {
            return back()->with('warning', 'Tidak ada Qty kirim yang diisi. Isi minimal satu baris.');
        }

        return redirect()
            ->route('prd.stock-requests.edit', $stockRequest)
            ->with('status', 'Pengiriman PRD → TRANSIT berhasil diproses.');
    }
}
