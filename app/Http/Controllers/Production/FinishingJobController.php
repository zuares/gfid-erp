<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\Item;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\View\View;

class FinishingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory
    ) {
    }

    /* ============================
     * INDEX
     * ============================ */

    public function index(Request $request): View
    {
        $status = $request->input('status'); // draft / posted
        $search = $request->input('search'); // code / notes
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        $query = FinishingJob::query()
            ->with(['createdBy'])
            ->withCount('lines');

        if ($status) {
            $query->where('status', $status);
        }

        if ($dateFrom) {
            $query->whereDate('date', '>=', $dateFrom);
        }

        if ($dateTo) {
            $query->whereDate('date', '<=', $dateTo);
        }

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        $jobs = $query
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.finishing_jobs.index', [
            'jobs' => $jobs,
            'status' => $status,
            'search' => $search,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
        ]);
    }

    /* ============================
     * READY BUNDLES (WIP-FIN)
     * ============================ */

    public function readyBundles(Request $request): View
    {
        $wipFinWarehouse = Warehouse::query()
            ->where('code', 'WIP-FIN')
            ->first();

        if (!$wipFinWarehouse) {
            $bundles = collect();
            $totalBundles = 0;
            $totalWipQty = 0;

            return view('production.finishing_jobs.bundles_ready', compact(
                'bundles',
                'totalBundles',
                'totalWipQty',
                'wipFinWarehouse'
            ));
        }

        $query = CuttingJobBundle::query()
            ->with([
                'cuttingJob',
                'lot.item',
                'finishedItem',
                'wipWarehouse',
            ])
            ->where('wip_warehouse_id', $wipFinWarehouse->id)
            ->where('wip_qty', '>', 0.0001);

        // Filter item_id via lot.item
        if ($itemId = $request->input('item_id')) {
            $query->whereHas('lot', function ($q) use ($itemId) {
                $q->where('item_id', $itemId);
            });
        }

        // Filter warna
        if ($color = $request->input('color')) {
            $query->whereHas('lot.item', function ($q) use ($color) {
                $q->where('color', 'like', '%' . $color . '%');
            });
        }

        // Filter kode bundle
        if ($bundleCode = $request->input('bundle_code')) {
            $query->where('bundle_code', 'like', '%' . $bundleCode . '%');
        }

        // Search umum
        if ($q = $request->input('q')) {
            $q = trim($q);
            $query->where(function ($sub) use ($q) {
                $sub->where('bundle_code', 'like', "%{$q}%")
                    ->orWhereHas('cuttingJob', function ($qq) use ($q) {
                        $qq->where('code', 'like', "%{$q}%");
                    })
                    ->orWhereHas('lot.item', function ($qqq) use ($q) {
                        $qqq->where('code', 'like', "%{$q}%")
                            ->orWhere('name', 'like', "%{$q}%");
                    });
            });
        }

        // Summary
        $summaryQuery = clone $query;
        $totalBundles = (clone $summaryQuery)->count();
        $totalWipQty = (clone $summaryQuery)->sum('wip_qty');

        // Data
        $bundles = $query
            ->orderBy('cutting_job_id')
            ->orderBy('bundle_no')
            ->orderByDesc('id')
            ->paginate(20)
            ->withQueryString();

        return view('production.finishing_jobs.bundles_ready', compact(
            'bundles',
            'totalBundles',
            'totalWipQty',
            'wipFinWarehouse'
        ));
    }

    /* ============================
     * CREATE
     * ============================ */

    public function create(Request $request): View
    {
        $date = Carbon::today()->toDateString();

        // Operator list (untuk dropdown di baris detail)
        $operators = Employee::query()
            ->orderBy('name')
            ->get();

        // Bundles dengan saldo WIP-FIN
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        $bundlesQuery = CuttingJobBundle::query()
            ->with(['cuttingJob', 'lot.item', 'finishedItem'])
            ->when($wipFinWarehouseId, function ($q) use ($wipFinWarehouseId) {
                $q->where('wip_warehouse_id', $wipFinWarehouseId);
            })
            ->where('wip_qty', '>', 0.0001)
            ->orderBy('cutting_job_id')
            ->orderBy('bundle_no');

        $bundles = $bundlesQuery->get();

        // bundle_ids[] dari halaman bundles_ready (kalau user pilih dari sana)
        $bundleIds = (array) $request->input('bundle_ids', []);

        $lines = [];

        if (!empty($bundleIds)) {
            $initialBundles = $bundles->whereIn('id', $bundleIds);

            foreach ($initialBundles as $bundle) {
                $itemModel = $bundle->finishedItem ?? $bundle->lot?->item;
                $itemId = $bundle->finished_item_id ?? $bundle->lot?->item_id ?? null;

                $itemLabel = $itemModel
                ? trim(
                    ($itemModel->code ?? '') . ' — ' .
                    ($itemModel->name ?? '') . ' ' .
                    ($itemModel->color ?? '')
                )
                : '';

                $wipQty = (float) ($bundle->wip_qty ?? 0);

                $lines[] = [
                    'bundle_id' => $bundle->id,
                    'item_id' => $itemId,
                    'item_label' => $itemLabel,
                    'wip_balance' => $wipQty,
                    // qty_in & qty_ok nanti dihitung di Blade/JS sebagai wip_balance & wip_balance - reject
                    'qty_reject' => 0,
                    'operator_id' => null,
                    'reject_reason' => null,
                    'reject_notes' => null,
                ];
            }
        }

        return view('production.finishing_jobs.create', compact(
            'date',
            'operators',
            'bundles',
            'lines',
        ));
    }

    /* ============================
     * STORE (DRAFT)
     * ============================ */

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],

            'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
            'lines.*.operator_id' => ['nullable', 'integer', 'exists:employees,id'],
            // qty_in & qty_ok diabaikan, dihitung ulang di server
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        $normalizedLines = [];

        foreach ($validated['lines'] as $index => $lineData) {
            /** @var CuttingJobBundle $bundle */
            $bundle = CuttingJobBundle::query()
                ->with(['finishedItem', 'lot.item'])
                ->findOrFail($lineData['bundle_id']);

            $wipBalance = (float) ($bundle->wip_qty ?? 0);

            // qty_reject dari user → dibersihkan
            $qtyReject = (float) ($lineData['qty_reject'] ?? 0);
            if ($qtyReject < 0) {
                $qtyReject = 0;
            }

            if ($qtyReject > $wipBalance + 0.0001) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_reject" =>
                        'Qty Reject untuk bundle ' . ($bundle->bundle_code ?? $bundle->id) .
                        ' melebihi saldo WIP-FIN (' . $wipBalance . ').',
                    ]);
            }

            $qtyIn = $wipBalance;
            $qtyOk = max(0, $qtyIn - $qtyReject);

            // Finishing WAJIB pakai finished_item_id
            $itemId = $bundle->finished_item_id;

            if (!$itemId) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.bundle_id" =>
                        'Item finishing untuk bundle ' . ($bundle->bundle_code ?? $bundle->id)
                        . ' belum di-set (finished_item_id kosong). Silakan perbaiki Cutting Job / Bundle.',
                    ]);
            }

            // Operator: kalau kosong, coba fallback ke employee_id user login (kalau ada)
            $operatorId = $lineData['operator_id'] ?? null;
            if (!$operatorId && Auth::user() && property_exists(Auth::user(), 'employee_id')) {
                $operatorId = Auth::user()->employee_id;
            }

            $normalizedLines[] = [
                'bundle' => $bundle,
                'data' => [
                    'bundle_id' => $bundle->id,
                    'item_id' => $itemId,
                    'operator_id' => $operatorId,
                    'qty_in' => $qtyIn,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'reject_reason' => $lineData['reject_reason'] ?? null,
                    'reject_notes' => $lineData['reject_notes'] ?? null,
                ],
            ];
        }

        $job = null;

        DB::transaction(function () use ($request, $validated, $normalizedLines, &$job) {
            $code = CodeGenerator::generate('FIN');

            /** @var FinishingJob $jobLocal */
            $jobLocal = FinishingJob::create([
                'code' => $code,
                'date' => $validated['date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($normalizedLines as $line) {
                FinishingJobLine::create([
                    'finishing_job_id' => $jobLocal->id,
                    'bundle_id' => $line['data']['bundle_id'],
                    'operator_id' => $line['data']['operator_id'],
                    'item_id' => $line['data']['item_id'],
                    'qty_in' => $line['data']['qty_in'],
                    'qty_ok' => $line['data']['qty_ok'],
                    'qty_reject' => $line['data']['qty_reject'],
                    'reject_reason' => $line['data']['reject_reason'],
                    'reject_notes' => $line['data']['reject_notes'],
                    'processed_at' => $validated['date'],
                ]);
            }

            $job = $jobLocal;
        });

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil dibuat sebagai draft.');
    }

    /* ============================
     * SHOW
     * ============================ */

    public function show(FinishingJob $finishing_job): View
    {
        $finishing_job->load([
            'lines.bundle.cuttingJob',
            'lines.bundle.lot.item',
            'lines.bundle.finishedItem',
            'lines.item',
            'lines.operator',
            'createdBy',
        ]);

        return view('production.finishing_jobs.show', [
            'job' => $finishing_job,
        ]);
    }

    /* ============================
     * EDIT (HEADER + DETAIL)
     * ============================ */

    public function edit(FinishingJob $finishing_job): View | RedirectResponse
    {
        $job = $finishing_job;

        if ($job->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('status', 'Finishing Job sudah diposting dan tidak bisa diedit.');
        }

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date->format('Y-m-d')
        : Carbon::parse($job->date)->format('Y-m-d');

        $bundles = CuttingJobBundle::query()
            ->with(['finishedItem', 'lot.item'])
            ->where('wip_qty', '>', 0)
            ->orderBy('bundle_code')
            ->get();

        $operators = Employee::query()
            ->orderBy('name')
            ->get();

        $lines = $job->lines->map(function (FinishingJobLine $line) {
            $bundle = $line->bundle;
            $bundleItem = $bundle?->finishedItem ?? $bundle?->lot?->item ?? $line->item;

            $itemLabel = $bundleItem
            ? trim(
                ($bundleItem->code ?? '') .
                ' — ' .
                ($bundleItem->name ?? '') .
                ' ' .
                ($bundleItem->color ?? '')
            )
            : '';

            $wipBalance = (float) ($bundle->wip_qty ?? $line->qty_in ?? 0);

            return [
                'bundle_id' => $bundle?->id,
                'item_id' => $line->item_id,
                'item_label' => $itemLabel,
                'wip_balance' => $wipBalance,
                'operator_id' => $line->operator_id,
                'qty_in' => $line->qty_in,
                'qty_ok' => $line->qty_ok,
                'qty_reject' => $line->qty_reject,
                'reject_reason' => $line->reject_reason,
                'reject_notes' => $line->reject_notes,
            ];
        })->values()->all();

        return view('production.finishing_jobs.edit', [
            'job' => $job,
            'date' => $date,
            'bundles' => $bundles,
            'operators' => $operators,
            'lines' => $lines,
        ]);
    }

    /* ============================
     * UPDATE (DRAFT)
     * ============================ */

    public function update(Request $request, FinishingJob $finishing_job): RedirectResponse
    {
        // Biar konsisten pakai $job
        $job = $finishing_job->fresh(); // ambil ulang dari DB, jaga-jaga status sudah berubah

        // Hanya boleh update kalau masih draft
        if (($job->status ?? 'draft') !== 'draft') {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('status', 'Finishing Job sudah diposting dan tidak bisa diedit.');
        }

        // 1. Validasi dasar
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'lines' => ['required', 'array', 'min:1'],

            'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
            'lines.*.operator_id' => ['nullable', 'integer', 'exists:employees,id'],
            // qty_in & qty_ok NGGAK kita percaya dari request → server yang hitung
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        $normalizedLines = [];

        // 2. Normalisasi & validasi per baris, hitung qty_in & qty_ok di server
        foreach ($validated['lines'] as $index => $lineData) {
            /** @var CuttingJobBundle $bundle */
            $bundle = CuttingJobBundle::query()
                ->with(['finishedItem', 'lot.item'])
                ->findOrFail($lineData['bundle_id']);

            // saldo WIP-FIN terkini
            $wipBalance = (float) ($bundle->wip_qty ?? 0);

            // Qty reject dari user
            $qtyReject = (float) ($lineData['qty_reject'] ?? 0);
            if ($qtyReject < 0) {
                $qtyReject = 0;
            }

            // Reject tidak boleh melebihi saldo WIP-FIN
            if ($qtyReject > $wipBalance + 0.0001) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_reject" =>
                        'Qty Reject untuk bundle ' . ($bundle->bundle_code ?? $bundle->id) .
                        ' melebihi saldo WIP-FIN (' . $wipBalance . ').',
                    ]);
            }

            // Server yang tentukan:
            $qtyIn = $wipBalance; // proses full saldo WIP-FIN
            $qtyOk = max(0, $qtyIn - $qtyReject); // sisanya OK

            // Finishing WAJIB pakai finished_item_id, fallback ke LOT cuma kalau benar-benar nggak ada
            $itemId = $bundle->finished_item_id ?: $bundle->lot?->item_id;
            if (!$itemId) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.bundle_id" =>
                        'Item finishing untuk bundle ' . ($bundle->bundle_code ?? $bundle->id) .
                        ' tidak ditemukan (finished_item_id & lot item kosong).',
                    ]);
            }

            $normalizedLines[] = [
                'bundle' => $bundle,
                'data' => [
                    'bundle_id' => $bundle->id,
                    'item_id' => $itemId,
                    'operator_id' => $lineData['operator_id'] ?? null,
                    'qty_in' => $qtyIn,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'reject_reason' => $lineData['reject_reason'] ?? null,
                    'reject_notes' => $lineData['reject_notes'] ?? null,
                ],
            ];
        }

        // 3. Simpan perubahan dalam transaksi
        DB::transaction(function () use ($job, $validated, $normalizedLines, $request) {
            // Update header
            $job->update([
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
                'updated_by' => $request->user()->id,
            ]);

            // Hapus semua line lama, insert ulang (karena masih draft aman)
            $job->lines()->delete();

            foreach ($normalizedLines as $line) {
                FinishingJobLine::create([
                    'finishing_job_id' => $job->id,
                    'bundle_id' => $line['data']['bundle_id'],
                    'operator_id' => $line['data']['operator_id'],
                    'item_id' => $line['data']['item_id'],
                    'qty_in' => $line['data']['qty_in'],
                    'qty_ok' => $line['data']['qty_ok'],
                    'qty_reject' => $line['data']['qty_reject'],
                    'reject_reason' => $line['data']['reject_reason'],
                    'reject_notes' => $line['data']['reject_notes'],
                    'processed_at' => $validated['date'],
                ]);
            }
        });

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil diperbarui.');
    }

    /* ============================
     * POST (WIP-FIN → FG + REJECT)
     * ============================ */

// pastikan use ini ada di atas controller

    public function post(FinishingJob $finishing_job): RedirectResponse
    {
        $job = $finishing_job;

        if (!$job || !$job->id) {
            return redirect()
                ->route('production.finishing_jobs.index')
                ->withErrors(['finishing_job' => 'Finishing Job tidak ditemukan.']);
        }

        if ($job->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('status', 'Finishing Job ini sudah diposting sebelumnya.');
        }

        $requiredCodes = ['WIP-FIN', 'FG', 'REJECT'];

        $warehouses = Warehouse::query()
            ->whereIn('code', $requiredCodes)
            ->get()
            ->keyBy('code');

        $missing = array_diff($requiredCodes, $warehouses->keys()->all());

        if (!empty($missing)) {
            return back()->withErrors([
                'warehouse' => 'Warehouse berikut belum dikonfigurasi: '
                . implode(', ', $missing)
                . '. Silakan setting dulu di Master Gudang.',
            ]);
        }

        $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
        $fgWarehouseId = $warehouses['FG']->id;
        $rejectWarehouseId = $warehouses['REJECT']->id;

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date
        : Carbon::parse($job->date);

        // load line + bundle + item
        $job->load(['lines', 'lines.bundle', 'lines.item']);

        DB::transaction(function () use ($job, $wipFinWarehouseId, $fgWarehouseId, $rejectWarehouseId, $date) {

            foreach ($job->lines as $line) {
                $qtyIn = (float) ($line->qty_in ?? 0);
                $qtyOk = (float) ($line->qty_ok ?? 0);
                $qtyReject = (float) ($line->qty_reject ?? 0);

                if ($qtyIn <= 0 && $qtyOk <= 0 && $qtyReject <= 0) {
                    continue;
                }

                // 🔎 Ambil unit_cost rata-rata item ini di WIP-FIN
                // Ambil unit cost dari WIP-FIN (hasil sewing sebelumnya)
                $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $line->item_id,
                );

                $movementDate = $date;
                $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;

// ==== 1) OUT dari WIP-FIN: qty_in (OK + reject) ====
                if ($qtyIn > 0) {
                    $this->inventory->stockOut(
                        warehouseId: $wipFinWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyIn,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'Finishing ' . $job->code,
                        allowNegative: false,
                        lotId: null, // barang jadi tanpa lot kain (kalau mau trace LOT, bisa diisi nanti)
                        unitCostOverride: $movementUnitCost, // 🔥 pakai cost WIP-FIN, bukan LotCost kain
                        affectLotCost: false, // 🔥 jangan sentuh LotCost (ini pure WIP → FG)
                    );
                }

// ==== 2) IN ke FG: qty_ok ====
                if ($qtyOk > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $fgWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyOk,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'Finishing OK ' . $job->code,
                        lotId: null, // kalau nanti kamu mau FG per LOT, bisa ganti pakai lot_id bundle
                        unitCost: $movementUnitCost,
                        affectLotCost: false, // tetap WIP/FG, jangan ngubah LotCost kain
                    );
                }

// ==== 3) IN ke REJECT: qty_reject ====
                if ($qtyReject > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $rejectWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyReject,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'Finishing REJECT ' . $job->code,
                        lotId: null,
                        unitCost: $movementUnitCost,
                        affectLotCost: false,
                    );
                }

                // ==== 4) Kurangi WIP qty di bundle ====
                if ($qtyIn > 0 && $line->bundle) {
                    $bundle = $line->bundle;
                    $current = (float) ($bundle->wip_qty ?? 0);
                    $used = $qtyIn;

                    $newWipQty = $current - $used;

                    // jaga-jaga floating error
                    if ($newWipQty < 0 && abs($newWipQty) > 0.0001) {
                        $newWipQty = 0;
                    }

                    if ($newWipQty < 0.0001) {
                        $newWipQty = 0;
                    }

                    $bundle->wip_qty = $newWipQty;
                    $bundle->save();
                }
            }

            $job->update([
                'status' => 'posted',
                'posted_at' => now(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil diposting, stok & costing sudah dipindahkan dari WIP-FIN ke FG/REJECT.');
    }

    /* ============================
     * UNPOST (FG + REJECT → WIP-FIN)
     * ============================ */
    public function unpost(FinishingJob $finishingJob): RedirectResponse
    {
        $job = $finishingJob;

        if ($job->status !== 'posted') {
            return back()->with('status', 'Finishing Job belum diposting, tidak bisa di-unpost.');
        }

        $warehouses = Warehouse::query()
            ->whereIn('code', ['WIP-FIN', 'FG', 'REJECT'])
            ->get()
            ->keyBy('code');

        if (!isset($warehouses['WIP-FIN'], $warehouses['FG'], $warehouses['REJECT'])) {
            return back()->with('status', 'Warehouse WIP-FIN, FG, dan REJECT belum dikonfigurasi lengkap.');
        }

        $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
        $fgWarehouseId = $warehouses['FG']->id;
        $rejectWarehouseId = $warehouses['REJECT']->id;

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date
        : Carbon::parse($job->date);

        $inventory = $this->inventory;

        try {
            DB::transaction(function () use (
                $job,
                $inventory,
                $wipFinWarehouseId,
                $fgWarehouseId,
                $rejectWarehouseId,
                $date
            ) {
                // perlu bundle juga supaya bisa balikin wip_qty
                $job->load(['lines.bundle']);

                foreach ($job->lines as $line) {
                    $qtyIn = (float) ($line->qty_in ?? 0);
                    $qtyOk = (float) ($line->qty_ok ?? 0);
                    $qtyReject = (float) ($line->qty_reject ?? 0);

                    if ($qtyIn <= 0 && $qtyOk <= 0 && $qtyReject <= 0) {
                        continue;
                    }

                    // 🔎 Ambil unit_cost dari FG sebagai estimasi cost yang akan balik ke WIP-FIN
                    $unitCostFromFg = $inventory->getItemIncomingUnitCost(
                        warehouseId: $fgWarehouseId,
                        itemId: $line->item_id,
                    );

                    // 1) OUT dari FG: qty_ok
                    if ($qtyOk > 0) {
                        $inventory->stockOut(
                            warehouseId: $fgWarehouseId,
                            itemId: $line->item_id,
                            qty: $qtyOk,
                            date: $date,
                            sourceType: FinishingJob::class,
                            sourceId: $job->id,
                            notes: 'Unpost Finishing OK ' . $job->code,
                            allowNegative: false,
                            lotId: null
                        );
                    }

                    // 2) OUT dari REJECT: qty_reject
                    if ($qtyReject > 0) {
                        $inventory->stockOut(
                            warehouseId: $rejectWarehouseId,
                            itemId: $line->item_id,
                            qty: $qtyReject,
                            date: $date,
                            sourceType: FinishingJob::class,
                            sourceId: $job->id,
                            notes: 'Unpost Finishing REJECT ' . $job->code,
                            allowNegative: false,
                            lotId: null
                        );
                    }

                    // 3) IN kembali ke WIP-FIN: qty_in
                    if ($qtyIn > 0) {
                        $inventory->stockIn(
                            warehouseId: $wipFinWarehouseId,
                            itemId: $line->item_id,
                            qty: $qtyIn,
                            date: $date,
                            sourceType: FinishingJob::class,
                            sourceId: $job->id,
                            notes: 'Unpost Finishing ' . $job->code,
                            lotId: null,
                            unitCost: $unitCostFromFg > 0 ? $unitCostFromFg : null,
                        );
                    }

                    // 4) Balikin wip_qty bundle di WIP-FIN
                    if ($qtyIn > 0 && $line->bundle) {
                        $bundle = $line->bundle;
                        $current = (float) ($bundle->wip_qty ?? 0);

                        // karena waktu POST kita kurangi wip_qty dengan qty_in,
                        // sekarang UNPOST kita tambahkan lagi qty_in
                        $bundle->wip_qty = $current + $qtyIn;
                        $bundle->wip_warehouse_id = $wipFinWarehouseId;
                        $bundle->save();
                    }
                }

                $job->update([
                    'status' => 'draft',
                    'posted_at' => null,
                    'unposted_at' => now(),
                    'updated_by' => auth()->id(),
                ]);
            });
        } catch (\Throwable $e) {
            report($e);

            return back()->with('status', 'Gagal unpost Finishing Job: ' . $e->getMessage());
        }

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil di-unpost: stok & wip bundle sudah dikembalikan ke WIP-FIN.');
    }

    public function reportPerItem(Request $request): View
    {
        // ===== FILTER =====
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $itemId = $request->input('item_id');

        // List item buat dropdown filter
        $items = Item::query()
            ->orderBy('code')
            ->get();

        // ===== AGREGAT QTY DARI FINISHING JOB LINES =====
        $linesQuery = FinishingJobLine::query()
            ->selectRaw('
            item_id,
            SUM(qty_in)     as total_in,
            SUM(qty_ok)     as total_ok,
            SUM(qty_reject) as total_reject
        ')
            ->with('item')
            ->whereHas('job', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted');

                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            });

        if ($itemId) {
            $linesQuery->where('item_id', $itemId);
        }

        $rows = $linesQuery
            ->groupBy('item_id')
            ->orderBy('item_id')
            ->get();

        // ===== COSTING: BACA DARI MUTASI MASUK FG (HPP) =====

        $fgWarehouseId = Warehouse::where('code', 'FG')->value('id');

        $costByItem = collect();

        if ($fgWarehouseId) {
            $costQuery = InventoryMutation::query()
                ->selectRaw('
                item_id,
                SUM(qty_change) as total_qty_in_fg,
                SUM(total_cost) as total_cost_fg
            ')
                ->where('warehouse_id', $fgWarehouseId)
                ->where('direction', 'in')
                ->where('source_type', FinishingJob::class);

            if ($dateFrom) {
                $costQuery->whereDate('date', '>=', $dateFrom);
            }

            if ($dateTo) {
                $costQuery->whereDate('date', '<=', $dateTo);
            }

            if ($itemId) {
                $costQuery->where('item_id', $itemId);
            }

            $mutations = $costQuery
                ->groupBy('item_id')
                ->get();

            $costByItem = $mutations->mapWithKeys(function ($m) {
                $qty = (float) ($m->total_qty_in_fg ?? 0);
                $cost = (float) ($m->total_cost_fg ?? 0);
                $unit = $qty > 0 ? $cost / $qty : 0;

                return [
                    (int) $m->item_id => [
                        'qty_in_fg' => $qty,
                        'total_cost_fg' => $cost,
                        'unit_cost_fg' => $unit,
                    ],
                ];
            });
        }

        // Merge qty + costing per item
        foreach ($rows as $row) {
            $id = (int) $row->item_id;

            $costInfo = $costByItem[$id] ?? [
                'qty_in_fg' => 0,
                'total_cost_fg' => 0,
                'unit_cost_fg' => 0,
            ];

            $row->qty_in_fg = $costInfo['qty_in_fg'];
            $row->total_cost_fg = $costInfo['total_cost_fg'];
            $row->unit_cost_fg = $costInfo['unit_cost_fg'];

            // HPP untuk qty OK (reject nggak ikut ke HPP FG)
            $row->total_hpp_ok = $row->total_ok * $row->unit_cost_fg;
        }

        // ===== GRAND TOTAL =====
        $grandTotalIn = $rows->sum('total_in');
        $grandTotalOk = $rows->sum('total_ok');
        $grandTotalReject = $rows->sum('total_reject');

        $grandQtyInFg = $rows->sum('qty_in_fg');
        $grandCostFg = $rows->sum('total_cost_fg');
        $grandHppOk = $rows->sum('total_hpp_ok');

        $avgUnitCostOk = $grandTotalOk > 0
        ? $grandHppOk / $grandTotalOk
        : 0;

        return view('production.finishing_jobs.report_per_item', [
            'rows' => $rows,
            'items' => $items,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'itemId' => $itemId,

            'grandTotalIn' => $grandTotalIn,
            'grandTotalOk' => $grandTotalOk,
            'grandTotalReject' => $grandTotalReject,

            'grandQtyInFg' => $grandQtyInFg,
            'grandCostFg' => $grandCostFg,
            'grandHppOk' => $grandHppOk,
            'avgUnitCostOk' => $avgUnitCostOk,
        ]);
    }

    public function reportPerItemDetail(Request $request, Item $item): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Ambil agregat per finishing_job_id untuk item ini (qty saja dulu)
        $linesQuery = FinishingJobLine::query()
            ->selectRaw('
            finishing_job_id,
            SUM(qty_in)     as total_in,
            SUM(qty_ok)     as total_ok,
            SUM(qty_reject) as total_reject
        ')
            ->where('item_id', $item->id)
            ->whereHas('job', function ($q) use ($dateFrom, $dateTo) {
                $q->where('status', 'posted');

                if ($dateFrom) {
                    $q->whereDate('date', '>=', $dateFrom);
                }

                if ($dateTo) {
                    $q->whereDate('date', '<=', $dateTo);
                }
            });

        $rows = $linesQuery
            ->groupBy('finishing_job_id')
            ->orderBy('finishing_job_id')
            ->get();

        // Load FinishingJob untuk setiap row
        $jobIds = $rows->pluck('finishing_job_id')->filter()->unique()->values()->all();

        $jobs = FinishingJob::query()
            ->with('createdBy')
            ->whereIn('id', $jobIds)
            ->get()
            ->keyBy('id');

        // ============================
        //  COSTING dari FG (HPP)
        // ============================

        // Cari id warehouse FG
        $fgWarehouseId = Warehouse::where('code', 'FG')->value('id');

        $costByJob = collect();

        if ($fgWarehouseId && !empty($jobIds)) {
            // Ambil mutasi "IN" ke gudang FG untuk item ini, bersumber dari FinishingJob
            $mutations = InventoryMutation::query()
                ->selectRaw('
                source_id,
                SUM(qty_change)  as total_qty_in_fg,
                SUM(total_cost)  as total_cost_fg
            ')
                ->where('warehouse_id', $fgWarehouseId)
                ->where('item_id', $item->id)
                ->where('direction', 'in')
                ->where('source_type', FinishingJob::class)
                ->whereIn('source_id', $jobIds)
                ->groupBy('source_id')
                ->get();

            // Map: finishing_job_id => info costing
            $costByJob = $mutations->mapWithKeys(function ($m) {
                $qty = (float) ($m->total_qty_in_fg ?? 0);
                $cost = (float) ($m->total_cost_fg ?? 0);
                $unit = $qty > 0 ? $cost / $qty : 0;

                return [
                    (int) $m->source_id => [
                        'qty_in_fg' => $qty, // qty yang benar-benar masuk FG dari job ini
                        'total_cost_fg' => $cost, // total rupiah yang dialihkan ke FG
                        'unit_cost_fg' => $unit, // HPP/pcs di FG untuk batch ini
                    ],
                ];
            });
        }

        // Tambahkan info costing ke setiap row
        foreach ($rows as $row) {
            $jobId = (int) $row->finishing_job_id;
            $costInfo = $costByJob[$jobId] ?? [
                'qty_in_fg' => 0,
                'total_cost_fg' => 0,
                'unit_cost_fg' => 0,
            ];

            $row->qty_in_fg = $costInfo['qty_in_fg'];
            $row->total_cost_fg = $costInfo['total_cost_fg'];
            $row->unit_cost_fg = $costInfo['unit_cost_fg'];

            // Total HPP hanya untuk qty OK (reject tidak ikut HPP FG)
            $row->total_hpp_ok = $row->total_ok * $row->unit_cost_fg;
        }

        // Hitung grand total (qty & cost)
        $grandTotalIn = $rows->sum('total_in');
        $grandTotalOk = $rows->sum('total_ok');
        $grandTotalReject = $rows->sum('total_reject');

        $grandTotalCostFg = $rows->sum('total_cost_fg'); // total rupiah yang masuk FG (semua job)
        $grandTotalHppOk = $rows->sum('total_hpp_ok'); // total HPP untuk qty OK saja

        // HPP rata-rata (weighted) untuk OK di periode ini
        $avgUnitCostOk = $grandTotalOk > 0
        ? $grandTotalHppOk / $grandTotalOk
        : 0;

        return view('production.finishing_jobs.report_per_item_detail', [
            'item' => $item,
            'rows' => $rows,
            'jobs' => $jobs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'grandTotalIn' => $grandTotalIn,
            'grandTotalOk' => $grandTotalOk,
            'grandTotalReject' => $grandTotalReject,

            // costing
            'grandTotalCostFg' => $grandTotalCostFg,
            'grandTotalHppOk' => $grandTotalHppOk,
            'avgUnitCostOk' => $avgUnitCostOk,
        ]);
    }

}
