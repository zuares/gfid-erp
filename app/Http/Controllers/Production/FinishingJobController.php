<?php

namespace App\Http\Controllers\Production;

use App\Helpers\CodeGenerator;
use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\FinishingJob;
use App\Models\FinishingJobLine;
use App\Models\Item;
use App\Models\ItemCostSnapshot;
use App\Models\SewingReturnLine;
use App\Models\Warehouse;
use App\Services\Costing\HppService;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinishingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected HppService $hpp,
    ) {}

    /* ============================================================
     * INDEX
     * ============================================================
     */

    public function index(Request $request): View
    {
        $search = $request->query('search');
        $status = $request->query('status'); // draft / posted / ''
        $rejectFlag = $request->query('reject'); // yes / no / ''

        $query = FinishingJob::query()
            ->withCount('lines as bundle_count')
            ->withSum('lines as total_ok', 'qty_ok')
            ->withSum('lines as total_reject', 'qty_reject')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        if ($status === 'draft') {
            $query->whereNull('posted_at');
        } elseif ($status === 'posted') {
            $query->whereNotNull('posted_at');
        }

        // ✅ lebih aman daripada HAVING alias
        if ($rejectFlag === 'yes') {
            $query->whereHas('lines', fn($q) => $q->where('qty_reject', '>', 0));
        } elseif ($rejectFlag === 'no') {
            $query->whereDoesntHave('lines', fn($q) => $q->where('qty_reject', '>', 0));
        }

        $finishingJobs = $query->paginate(15)->withQueryString();

        return view('production.finishing_jobs.index', compact(
            'finishingJobs',
            'search',
            'status',
            'rejectFlag',
        ));
    }

    /* ============================================================
     * CREATE
     * ============================================================
     */

    public function create(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        $operators = Employee::query()
            ->where('role', 'sewing') // sementara
            ->orderBy('name')
            ->get();

        if (!$wipFinWarehouseId) {
            return view('production.finishing_jobs.create', [
                'dateDefault' => old('date', $today),
                'lines' => [],
                'operators' => $operators,
            ])->withErrors([
                'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi. Silakan set warehouse dengan kode "WIP-FIN" terlebih dahulu.',
            ]);
        }

        $bundleIds = (array) $request->input('bundle_ids', []);

        $bundlesQuery = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->when(!empty($bundleIds), fn($q) => $q->whereIn('id', $bundleIds));

        $itemsWip = $bundlesQuery
            ->selectRaw('finished_item_id as item_id, SUM(wip_qty) as total_wip')
            ->whereNotNull('finished_item_id')
            ->groupBy('finished_item_id')
            ->orderBy('finished_item_id')
            ->get();

        $itemIds = $itemsWip->pluck('item_id')->filter()->unique()->values()->all();

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'code', 'name', 'color'])
            ->keyBy('id');

        $lines = [];

        foreach ($itemsWip as $row) {
            $itemId = (int) $row->item_id;
            $totalWip = (int) round((float) $row->total_wip); // ✅ integer

            $item = $items[$itemId] ?? null;

            // label = kode saja
            $itemLabel = $item
            ? ($item->code ?? ('Item #' . $itemId))
            : 'Item #' . $itemId;

            $lines[] = [
                'item_id' => $itemId,
                'item_label' => $itemLabel,
                'total_wip' => $totalWip,
                'qty_in' => null,
                'qty_reject' => 0,
                'reject_reason' => null,
                'reject_notes' => null,
            ];
        }

        return view('production.finishing_jobs.create', [
            'dateDefault' => old('date', $today),
            'lines' => $lines,
            'operators' => $operators,
        ]);
    }

    /* ============================================================
     * STORE (INTEGER)
     * ============================================================
     */

    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            'operator_global_id' => ['nullable', 'integer', 'exists:employees,id'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],

            // ✅ integer qty
            'lines.*.qty_in' => ['nullable', 'integer', 'min:0'],
            'lines.*.qty_reject' => ['required', 'integer', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
        if (!$wipFinWarehouseId) {
            return back()->withInput()->withErrors([
                'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi. Silakan set warehouse dengan kode WIP-FIN terlebih dahulu.',
            ]);
        }

        $globalOperatorId = $this->resolveGlobalOperatorId($validated['operator_global_id'] ?? null);

        $perItemLines = [];

        foreach ($validated['lines'] as $index => $lineData) {
            $itemId = (int) $lineData['item_id'];
            $qtyInRequested = (int) ($lineData['qty_in'] ?? 0);
            $qtyRejectRequested = (int) ($lineData['qty_reject'] ?? 0);

            $qtyInRequested = max(0, $qtyInRequested);
            $qtyRejectRequested = max(0, $qtyRejectRequested);

            $rejectReason = $lineData['reject_reason'] ?? null;
            $rejectNotes = $lineData['reject_notes'] ?? null;

            $bundles = CuttingJobBundle::query()
                ->readyForFinishing($wipFinWarehouseId)
                ->where('finished_item_id', $itemId)
                ->orderBy('cutting_job_id')
                ->orderBy('bundle_no')
                ->orderBy('id')
                ->get();

            $totalWip = (int) round((float) $bundles->sum('wip_qty'));

            if ($totalWip <= 0) {
                return back()->withInput()->withErrors([
                    "lines.{$index}.item_id" => 'Tidak ada saldo WIP-FIN untuk item ini. Pastikan sudah ada Sewing Return ke WIP-FIN.',
                ]);
            }

            if ($qtyInRequested <= 0) {
                if ($qtyRejectRequested > 0) {
                    return back()->withInput()->withErrors([
                        "lines.{$index}.qty_in" => 'Qty Proses harus > 0 jika ada Qty Reject.',
                    ]);
                }
                continue;
            }

            $qtyIn = min($qtyInRequested, $totalWip);

            if ($qtyRejectRequested > $qtyIn) {
                return back()->withInput()->withErrors([
                    "lines.{$index}.qty_reject" => "Qty Reject melebihi Qty Proses untuk item ini ({$qtyIn}).",
                ]);
            }

            $qtyOk = $qtyIn - $qtyRejectRequested;

            $perItemLines[] = [
                'item_id' => $itemId,
                'qty_in' => $qtyIn,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyRejectRequested,
                'reject_reason' => $rejectReason,
                'reject_notes' => $rejectNotes,
                'bundles' => $bundles,
                'index' => $index,
            ];
        }

        if (empty($perItemLines)) {
            return back()->withInput()->withErrors([
                'lines' => 'Isi minimal satu Qty Proses > 0 untuk membuat Finishing Job.',
            ]);
        }

        $job = null;

        // ✅ cache nama operator sewing biar tidak N+1
        $empNameCache = [];

        DB::transaction(function () use ($request, $validated, $perItemLines, $globalOperatorId, &$job, &$empNameCache) {
            $code = CodeGenerator::generate('FIN');

            $jobLocal = FinishingJob::create([
                'code' => $code,
                'date' => $validated['date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($perItemLines as $line) {
                $itemId = (int) $line['item_id'];
                $qtyInTotal = (int) $line['qty_in'];
                $qtyRejectTotal = (int) $line['qty_reject'];

                $rejectReason = $line['reject_reason'];
                $rejectNotes = $line['reject_notes'];

                $bundles = $line['bundles'];

                $remainingIn = $qtyInTotal;
                $remainingReject = $qtyRejectTotal;

                foreach ($bundles as $bundle) {
                    if ($remainingIn <= 0) {
                        break;
                    }

                    $bundleWip = (int) round((float) ($bundle->wip_qty ?? 0));
                    if ($bundleWip <= 0) {
                        continue;
                    }

                    $takeIn = min($bundleWip, $remainingIn);

                    // ✅ reject integer: ambil dari sisa reject, tidak boleh > takeIn
                    $takeReject = 0;
                    if ($remainingReject > 0) {
                        $takeReject = min($remainingReject, $takeIn);
                    }

                    $qtyOkThisBundle = $takeIn - $takeReject;

                    // Ambil sewing attribution dari SewingReturnLine terbaru untuk bundle + item ini
                    $sewingReturnLine = SewingReturnLine::query()
                        ->whereHas('sewingPickupLine', function ($q) use ($bundle, $itemId) {
                            $q->where('cutting_job_bundle_id', $bundle->id)
                                ->where('finished_item_id', $itemId);
                        })
                        ->latest('id')
                        ->first();

                    $sewingOperatorId = $sewingReturnLine?->sewingPickupLine?->operator_id ?? null;

                    $sewingOperatorName = null;
                    if ($sewingOperatorId) {
                        $sewingOperatorName = $empNameCache[$sewingOperatorId] ??= Employee::whereKey($sewingOperatorId)->value('name');
                    }

                    FinishingJobLine::create([
                        'finishing_job_id' => $jobLocal->id,
                        'bundle_id' => $bundle->id,
                        'operator_id' => $globalOperatorId,
                        'sewing_operator_id' => $sewingOperatorId,
                        'sewing_operator_name' => $sewingOperatorName,
                        'item_id' => $itemId,
                        'qty_in' => $takeIn,
                        'qty_ok' => $qtyOkThisBundle,
                        'qty_reject' => $takeReject,
                        'reject_reason' => $rejectReason,
                        'reject_notes' => $rejectNotes,
                        'processed_at' => $validated['date'],
                    ]);

                    $remainingIn -= $takeIn;
                    $remainingReject -= $takeReject;
                }

                // kalau masih ada reject tersisa padahal qty_in sudah habis -> harusnya tidak terjadi
                if ($remainingReject > 0) {
                    throw ValidationException::withMessages([
                        'finishing' => "Reject tersisa {$remainingReject} padahal Qty In habis. Cek input item {$itemId}.",
                    ]);
                }
            }

            $job = $jobLocal;
        });

        $job->loadMissing('lines');

        $hasReject = $job->lines()
            ->where('qty_reject', '>', 0)
            ->exists();

        if (!$hasReject) {
            try {
                // auto-post jika 0 reject
                return $this->post($job);
            } catch (ValidationException $e) {
                return redirect()
                    ->route('production.finishing_jobs.show', $job->id)
                    ->withErrors($e->errors())
                    ->with('status', 'Finishing Job berhasil dibuat sebagai draft, namun auto-post gagal. Periksa detail di bawah.');
            }
        }

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil dibuat sebagai draft (ADA REJECT).');
    }

    /* ============================================================
     * SHOW
     * ============================================================
     */

    public function show(FinishingJob $finishing_job): View
    {
        $finishing_job->load([
            'lines.bundle.cuttingJob',
            'lines.bundle.lot.item',
            'lines.bundle.finishedItem',
            'lines.item',
            'lines.sewingOperator',
            'lines.operator',
            'createdBy',
        ]);

        $rmSnapshots = ItemCostSnapshot::query()
            ->with('item')
            ->where('reference_type', 'auto_hpp_rm_only_finishing')
            ->where('reference_id', $finishing_job->id)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->get();

        $hasReject = $finishing_job->lines
            ->contains(fn(FinishingJobLine $line) => ((int) $line->qty_reject) > 0);

        $isAutoPost = $finishing_job->status === 'posted' && !$hasReject;

        return view('production.finishing_jobs.show', [
            'job' => $finishing_job,
            'rmSnapshots' => $rmSnapshots,
            'hasReject' => $hasReject,
            'isAutoPost' => $isAutoPost,
        ]);
    }

    /* ============================================================
     * EDIT / UPDATE
     * ============================================================
     */

    public function edit(FinishingJob $finishingJob): RedirectResponse | View
    {
        if ($finishingJob->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $finishingJob->id)
                ->with('error', 'Finishing yang sudah POSTED tidak bisa diubah.');
        }

        $lines = $finishingJob->lines()->with(['bundle.item', 'sewingOperator'])->get();

        return view('production.finishing_jobs.edit', [
            'job' => $finishingJob,
            'dateDefault' => old('date', $finishingJob->date?->toDateString() ?? now()->toDateString()),
            'lines' => $lines,
        ]);
    }

    public function update(Request $req, FinishingJob $finishingJob): RedirectResponse
    {
        if ($finishingJob->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $finishingJob->id)
                ->with('error', 'Finishing yang sudah POSTED tidak bisa diubah.');
        }

        $req->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            // ✅ integer qty
            'lines.*.qty_in' => ['required', 'integer', 'min:0'],
            'lines.*.qty_ok' => ['required', 'integer', 'min:0'],
            'lines.*.qty_reject' => ['required', 'integer', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:255'],
        ]);

        $empNameCache = [];

        DB::beginTransaction();
        try {
            $finishingJob->update([
                'date' => $req->date,
                'notes' => $req->notes,
                'updated_by' => Auth::id(),
            ]);

            $finishingJob->lines()->delete();

            $hasReject = false;

            foreach ($req->input('lines', []) as $idx => $row) {
                $bundle = CuttingJobBundle::find($row['bundle_id']);
                if (!$bundle) {
                    continue;
                }

                $qtyIn = (int) ($row['qty_in'] ?? 0);
                $qtyOk = (int) ($row['qty_ok'] ?? 0);
                $qtyReject = (int) ($row['qty_reject'] ?? 0);

                if (($qtyOk + $qtyReject) > $qtyIn) {
                    DB::rollBack();
                    return back()->withInput()->withErrors([
                        "lines.{$idx}.qty_ok" => 'Qty OK + Qty Reject melebihi Qty IN pada baris ini.',
                    ]);
                }

                $sewingReturnLine = SewingReturnLine::query()
                    ->whereHas('sewingPickupLine', function ($q) use ($bundle, $row) {
                        $q->where('cutting_job_bundle_id', $bundle->id)
                            ->where('finished_item_id', (int) $row['item_id']);
                    })
                    ->latest('id')
                    ->first();

                $sewingOperatorId = $sewingReturnLine?->sewingPickupLine?->operator_id ?? null;

                $sewingOperatorName = null;
                if ($sewingOperatorId) {
                    $sewingOperatorName = $empNameCache[$sewingOperatorId] ??= Employee::whereKey($sewingOperatorId)->value('name');
                }

                FinishingJobLine::create([
                    'finishing_job_id' => $finishingJob->id,
                    'bundle_id' => $bundle->id,
                    'item_id' => (int) $row['item_id'],
                    'qty_in' => $qtyIn,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'reject_reason' => $row['reject_reason'] ?? null,
                    'operator_id' => Auth::user()->employee_id ?? null,
                    'sewing_operator_id' => $sewingOperatorId,
                    'sewing_operator_name' => $sewingOperatorName,
                    'processed_at' => $req->date,
                ]);

                if ($qtyReject > 0) {
                    $hasReject = true;
                }

            }

            if (!$hasReject) {
                $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJECT'];
                $warehouses = $this->getRequiredWarehouses($requiredCodes);

                $missing = array_diff($requiredCodes, $warehouses->keys()->all());
                if (!empty($missing)) {
                    DB::rollBack();
                    return redirect()
                        ->route('production.finishing_jobs.show', $finishingJob->id)
                        ->withErrors([
                            'warehouse' => 'Warehouse berikut belum dikonfigurasi: ' . implode(', ', $missing),
                        ]);
                }

                $movementDate = $this->resolveMovementDate($finishingJob);

                $this->applyPostingMovements(
                    job: $finishingJob,
                    wipFinWarehouseId: $warehouses['WIP-FIN']->id,
                    prodWarehouseId: $warehouses['WH-PRD']->id,
                    rejectWarehouseId: $warehouses['REJECT']->id,
                    movementDate: $movementDate,
                    notesPrefix: 'Finishing ' . $finishingJob->code,
                );

                $finishingJob->update([
                    'status' => 'posted',
                    'posted_at' => now(),
                    'updated_by' => Auth::id(),
                ]);
            }

            DB::commit();

            return redirect()
                ->route('production.finishing_jobs.show', $finishingJob->id)
                ->with('success', $hasReject
                    ? 'Finishing diperbarui (ADA REJECT)'
                    : 'Finishing diperbarui & AUTO POSTED (0 reject)'
                );
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('FinishingJobController@update error: ' . $th->getMessage(), ['exception' => $th]);
            throw $th;
        }
    }

    /* ============================================================
     * AJAX BUNDLE ROW
     * ============================================================
     */

    public function bundle_row(Request $req): string
    {
        $index = (int) $req->query('index', 0);
        $bundleId = $req->query('bundle_id');
        $bundle = $bundleId ? CuttingJobBundle::with('item')->find($bundleId) : null;

        return view('production.finishing_jobs._bundle_row', [
            'index' => $index,
            'bundle' => $bundle,
        ])->render();
    }

    /* ============================================================
     * POST
     * ============================================================
     */

    public function post(FinishingJob $finishing_job): RedirectResponse
    {
        $job = $finishing_job->loadMissing(['lines']);

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

        $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJECT'];
        $warehouses = $this->getRequiredWarehouses($requiredCodes);

        $missing = array_diff($requiredCodes, $warehouses->keys()->all());
        if (!empty($missing)) {
            return back()->withErrors([
                'warehouse' => 'Warehouse berikut belum dikonfigurasi: ' . implode(', ', $missing) . '. Silakan setting dulu di Master Gudang.',
            ]);
        }

        $movementDate = $this->resolveMovementDate($job);

        // ✅ VALIDASI: saldo Sewing Return cukup utk qty_ok
        $this->assertSewingReturnBalanceForFinishingJob($job);

        DB::transaction(function () use ($job, $warehouses, $movementDate) {
            $this->applyPostingMovements(
                job: $job,
                wipFinWarehouseId: $warehouses['WIP-FIN']->id,
                prodWarehouseId: $warehouses['WH-PRD']->id,
                rejectWarehouseId: $warehouses['REJECT']->id,
                movementDate: $movementDate,
                notesPrefix: 'Finishing ' . $job->code,
            );

            $job->update([
                'status' => 'posted',
                'posted_at' => now(),
                'updated_by' => auth()->id(),
            ]);
        });

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil diposting (INTEGER), stok & HPP RM-only dipindahkan dari WIP-FIN ke WH-PRD/REJECT.');
    }

    public function forcePost(FinishingJob $finishingJob): RedirectResponse
    {
        $job = $finishingJob;

        if ($job->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('info', 'Finishing ini sudah POSTED.');
        }

        $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJECT'];
        $warehouses = $this->getRequiredWarehouses($requiredCodes);

        $missing = array_diff($requiredCodes, $warehouses->keys()->all());
        if (!empty($missing)) {
            return redirect()->back()->withErrors([
                'warehouse' => 'Warehouse WIP-FIN, WH-PRD, atau REJECT belum dikonfigurasi.',
            ]);
        }

        $movementDate = $this->resolveMovementDate($job);

        DB::beginTransaction();
        try {
            $this->applyPostingMovements(
                job: $job,
                wipFinWarehouseId: $warehouses['WIP-FIN']->id,
                prodWarehouseId: $warehouses['WH-PRD']->id,
                rejectWarehouseId: $warehouses['REJECT']->id,
                movementDate: $movementDate,
                notesPrefix: 'ForcePost Finishing ' . $job->code,
            );

            $job->update([
                'status' => 'posted',
                'posted_at' => now(),
                'updated_by' => Auth::id(),
            ]);

            DB::commit();

            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('success', 'Finishing berhasil diposting (manual override)');
        } catch (\Throwable $th) {
            DB::rollBack();
            Log::error('FinishingJobController@forcePost error: ' . $th->getMessage(), ['exception' => $th]);
            throw $th;
        }
    }

    /* ============================================================
     * HELPERS
     * ============================================================
     */

    protected function getRequiredWarehouses(array $codes)
    {
        return Warehouse::query()
            ->whereIn('code', $codes)
            ->get()
            ->keyBy('code');
    }

    protected function resolveGlobalOperatorId(?int $headerOperatorId): ?int
    {
        return $headerOperatorId ?? (Auth::user()->employee_id ?? null);
    }

    protected function resolveMovementDate(FinishingJob $job): \DateTimeInterface
    {
        $date = $job->date;
        if ($date instanceof \DateTimeInterface) {
            return $date;
        }
        return Carbon::parse($date ?? now());
    }

    /**
     * Apply mutasi stok + snapshot HPP RM-only + update finished_qty sewing_return_lines.
     * Asumsi dipanggil DI DALAM TRANSACTION.
     *
     * INTEGER MODE:
     * - qty_ok, qty_reject, qty_in, finished_qty, wip_qty = integer
     */
    protected function applyPostingMovements(
        FinishingJob $job,
        int $wipFinWarehouseId,
        int $prodWarehouseId,
        int $rejectWarehouseId,
        \DateTimeInterface $movementDate,
        string $notesPrefix = 'Finishing'
    ): void {
        $job->loadMissing(['lines.bundle', 'lines.item']);

        foreach ($job->lines as $line) {
            $qtyOk = (int) ($line->qty_ok ?? 0);
            $qtyReject = (int) ($line->qty_reject ?? 0);
            $qtyInField = (int) ($line->qty_in ?? 0);

            $qtyUsed = $qtyOk + $qtyReject;

            // fallback untuk data lama
            if ($qtyUsed <= 0 && $qtyInField > 0) {
                $qtyUsed = $qtyInField;
            }

            if ($qtyUsed <= 0 && $qtyOk <= 0 && $qtyReject <= 0) {
                continue;
            }

            $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                warehouseId: $wipFinWarehouseId,
                itemId: (int) $line->item_id,
            );
            $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;

            // 1) OUT WIP-FIN
            if ($qtyUsed > 0) {
                $this->inventory->stockOut(
                    warehouseId: $wipFinWarehouseId,
                    itemId: (int) $line->item_id,
                    qty: $qtyUsed,
                    date: $movementDate,
                    sourceType: FinishingJob::class,
                    sourceId: $job->id,
                    notes: $notesPrefix,
                    allowNegative: false,
                    lotId: null,
                    unitCostOverride: $movementUnitCost,
                    affectLotCost: false,
                );
            }

            // 2) IN WH-PRD (OK)
            if ($qtyOk > 0) {
                $this->inventory->stockIn(
                    warehouseId: $prodWarehouseId,
                    itemId: (int) $line->item_id,
                    qty: $qtyOk,
                    date: $movementDate,
                    sourceType: FinishingJob::class,
                    sourceId: $job->id,
                    notes: $notesPrefix . ' OK',
                    lotId: null,
                    unitCost: $movementUnitCost,
                    affectLotCost: false,
                );

                if ($movementUnitCost !== null && $movementUnitCost > 0) {
                    $this->hpp->createSnapshot(
                        itemId: (int) $line->item_id,
                        warehouseId: null,
                        snapshotDate: $movementDate->format('Y-m-d'),
                        referenceType: 'auto_hpp_rm_only_finishing',
                        referenceId: $job->id,
                        qtyBasis: $qtyOk,
                        rmUnitCost: $movementUnitCost,
                        cuttingUnitCost: 0,
                        sewingUnitCost: 0,
                        finishingUnitCost: 0,
                        packagingUnitCost: 0,
                        overheadUnitCost: 0,
                        notes: 'Auto HPP RM-only dari ' . $notesPrefix,
                        setActive: false,
                    );
                }

                if (!empty($line->bundle_id)) {
                    $this->allocateFinishedQtyFromSewingReturnByBundle(
                        bundleId: (int) $line->bundle_id,
                        itemId: (int) $line->item_id,
                        qtyOkNeed: $qtyOk,
                        jobCode: (string) $job->code,
                    );
                }
            }

            // 3) IN REJECT
            if ($qtyReject > 0) {
                $this->inventory->stockIn(
                    warehouseId: $rejectWarehouseId,
                    itemId: (int) $line->item_id,
                    qty: $qtyReject,
                    date: $movementDate,
                    sourceType: FinishingJob::class,
                    sourceId: $job->id,
                    notes: $notesPrefix . ' REJECT',
                    lotId: null,
                    unitCost: $movementUnitCost,
                    affectLotCost: false,
                );
            }

            // 4) Kurangi wip_qty bundle (OK + Reject)
            if ($line->bundle) {
                $bundle = $line->bundle;
                $current = (int) round((float) ($bundle->wip_qty ?? 0));
                $usedFromBundle = $qtyOk + $qtyReject;

                if ($usedFromBundle > 0) {
                    $bundle->wip_qty = max(0, $current - $usedFromBundle);
                    $bundle->save();
                }
            }
        }
    }

    /**
     * Allocate finished_qty (INTEGER) pada sewing_return_lines berbasis bundle + item.
     * FIFO by id, lock row untuk concurrency.
     */
    protected function allocateFinishedQtyFromSewingReturnByBundle(
        int $bundleId,
        int $itemId,
        int $qtyOkNeed,
        string $jobCode
    ): void {
        if ($qtyOkNeed <= 0) {
            return;
        }

        if (!method_exists(SewingReturnLine::class, 'sewingPickupLine')) {
            throw ValidationException::withMessages([
                'sewing_balance' => 'Model SewingReturnLine belum memiliki relasi sewingPickupLine(). Tambahkan belongsTo ke SewingPickupLine.',
            ]);
        }

        $lines = SewingReturnLine::query()
            ->whereHas('sewingPickupLine', function ($q) use ($bundleId, $itemId) {
                $q->where('cutting_job_bundle_id', $bundleId)
                    ->where('finished_item_id', $itemId);
            })
            ->whereRaw('qty_ok - finished_qty > 0')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $qtyOkNeed;

        foreach ($lines as $rl) {
            $avail = (int) $rl->qty_ok - (int) $rl->finished_qty;
            if ($avail <= 0) {
                continue;
            }

            $take = min($avail, $remaining); // ✅ integer
            if ($take <= 0) {
                continue;
            }

            $rl->finished_qty = (int) $rl->finished_qty + $take;
            $rl->save();

            $remaining -= $take;
            if ($remaining <= 0) {
                break;
            }

        }

        if ($remaining > 0) {
            throw ValidationException::withMessages([
                'finishing' => "Sewing Return (bundle {$bundleId}, item {$itemId}) tidak cukup untuk finishing OK {$qtyOkNeed}. Kurang {$remaining}. Job: {$jobCode}",
            ]);
        }
    }

    /**
     * Pastikan total Finishing OK di job ini tidak melebihi saldo Sewing Return per bundle+item,
     * dengan check fisik WIP bundle juga.
     */
    protected function assertSewingReturnBalanceForFinishingJob(FinishingJob $job): void
    {
        $job->loadMissing('lines');

        $lines = $job->lines()
            ->select('bundle_id', 'item_id', DB::raw('SUM(qty_ok) as qty_ok_total'))
            ->groupBy('bundle_id', 'item_id')
            ->get();

        foreach ($lines as $line) {
            $bundleId = (int) $line->bundle_id;
            $itemId = (int) $line->item_id;
            $qtyOkJob = (int) $line->qty_ok_total;

            if ($qtyOkJob <= 0) {
                continue;
            }

            $bundle = CuttingJobBundle::find($bundleId);
            $bundleWipQty = (int) round((float) ($bundle?->wip_qty ?? 0));

            $totalSewingOk = (int) DB::table('sewing_return_lines as srl')
                ->join('sewing_pickup_lines as spl', 'srl.sewing_pickup_line_id', '=', 'spl.id')
                ->where('spl.cutting_job_bundle_id', $bundleId)
                ->where('spl.finished_item_id', $itemId)
                ->sum('srl.qty_ok');

            $totalFinishingOkPosted = (int) DB::table('finishing_job_lines as fjl')
                ->join('finishing_jobs as fj', 'fjl.finishing_job_id', '=', 'fj.id')
                ->where('fjl.bundle_id', $bundleId)
                ->where('fjl.item_id', $itemId)
                ->where('fj.status', 'posted')
                ->where('fj.id', '<>', $job->id)
                ->sum('fjl.qty_ok');

            $availableFromSew = max(0, $totalSewingOk - $totalFinishingOkPosted);

            $sewingDiff = $qtyOkJob - $availableFromSew;
            $wipDiff = $qtyOkJob - $bundleWipQty;

            Log::info('DEBUG_FINISHING_GUARD_INT', [
                'job_id' => $job->id,
                'job_code' => $job->code,
                'bundle_id' => $bundleId,
                'item_id' => $itemId,
                'qtyOkJob' => $qtyOkJob,
                'totalSewingOk' => $totalSewingOk,
                'totalFinishingOkPosted' => $totalFinishingOkPosted,
                'availableFromSew' => $availableFromSew,
                'sewingDiff' => $sewingDiff,
                'bundle_wip_qty' => $bundleWipQty,
                'wipDiff' => $wipDiff,
            ]);

            // CASE 1: sewing cukup, tapi WIP fisik kurang => block
            if ($sewingDiff <= 0) {
                if ($wipDiff > 0) {
                    throw ValidationException::withMessages([
                        'finishing_job' =>
                        "WIP-FIN untuk bundle {$bundleId}, item {$itemId} tidak cukup untuk finishing OK {$qtyOkJob}. " .
                        "Kurang {$wipDiff}. Job: {$job->code}",
                    ]);
                }
                continue;
            }

            // CASE 2: sewing kurang, tapi fisik WIP masih cukup => toleransi + warning
            if ($bundleWipQty >= $qtyOkJob) {
                Log::warning('FINISHING_GUARD_INT_SEWING_MISMATCH_WIP_OK', [
                    'job_id' => $job->id,
                    'job_code' => $job->code,
                    'bundle_id' => $bundleId,
                    'item_id' => $itemId,
                    'qtyOkJob' => $qtyOkJob,
                    'availableFromSew' => $availableFromSew,
                    'bundle_wip_qty' => $bundleWipQty,
                    'note' => 'Sewing Return logis kurang, tapi stok fisik WIP-FIN cukup. Diizinkan dengan warning.',
                ]);
                continue;
            }

            // CASE 3: sewing kurang & fisik kurang => block
            $short = max($sewingDiff, $wipDiff);

            throw ValidationException::withMessages([
                'finishing_job' =>
                "Sewing Return / WIP-FIN untuk bundle {$bundleId}, item {$itemId} tidak cukup untuk finishing OK {$qtyOkJob}. " .
                "Kurang {$short}. Job: {$job->code}",
            ]);
        }
    }
}
