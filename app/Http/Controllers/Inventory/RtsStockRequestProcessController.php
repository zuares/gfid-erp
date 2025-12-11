<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\InventoryMutation;
use App\Models\StockRequest;
use App\Services\Inventory\InventoryService;
use Illuminate\Contracts\View\View;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;

class RtsStockRequestProcessController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Daftar permintaan RTS yang masuk ke PRD.
     */
    public function index(Request $request): View
    {
        // 🔹 filter dari query string
        $statusFilter = $request->input('status', 'all'); // pending | submitted | partial | completed | all
        $period = $request->input('period', 'today'); // today | week | month | all

        // ========== HITUNG RANGE TANGGAL BERDASARKAN PERIOD ==========
        $dateFrom = null;
        $dateTo = null;

        switch ($period) {
            case 'week':
                $dateFrom = Carbon::now()->startOfWeek(); // Senin
                $dateTo = Carbon::now()->endOfWeek();
                break;

            case 'month':
                $dateFrom = Carbon::now()->startOfMonth();
                $dateTo = Carbon::now()->endOfMonth();
                break;

            case 'all':
                // tanpa batas tanggal
                break;

            case 'today':
            default:
                $dateFrom = Carbon::today();
                $dateTo = Carbon::today();
                $period = 'today';
                break;
        }

        // Helper closure untuk apply filter tanggal ke query manapun
        $applyDateFilter = function ($query) use ($dateFrom, $dateTo) {
            if ($dateFrom && $dateTo) {
                $query->whereBetween('date', [
                    $dateFrom->copy()->startOfDay(),
                    $dateTo->copy()->endOfDay(),
                ]);
            }
            return $query;
        };

        // ========== BASE QUERY: hanya RTS Replenish ==========
        $baseQuery = StockRequest::rtsReplenish()
            ->with(['destinationWarehouse', 'sourceWarehouse'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_issued_qty', 'qty_issued');

        $baseQuery = $applyDateFilter($baseQuery);

        // ========== DASHBOARD STATS (ikut period juga) ==========
        $statsBase = StockRequest::rtsReplenish();
        $statsBase = $applyDateFilter($statsBase);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->where('status', 'submitted')->count(),
            'partial' => (clone $statsBase)->where('status', 'partial')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
        ];
        $stats['pending'] = $stats['submitted'] + $stats['partial'];

        // Outstanding qty (request - issued) untuk semua dokumen dalam period
        $outstandingQty = (clone $statsBase)
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_issued_qty', 'qty_issued')
            ->get()
            ->sum(function ($req) {
                $reqQty = (float) ($req->total_requested_qty ?? 0);
                $issuedQty = (float) ($req->total_issued_qty ?? 0);
                return max($reqQty - $issuedQty, 0);
            });

        // ========== FILTER LIST VIEW (status + period) ==========
        $listQuery = clone $baseQuery;

        switch ($statusFilter) {
            case 'submitted':
                $listQuery->where('status', 'submitted');
                break;

            case 'partial':
                $listQuery->where('status', 'partial');
                break;

            case 'completed':
                $listQuery->where('status', 'completed');
                break;

            case 'pending':
                // Pending = submitted + partial
                $listQuery->whereIn('status', ['submitted', 'partial']);
                break;

            case 'all':
            default:
                // tidak filter status
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

        // histori movement kalau sudah ada mutasi (mungkin nanti dipakai)
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

    /**
     * Halaman PRD isi rencana Qty Kirim (belum mutasi stok).
     */
    public function edit(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        // ⬅️ tambah guard status
        // abort_if($stockRequest->status === 'completed', 404);
        // atau kalau mau lebih soft:
        if ($stockRequest->status === 'completed') {
            return redirect()
                ->route('prd.stock-requests.show', $stockRequest)
                ->with('status', 'Dokumen sudah selesai, rencana kirim tidak bisa diubah lagi.');
        }

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $sourceWarehouseId = $stockRequest->source_warehouse_id;

        $liveStocks = [];
        foreach ($stockRequest->lines as $line) {
            $liveStocks[$line->id] = $this->inventory->getAvailableStock(
                $sourceWarehouseId,
                $line->item_id
            );
        }

        $defaultQtyIssued = [];
        foreach ($stockRequest->lines as $line) {
            $defaultQtyIssued[$line->id] = (float) $line->qty_issued;
        }

        $movementHistory = InventoryMutation::with(['item', 'warehouse'])
            ->where('source_type', 'stock_request')
            ->where('source_id', $stockRequest->id)
            ->orderBy('date')
            ->orderBy('id')
            ->get();

        return view('inventory.prd_stock_requests.edit', [
            'stockRequest' => $stockRequest,
            'liveStocks' => $liveStocks,
            'defaultQtyIssued' => $defaultQtyIssued,
            'movementHistory' => $movementHistory,
        ]);
    }

    /**
     * Simpan rencana Qty Kirim dari PRD (boleh > outstanding & > stok, TANPA mutasi).
     * RTS nanti yang konfirmasi fisik & trigger mutasi stok.
     */
    public function confirm(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load('lines');

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_issued' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $linesInput = $validated['lines'];

        DB::transaction(function () use ($stockRequest, $linesInput) {
            foreach ($stockRequest->lines as $line) {
                $input = $linesInput[$line->id] ?? [];

                $qtyIssued = isset($input['qty_issued'])
                ? (float) $input['qty_issued']
                : 0.0;

                // 🔹 Simpan apa adanya, boleh > request / > stok
                $line->qty_issued = $qtyIssued;
                $line->save();
            }

            // 🔹 Untuk tahap ini kita TIDAK mengubah status & TIDAK mutasi stok.
            // Status bisa tetap 'submitted' sampai RTS konfirmasi fisik & mutasi stok.
        });

        return redirect()
            ->route('prd.stock-requests.edit', $stockRequest)
            ->with('status', 'Rencana Qty Kirim sudah disimpan. RTS bisa konfirmasi jumlah fisik sebelum stok dipindahkan.');
    }

    /**
     * Kalau nanti kamu butuh tahap akhir (setelah RTS konfirmasi fisik) untuk mutasi stok,
     * bisa tambahkan method lain di sini, misalnya finalize(), yang baru panggil InventoryService::move().
     */
}
