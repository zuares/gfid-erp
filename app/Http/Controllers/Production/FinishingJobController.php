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

        // Operator list (kalau form masih pakai dropdown)
        $operators = Employee::query()
            ->orderBy('name')
            ->get();

        // Bundles dengan saldo WIP-FIN
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        $bundlesQuery = CuttingJobBundle::query()
            ->with(['cuttingJob', 'lot.item', 'finishedItem'])
            ->when($wipFinWarehouseId, function ($q) use ($wipFinWarehouseId) {
                $q->where('wip_warehouse_id', $wipFinWarehouseId)
                    ->where('wip_qty', '>', 0.0001);
            })
            ->orderBy('cutting_job_id')
            ->orderBy('bundle_no');

        $bundles = $bundlesQuery->get();

        // bundle_ids[] dari bundles_ready
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
                    'qty_in' => $wipQty,
                    'qty_ok' => $wipQty,
                    'qty_reject' => 0,
                    'operator_id' => null,
                    'reject_reason' => null,
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

        $job->load(['lines', 'lines.bundle']);

        DB::transaction(function () use ($job, $wipFinWarehouseId, $fgWarehouseId, $rejectWarehouseId, $date) {
            foreach ($job->lines as $line) {
                if (
                    ($line->qty_in ?? 0) <= 0
                    && ($line->qty_ok ?? 0) <= 0
                    && ($line->qty_reject ?? 0) <= 0
                ) {
                    continue;
                }

                if ($line->qty_in > 0) {
                    $this->inventory->stockOut(
                        $wipFinWarehouseId,
                        $line->item_id,
                        $line->qty_in,
                        $date,
                        FinishingJob::class,
                        $job->id,
                        'Finishing ' . $job->code,
                        false,
                        null
                    );
                }

                if ($line->qty_ok > 0) {
                    $this->inventory->stockIn(
                        $fgWarehouseId,
                        $line->item_id,
                        $line->qty_ok,
                        $date,
                        FinishingJob::class,
                        $job->id,
                        'Finishing OK ' . $job->code,
                        false,
                        null
                    );
                }

                if ($line->qty_reject > 0) {
                    $this->inventory->stockIn(
                        $rejectWarehouseId,
                        $line->item_id,
                        $line->qty_reject,
                        $date,
                        FinishingJob::class,
                        $job->id,
                        'Finishing REJECT ' . $job->code,
                        false,
                        null
                    );
                }

                if ($line->qty_in > 0 && $line->bundle) {
                    $bundle = $line->bundle;
                    $current = (float) ($bundle->wip_qty ?? 0);
                    $used = (float) $line->qty_in;

                    $newWipQty = $current - $used;

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
                'updated_by' => Auth::id(),
            ]);
        });

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil diposting dan stok sudah diperbarui.');
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
            DB::transaction(function () use ($job, $inventory, $wipFinWarehouseId, $fgWarehouseId, $rejectWarehouseId, $date) {
                $job->load(['lines.bundle']);

                foreach ($job->lines as $line) {
                    if ($line->qty_ok > 0) {
                        $inventory->stockOut(
                            $fgWarehouseId,
                            $line->item_id,
                            $line->qty_ok,
                            $date,
                            FinishingJob::class,
                            $job->id,
                            'Unpost Finishing OK ' . $job->code
                        );
                    }

                    if ($line->qty_reject > 0) {
                        $inventory->stockOut(
                            $rejectWarehouseId,
                            $line->item_id,
                            $line->qty_reject,
                            $date,
                            FinishingJob::class,
                            $job->id,
                            'Unpost Finishing REJECT ' . $job->code
                        );
                    }

                    if ($line->qty_in > 0) {
                        $inventory->stockIn(
                            $wipFinWarehouseId,
                            $line->item_id,
                            $line->qty_in,
                            $date,
                            FinishingJob::class,
                            $job->id,
                            'Unpost Finishing ' . $job->code
                        );
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
            ->with('status', 'Finishing Job berhasil di-unpost dan stok sudah dikembalikan.');
    }

    public function reportPerItem(Request $request): View
    {
        // Filter
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');
        $itemId = $request->input('item_id');

        // List item (barang jadi) buat filter dropdown
        $items = Item::query()
            ->orderBy('code')
            ->get();

        // Query: ambil line finishing yang SUDAH POSTED
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

        // Hitung grand total
        $grandTotalIn = $rows->sum('total_in');
        $grandTotalOk = $rows->sum('total_ok');
        $grandTotalReject = $rows->sum('total_reject');

        return view('production.finishing_jobs.report_per_item', [
            'rows' => $rows,
            'items' => $items,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'itemId' => $itemId,
            'grandTotalIn' => $grandTotalIn,
            'grandTotalOk' => $grandTotalOk,
            'grandTotalReject' => $grandTotalReject,
        ]);
    }

    public function reportPerItemDetail(Request $request, Item $item): View
    {
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        // Ambil agregat per finishing_job_id untuk item ini
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

        // Hitung grand total
        $grandTotalIn = $rows->sum('total_in');
        $grandTotalOk = $rows->sum('total_ok');
        $grandTotalReject = $rows->sum('total_reject');

        return view('production.finishing_jobs.report_per_item_detail', [
            'item' => $item,
            'rows' => $rows,
            'jobs' => $jobs,
            'dateFrom' => $dateFrom,
            'dateTo' => $dateTo,
            'grandTotalIn' => $grandTotalIn,
            'grandTotalOk' => $grandTotalOk,
            'grandTotalReject' => $grandTotalReject,
        ]);
    }

}
