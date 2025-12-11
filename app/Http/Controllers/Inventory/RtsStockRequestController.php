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

// ⬅️ tambahkan di atas controller

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

        // Cari DOKUMEN RTS hari itu (WH-PRD → WH-RTS)
        // Boleh status apapun (submitted / partial / completed), hanya untuk PREFILL.
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
            // Pakai tanggal dokumen (kalau beda)
            $prefillDate = $prefillRequest->date?->toDateString() ?? $prefillDate;
            $prefillStatus = $prefillRequest->status;

            // Flag: kalau permintaan lama SUDAH SELESAI → prefill untuk permintaan baru
            $prefillFromCompleted = $prefillRequest->status === 'completed';

            // Prefill baris: item + qty_request (+ notes kalau nanti mau dipakai)
            $prefillLines = $prefillRequest->lines->map(function ($line) {
                return [
                    'item_id' => $line->item_id,
                    'qty_request' => $line->qty_request,
                    // 'notes'    => $line->notes,
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
            'prefillStatus' => $prefillStatus,
            'prefillFromCompleted' => $prefillFromCompleted, // <-- buat teks info di Blade
        ]);
    }
    /**
     * Simpan / UPDATE Stock Request dari RTS untuk TANGGAL yang sama.
     *
     * ✅ RTS boleh request melebihi stok PRD (backend TIDAK memblokir),
     *    sistem hanya menyimpan snapshot stok untuk referensi.
     * ✅ Jika sudah ada dokumen RTS hari itu (kombinasi WH-PRD → WH-RTS) dengan status "submitted",
     *    MAKA dokumen tersebut DIOVERWRITE isinya dengan data baru dari form
     *    (code tidak berubah, hanya lines & notes yang di-update).
     * ✅ Jika dokumen hari itu statusnya sudah "partial" atau "completed",
     *    sistem TIDAK mengubah dokumen lama, tapi membuat dokumen BARU dengan kode baru.
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
            // 🔍 HANYA cari existing "submitted" untuk di-OVERWRITE
            $existing = StockRequest::rtsReplenish()
                ->whereDate('date', $date)
                ->where('source_warehouse_id', $sourceWarehouseId)
                ->where('destination_warehouse_id', $destinationWarehouseId)
                ->where('status', 'submitted')
                ->lockForUpdate()
                ->latest('id')
                ->first();

            if ($existing) {
                // 📌 MODE UPDATE: pakai dokumen lama, ganti isinya.
                $wasUpdated = true;
                $stockRequest = $existing;

                // Update header seperlunya (date & notes)
                $stockRequest->date = $date;
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
                    'qty_issued' => null, // final akan diisi saat RTS finalize
                    'notes' => $lineData['notes'] ?? null,
                ]);
            }
        });

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with(
                'status',
                $wasUpdated
                ? 'Permintaan stok RTS untuk hari ini berhasil diperbarui (dokumen masih dalam status submitted).'
                : 'Stock Request baru berhasil dibuat dan dikirim ke Gudang Produksi.'
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
     * FORM KONFIRMASI FISIK DI RTS
     * - RTS melihat: qty diminta, rencana kirim PRD (qty_issued dari PRD),
     *   dan mengisi qty fisik yang benar-benar diterima.
     */
    public function confirmReceive(StockRequest $stockRequest): View
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if(!in_array($stockRequest->status, ['submitted', 'partial']), 404);

        $stockRequest->load(['lines.item', 'sourceWarehouse', 'destinationWarehouse']);

        $sourceWarehouseId = $stockRequest->source_warehouse_id;

        // Stok live PRD per item (info saja)
        $liveStocks = [];
        foreach ($stockRequest->lines as $line) {
            $liveStocks[$line->id] = $this->inventory->getAvailableStock(
                $sourceWarehouseId,
                $line->item_id
            );
        }

        // Summary
        $totalRequested = (float) $stockRequest->lines->sum('qty_request');
        $totalPlanned = (float) $stockRequest->lines->sum(function ($line) {
            return (float) ($line->qty_issued ?? 0);
        });

        return view('inventory.rts_stock_requests.confirm', [
            'stockRequest' => $stockRequest,
            'liveStocks' => $liveStocks,
            'totalRequested' => $totalRequested,
            'totalPlanned' => $totalPlanned,
        ]);
    }

    /**
     * FINALIZE: RTS konfirmasi fisik → baru mutasi stok PRD → RTS.
     *
     * - Boleh input qty_received berapapun (boleh > request, boleh > stok live),
     *   validasi stok minus (kalau ada) ditangani di InventoryService::move().
     * - Kita set qty_issued = qty_received (final).
     * - Status:
     *      - submitted : belum ada baris yang diterima
     *      - partial   : ada yang diterima, tapi masih ada outstanding
     *      - completed : semua permintaan terpenuhi (outstanding total = 0)
     */
    public function finalize(Request $request, StockRequest $stockRequest): RedirectResponse
    {
        abort_unless($stockRequest->purpose === 'rts_replenish', 404);
        abort_if(!in_array($stockRequest->status, ['submitted', 'partial']), 404);

        $validated = $request->validate([
            'lines' => ['required', 'array'],
            'lines.*.qty_received' => ['nullable', 'numeric', 'gte:0'],
        ]);

        $sourceWarehouseId = $stockRequest->source_warehouse_id;
        $destinationWarehouseId = $stockRequest->destination_warehouse_id;
        $linesInput = $validated['lines'] ?? [];

        $anyReceived = false;

        DB::transaction(function () use (
            &$anyReceived,
            $stockRequest,
            $sourceWarehouseId,
            $destinationWarehouseId,
            $linesInput
        ) {
            $stockRequest->load('lines');

            foreach ($stockRequest->lines as $line) {
                $lineId = $line->id;
                $input = $linesInput[$lineId] ?? null;

                $qtyReceived = 0.0;
                if ($input !== null && isset($input['qty_received'])) {
                    $qtyReceived = (float) $input['qty_received'];
                }

                // Kalau user tidak isi / isi 0 → lewati baris ini
                if ($qtyReceived <= 0) {
                    continue;
                }

                $anyReceived = true;

                try {
                    // 🧠 IZINKAN STOK MINUS DI WH-PRD, mutasi pakai tanggal dokumen RTS
                    $this->inventory->move(
                        $line->item_id,
                        $sourceWarehouseId,
                        $destinationWarehouseId,
                        $qtyReceived,
                        referenceType: 'stock_request',
                        referenceId: $stockRequest->id,
                        notes: 'RTS receive confirmation',
                        date: $stockRequest->date ?? now(),
                        allowNegative: true, // ⬅️ stok WH-PRD boleh minus
                    );
                } catch (\RuntimeException $e) {
                    // Error stok (kalau someday allowNegative = false atau case lain)
                    throw ValidationException::withMessages([
                        'stock' => $e->getMessage(),
                    ]);
                }

                // 🔁 Kunci angka kirim: qty_issued = qty_received (angka final)
                $line->qty_issued = $qtyReceived;
                $line->save();
            }

            if (!$anyReceived) {
                // Tidak ada baris yang diisi → jangan ubah status apa pun
                return;
            }

            // Hitung ulang status dokumen
            $stockRequest->load('lines');

            $totalIssued = 0.0;
            $anyOutstanding = false;

            foreach ($stockRequest->lines as $line) {
                $reqQty = (float) ($line->qty_request ?? 0);
                $issuedQty = (float) ($line->qty_issued ?? 0);

                $totalIssued += $issuedQty;

                $outstanding = max($reqQty - $issuedQty, 0);
                if ($outstanding > 0) {
                    $anyOutstanding = true;
                }
            }

            if ($totalIssued <= 0) {
                $stockRequest->status = 'submitted'; // secara logika harusnya jarang kejadian
            } elseif ($anyOutstanding) {
                $stockRequest->status = 'partial';
            } else {
                $stockRequest->status = 'completed';
            }

            $stockRequest->save();
        });

        if (!$anyReceived) {
            return back()
                ->with('warning', 'Tidak ada Qty fisik yang diisi. Isi minimal satu baris untuk memproses mutasi.');
        }

        return redirect()
            ->route('rts.stock-requests.show', $stockRequest)
            ->with(
                'status',
                'Konfirmasi fisik berhasil. Stok PRD → RTS sudah dimutasi sesuai qty fisik (stok PRD bisa minus jika kirim lebih besar).'
            );
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
