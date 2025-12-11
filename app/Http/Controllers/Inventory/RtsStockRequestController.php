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

class RtsStockRequestController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {}

    /**
     * Daftar Stock Request RTS (permintaan dari RTS ke PRD).
     */
    public function index(Request $request): View
    {
        // 🔹 default status = all supaya completed juga langsung kelihatan
        $statusFilter = $request->input('status', 'all'); // pending | submitted | partial | completed | all
        $period = $request->input('period', 'today'); // today | week | month | all

        // ========== HITUNG RANGE TANGGAL BERDASARKAN PERIOD ==========
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
                // tanpa batas tanggal
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

        // ========== BASE QUERY: hanya RTS Replenish ==========
        $baseQuery = StockRequest::rtsReplenish()
            ->with(['sourceWarehouse', 'destinationWarehouse', 'lines.item', 'requestedBy'])
            ->withSum('lines as total_requested_qty', 'qty_request')
            ->withSum('lines as total_issued_qty', 'qty_issued');

        $baseQuery = $applyDateFilter($baseQuery);

        // ========== DASHBOARD STATS ==========
        $statsBase = StockRequest::rtsReplenish();
        $statsBase = $applyDateFilter($statsBase);

        $stats = [
            'total' => (clone $statsBase)->count(),
            'submitted' => (clone $statsBase)->where('status', 'submitted')->count(),
            'partial' => (clone $statsBase)->where('status', 'partial')->count(),
            'completed' => (clone $statsBase)->where('status', 'completed')->count(),
        ];
        $stats['pending'] = $stats['submitted'] + $stats['partial'];

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
                $listQuery->whereIn('status', ['submitted', 'partial']);
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
     * RTS boleh request item meskipun stok di WH-PRD sedang 0 / kurang.
     */

    public function create(Request $request): View
    {
        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->firstOrFail();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->firstOrFail();

        // ====== TARGET TANGGAL (UNTUK PREFILL) ======
        $dateParam = $request->input('date');
        $targetDate = $dateParam
        ? Carbon::parse($dateParam)->startOfDay()
        : Carbon::today()->startOfDay();

        // Cari dokumen RTS hari itu (WH-PRD → WH-RTS)
        $prefillRequest = StockRequest::rtsReplenish()
            ->whereDate('date', $targetDate->toDateString())
            ->where('source_warehouse_id', $prdWarehouse->id)
            ->where('destination_warehouse_id', $rtsWarehouse->id)
            ->with(['lines'])
            ->orderByDesc('id')
            ->first();

        $prefillDate = $targetDate->toDateString();
        $prefillLines = null;

        if ($prefillRequest) {
            // Pakai tanggal dokumen (kalau beda)
            $prefillDate = $prefillRequest->date?->toDateString() ?? $prefillDate;

            // Prefill baris: item + qty_request (+ notes kalau nanti mau dipakai)
            $prefillLines = $prefillRequest->lines->map(function ($line) {
                return [
                    'item_id' => $line->item_id,
                    'qty_request' => $line->qty_request,
                    // 'notes'    => $line->notes, // kalau mau tambahin kolom catatan di form
                ];
            })->toArray();
        }

        // Fallback item list dari WH-PRD (tetap sama)
        $finishedGoodsItems = Item::whereHas('inventoryStocks', function ($q) use ($prdWarehouse) {
            $q->where('warehouse_id', $prdWarehouse->id);
        })
            ->orderBy('name')
            ->get();

        return view('inventory.rts_stock_requests.create', [
            'prdWarehouse' => $prdWarehouse,
            'rtsWarehouse' => $rtsWarehouse,
            'finishedGoodsItems' => $finishedGoodsItems,
            // data prefill
            'prefillDate' => $prefillDate,
            'prefillLines' => $prefillLines,
            'prefillRequest' => $prefillRequest,
        ]);
    }

    /**
     * Simpan / UPDATE Stock Request dari RTS untuk TANGGAL yang sama.
     *
     * ✅ RTS boleh request melebihi stok PRD (backend TIDAK memblokir),
     *    sistem hanya menyimpan snapshot stok untuk referensi.
     * ✅ Jika sudah ada dokumen RTS hari itu (kombinasi WH-PRD → WH-RTS, status submitted/partial),
     *    MAKA dokumen tersebut DIOVERWRITE isinya dengan data baru dari form.
     *    (code tidak berubah, hanya lines & notes yang di-update).
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
        ], [
            'lines.required' => 'Minimal harus ada 1 baris item.',
            'lines.*.item_id.required' => 'Pilih item untuk setiap baris.',
            'lines.*.qty_request.required' => 'Isi qty untuk setiap baris.',
        ]);

        $date = Carbon::parse($validated['date'])->startOfDay();
        $sourceWarehouseId = (int) $validated['source_warehouse_id'];
        $destinationWarehouseId = (int) $validated['destination_warehouse_id'];

        $stockRequest = null;
        $wasUpdated = false;

        DB::transaction(function () use (
            &$stockRequest,
            &$wasUpdated,
            $validated,
            $date,
            $sourceWarehouseId,
            $destinationWarehouseId
        ) {
            // 🔍 Cek apakah sudah ada dokumen RTS di tanggal yang sama
            $existing = StockRequest::rtsReplenish()
                ->whereDate('date', $date)
                ->where('source_warehouse_id', $sourceWarehouseId)
                ->where('destination_warehouse_id', $destinationWarehouseId)
                ->whereIn('status', ['submitted', 'partial']) // yang masih jalan
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                // 📌 MODE UPDATE: pakai dokumen lama, ganti isinya.
                $wasUpdated = true;
                $stockRequest = $existing;

                // Update header seperlunya (date & notes)
                $stockRequest->date = $date;
                // kalau notes baru diisi, replace; kalau kosong, biarkan notes lama
                if (array_key_exists('notes', $validated)) {
                    $stockRequest->notes = $validated['notes'] ?? null;
                }
                $stockRequest->save();

                // Hapus semua lines lama → diganti full dari input baru
                $stockRequest->lines()->delete();
            } else {
                // 🆕 MODE CREATE BARU
                $wasUpdated = false;
                $code = $this->generateCodeForDate($date);

                $stockRequest = StockRequest::create([
                    'code' => $code,
                    'date' => $date,
                    'purpose' => 'rts_replenish',
                    'source_warehouse_id' => $sourceWarehouseId,
                    'destination_warehouse_id' => $destinationWarehouseId,
                    'status' => 'submitted', // langsung submit ke PRD
                    'requested_by_user_id' => Auth::id(),
                    'notes' => $validated['notes'] ?? null,
                ]);
            }

            // 🔁 Tulis ulang semua lines berdasarkan input terbaru
            foreach ($validated['lines'] as $i => $lineData) {
                $itemId = (int) $lineData['item_id'];
                $qtyRequest = (float) $lineData['qty_request'];

                // Snapshot stok saat request (bisa dipakai buat analisa over-request)
                $available = $this->inventory->getAvailableStock($sourceWarehouseId, $itemId);

                StockRequestLine::create([
                    'stock_request_id' => $stockRequest->id,
                    'line_no' => $i + 1,
                    'item_id' => $itemId,
                    'qty_request' => $qtyRequest,
                    'stock_snapshot_at_request' => $available,
                    'qty_issued' => null,
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }
        });

        // 🔁 Redirect ke SHOW dokumen yang sama (baik baru ataupun update)
        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with(
                'status',
                $wasUpdated
                ? 'Permintaan stok RTS untuk hari ini berhasil diperbarui.'
                : 'Stock Request berhasil dibuat dan dikirim ke Gudang Produksi.'
            );
    }

    /**
     * Tampilkan detail Stock Request.
     */
    public function show(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);

        $stockRequest->load([
            'lines.item',
            'sourceWarehouse',
            'destinationWarehouse',
            'requestedBy',
        ]);

        $lines = $stockRequest->lines;

        // Summary dasar
        $totalLines = $lines->count();
        $totalRequestedQty = $lines->sum(function ($line) {
            return (float) ($line->qty_request ?? 0);
        });
        $totalSnapshotQty = $lines->whereNotNull('stock_snapshot_at_request')->sum(function ($line) {
            return (float) ($line->stock_snapshot_at_request ?? 0);
        });
        $totalIssuedQty = $lines->whereNotNull('qty_issued')->sum(function ($line) {
            return (float) ($line->qty_issued ?? 0);
        });

        // Summary over-request
        $overLinesCount = 0;
        $overQtyTotal = 0.0;

        foreach ($lines as $line) {
            $qtyRequest = (float) ($line->qty_request ?? 0);
            $snapshot = $line->stock_snapshot_at_request !== null
            ? (float) $line->stock_snapshot_at_request
            : null;

            if ($snapshot !== null && $qtyRequest > $snapshot) {
                $overLinesCount++;
                $overQtyTotal += max($qtyRequest - $snapshot, 0);
            }
        }

        $summary = [
            'total_lines' => $totalLines,
            'total_requested_qty' => $totalRequestedQty,
            'total_snapshot_qty' => $totalSnapshotQty,
            'total_issued_qty' => $totalIssuedQty,
            'over_lines_count' => $overLinesCount,
            'over_qty_total' => $overQtyTotal,
            'has_over_request' => $overLinesCount > 0,
        ];

        return view('inventory.rts_stock_requests.show', [
            'stockRequest' => $stockRequest,
            'summary' => $summary,
        ]);
    }

    /**
     * QUICK TODAY:
     * - Kalau sudah ada RTS hari ini (status submitted/partial) → lompat ke show.
     * - Kalau belum ada → lompat ke create.
     */
    public function quickToday(): RedirectResponse
    {
        $today = Carbon::today()->toDateString();

        $prdWarehouse = Warehouse::where('code', 'WH-PRD')->first();
        $rtsWarehouse = Warehouse::where('code', 'WH-RTS')->first();

        $query = StockRequest::rtsReplenish()
            ->whereDate('date', $today);

        if ($prdWarehouse && $rtsWarehouse) {
            $query->where('source_warehouse_id', $prdWarehouse->id)
                ->where('destination_warehouse_id', $rtsWarehouse->id);
        }

        $todayRequest = $query
            ->whereIn('status', ['submitted', 'partial'])
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

        $nextNumber = 1;

        if ($last) {
            $lastNumber = (int) substr($last->code, -3);
            $nextNumber = $lastNumber + 1;
        }

        return sprintf('%s-%03d', $prefix, $nextNumber);
    }
}
