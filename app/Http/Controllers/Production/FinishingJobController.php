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

        if ($rejectFlag === 'yes') {
            $query->having('total_reject', '>', 0);
        } elseif ($rejectFlag === 'no') {
            $query->having('total_reject', '=', 0);
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
            ->where('role', 'sewing') // sementara pakai role sewing dulu
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
            ->when(!empty($bundleIds), function ($q) use ($bundleIds) {
                $q->whereIn('id', $bundleIds);
            });

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
            $totalWip = (float) $row->total_wip;

            $item = $items[$itemId] ?? null;

            // ⬇️ UBAH DI SINI: label = kode saja
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
            ];
        }

        return view('production.finishing_jobs.create', [
            'dateDefault' => old('date', $today),
            'lines' => $lines,
            'operators' => $operators,
        ]);
    }

    /* ============================================================
     * STORE
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

            'lines.*.qty_in' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        if (!$wipFinWarehouseId) {
            return back()
                ->withInput()
                ->withErrors([
                    'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi. Silakan set warehouse dengan kode WIP-FIN terlebih dahulu.',
                ]);
        }

        $globalOperatorId = $this->resolveGlobalOperatorId($validated['operator_global_id'] ?? null);

        $perItemLines = [];

        foreach ($validated['lines'] as $index => $lineData) {
            $itemId = (int) $lineData['item_id'];
            $qtyInRequested = (float) ($lineData['qty_in'] ?? 0);
            $qtyReject = (float) ($lineData['qty_reject'] ?? 0);

            $qtyInRequested = max(0, $qtyInRequested);
            $qtyReject = max(0, $qtyReject);

            $rejectReason = $lineData['reject_reason'] ?? null;
            $rejectNotes = $lineData['reject_notes'] ?? null;

            $bundles = CuttingJobBundle::query()
                ->readyForFinishing($wipFinWarehouseId)
                ->where('finished_item_id', $itemId)
                ->orderBy('cutting_job_id')
                ->orderBy('bundle_no')
                ->orderBy('id')
                ->get();

            $totalWip = (float) $bundles->sum('wip_qty');

            if ($totalWip <= 0.0001) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.item_id" =>
                        'Tidak ada saldo WIP-FIN untuk item ini. Pastikan sudah ada Sewing Return ke WIP-FIN.',
                    ]);
            }

            if ($qtyInRequested <= 0.0001) {
                if ($qtyReject > 0.0001) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            "lines.{$index}.qty_in" =>
                            'Qty Proses harus lebih besar dari 0 jika ada Qty Reject.',
                        ]);
                }
                continue;
            }

            $qtyIn = min($qtyInRequested, $totalWip);

            if ($qtyReject > $qtyIn + 0.0001) {
                return back()
                    ->withInput()
                    ->withErrors([
                        "lines.{$index}.qty_reject" =>
                        'Qty Reject melebihi Qty Proses untuk item ini (' . $qtyIn . ').',
                    ]);
            }

            $qtyOk = max(0, $qtyIn - $qtyReject);

            $perItemLines[] = [
                'item_id' => $itemId,
                'qty_in' => $qtyIn,
                'qty_ok' => $qtyOk,
                'qty_reject' => $qtyReject,
                'reject_reason' => $rejectReason,
                'reject_notes' => $rejectNotes,
                'bundles' => $bundles,
                'index' => $index,
            ];
        }

        if (empty($perItemLines)) {
            return back()
                ->withInput()
                ->withErrors([
                    'lines' => 'Isi minimal satu Qty Proses > 0 untuk membuat Finishing Job.',
                ]);
        }

        $job = null;

        DB::transaction(function () use ($request, $validated, $perItemLines, $globalOperatorId, &$job) {
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
                $qtyInTotal = (float) $line['qty_in'];
                $qtyRejectTotal = (float) $line['qty_reject'];

                $rejectReason = $line['reject_reason'];
                $rejectNotes = $line['reject_notes'];

                $bundles = $line['bundles'];

                $remainingIn = $qtyInTotal;
                $remainingReject = $qtyRejectTotal;

                foreach ($bundles as $bundle) {
                    if ($remainingIn <= 0) {
                        break;
                    }

                    $bundleWip = (float) ($bundle->wip_qty ?? 0);
                    if ($bundleWip <= 0) {
                        continue;
                    }

                    $takeIn = min($bundleWip, $remainingIn);

                    $rejectForThisBundle = 0.0;
                    if ($qtyRejectTotal > 0 && $qtyInTotal > 0) {
                        $ratio = $takeIn / $qtyInTotal;
                        $rejectForThisBundle = $qtyRejectTotal * $ratio;

                        if ($bundle->is($bundles->last())) {
                            $rejectForThisBundle = $remainingReject;
                        }
                    }

                    $qtyOkThisBundle = max(0, $takeIn - $rejectForThisBundle);

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
                        $emp = Employee::find($sewingOperatorId);
                        $sewingOperatorName = $emp?->name;
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
                        'qty_reject' => $rejectForThisBundle,
                        'reject_reason' => $rejectReason,
                        'reject_notes' => $rejectNotes,
                        'processed_at' => $validated['date'],
                    ]);

                    $remainingIn -= $takeIn;
                    $remainingReject -= $rejectForThisBundle;
                }
            }

            $job = $jobLocal;
        });

        $job->loadMissing('lines');

        $hasReject = $job->lines()
            ->where('qty_reject', '>', 0.0001)
            ->exists();

        if (!$hasReject) {
            try {
                // Coba auto-post (langsung WIP-FIN → WH-PRD)
                return $this->post($job);
            } catch (ValidationException $e) {
                // Kalau auto-post gagal (contoh: Sewing Return tidak cukup),
                // job tetap ada sebagai draft, user diarahkan ke halaman SHOW
                return redirect()
                    ->route('production.finishing_jobs.show', $job->id)
                    ->withErrors($e->errors())
                    ->with('status', 'Finishing Job berhasil dibuat sebagai draft, namun auto-post gagal. Periksa detail di bawah.');
            }
        }

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job (per item) berhasil dibuat sebagai draft (Qty proses bisa sebagian).');

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
            ->contains(fn(FinishingJobLine $line) => (float) $line->qty_reject > 0.0001);

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
            'lines.*.qty_in' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_ok' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:255'],
        ]);

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

                $qtyIn = (float) ($row['qty_in'] ?? 0);
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);

                if ($qtyOk + $qtyReject > $qtyIn + 0.0001) {
                    DB::rollBack();
                    return back()->withInput()
                        ->withErrors([
                            "lines.{$idx}.qty_ok" => 'Qty OK + Qty Reject melebihi Qty IN pada baris ini.',
                        ]);
                }

                $sewingReturnLine = SewingReturnLine::query()
                    ->whereHas('sewingPickupLine', function ($q) use ($bundle) {
                        $q->where('cutting_job_bundle_id', $bundle->id);
                    })
                    ->latest('id')
                    ->first();

                $sewingOperatorId = $sewingReturnLine?->sewingPickupLine?->operator_id ?? null;
                $sewingOperatorName = null;
                if ($sewingOperatorId) {
                    $emp = Employee::find($sewingOperatorId);
                    $sewingOperatorName = $emp?->name;
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

                if ($qtyReject > 0.0001) {
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

                $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
                $prodWarehouseId = $warehouses['WH-PRD']->id;
                $rejectWarehouseId = $warehouses['REJECT']->id;

                $movementDate = $this->resolveMovementDate($finishingJob);

                $this->applyPostingMovements(
                    job: $finishingJob,
                    wipFinWarehouseId: $wipFinWarehouseId,
                    prodWarehouseId: $prodWarehouseId,
                    rejectWarehouseId: $rejectWarehouseId,
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
     * POST (RESMI)
     * ============================================================
     */

    public function post(FinishingJob $finishing_job): RedirectResponse
    {
        // Pakai satu variabel $job, dan sekalian load lines + bundle untuk loop validasi
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

        // Pastikan warehouse wajib sudah ada
        $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJECT'];
        $warehouses = $this->getRequiredWarehouses($requiredCodes);

        $missing = array_diff($requiredCodes, $warehouses->keys()->all());
        if (!empty($missing)) {
            return back()->withErrors([
                'warehouse' => 'Warehouse berikut belum dikonfigurasi: '
                . implode(', ', $missing)
                . '. Silakan setting dulu di Master Gudang.',
            ]);
        }

        $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
        $prodWarehouseId = $warehouses['WH-PRD']->id;
        $rejectWarehouseId = $warehouses['REJECT']->id;

        $movementDate = $this->resolveMovementDate($job);

        // ✅ VALIDASI: saldo Sewing Return cukup untuk semua finishing OK di job ini
        $this->assertSewingReturnBalanceForFinishingJob($job);

        DB::transaction(function () use ($job, $wipFinWarehouseId, $prodWarehouseId, $rejectWarehouseId, $movementDate) {
            $this->applyPostingMovements(
                job: $job,
                wipFinWarehouseId: $wipFinWarehouseId,
                prodWarehouseId: $prodWarehouseId,
                rejectWarehouseId: $rejectWarehouseId,
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
            ->with('status', 'Finishing Job berhasil diposting, stok & HPP RM-only sudah dipindahkan dari WIP-FIN ke WH-PRD/REJECT.');
    }

    /* ============================================================
     * FORCE POST (MANUAL OVERRIDE)
     * ============================================================
     */

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

        $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
        $prodWarehouseId = $warehouses['WH-PRD']->id;
        $rejectWarehouseId = $warehouses['REJECT']->id;

        $movementDate = $this->resolveMovementDate($job);

        DB::beginTransaction();
        try {
            $this->applyPostingMovements(
                job: $job,
                wipFinWarehouseId: $wipFinWarehouseId,
                prodWarehouseId: $prodWarehouseId,
                rejectWarehouseId: $rejectWarehouseId,
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
     * HELPER INTERNAL
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
        if ($headerOperatorId) {
            return $headerOperatorId;
        }

        $user = Auth::user();
        if ($user && property_exists($user, 'employee_id')) {
            return $user->employee_id;
        }
        return null;
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
            $qtyIn = (float) ($line->qty_in ?? ((float) ($line->qty_ok ?? 0) + (float) ($line->qty_reject ?? 0)));
            $qtyOk = (float) ($line->qty_ok ?? 0);
            $qtyReject = (float) ($line->qty_reject ?? 0);

            if ($qtyIn <= 0.0000001 && $qtyOk <= 0.0000001 && $qtyReject <= 0.0000001) {
                continue;
            }

            $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                warehouseId: $wipFinWarehouseId,
                itemId: $line->item_id,
            );
            $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;

            // OUT dari WIP-FIN: qty_in (OK + reject)
            if ($qtyIn > 0.0000001) {
                $this->inventory->stockOut(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $line->item_id,
                    qty: $qtyIn,
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

            // IN ke WH-PRD: qty_ok
            if ($qtyOk > 0.0000001) {
                $this->inventory->stockIn(
                    warehouseId: $prodWarehouseId,
                    itemId: $line->item_id,
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
                        itemId: $line->item_id,
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

                // ✅ Update finished_qty pada SewingReturnLine (berbasis bundle + item)
                if (!empty($line->bundle_id)) {
                    $this->allocateFinishedQtyFromSewingReturnByBundle(
                        bundleId: (int) $line->bundle_id,
                        itemId: (int) $line->item_id,
                        qtyOkNeed: (float) $qtyOk,
                        jobCode: (string) $job->code,
                    );
                }
            }

            // IN ke REJECT: qty_reject
            if ($qtyReject > 0.0000001) {
                $this->inventory->stockIn(
                    warehouseId: $rejectWarehouseId,
                    itemId: $line->item_id,
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

            // Kurangi WIP qty di bundle
            if ($qtyIn > 0.0000001 && $line->bundle) {
                $bundle = $line->bundle;
                $current = (float) ($bundle->wip_qty ?? 0);
                $bundle->wip_qty = max(0, $current - $qtyIn);
                $bundle->save();
            }
        }
    }

    /**
     * Allocate finished_qty pada sewing_return_lines berbasis bundle + item.
     *
     * Sumber bundle ditarik dari sewing_pickup_lines.bundle_id (FK ke cutting_job_bundles.id).
     * FIFO by id. Lock row supaya aman di concurrency.
     */
    protected function allocateFinishedQtyFromSewingReturnByBundle(
        int $bundleId,
        int $itemId,
        float $qtyOkNeed,
        string $jobCode
    ): void {
        if ($qtyOkNeed <= 0.0000001) {
            return;
        }

        // Fail-fast jika relasi belum dibuat di model (biar jelas)
        if (!method_exists(SewingReturnLine::class, 'sewingPickupLine')) {
            throw ValidationException::withMessages([
                'sewing_balance' => 'Model SewingReturnLine belum memiliki relasi sewingPickupLine(). Tambahkan belongsTo ke SewingPickupLine.',
            ]);
        }

        $lines = SewingReturnLine::query()
            ->whereHas('sewingPickupLine', function ($q) use ($bundleId, $itemId) {
                // 🔁 SAMA persis dengan query Tinker yang tadi berhasil
                $q->where('cutting_job_bundle_id', $bundleId)
                    ->where('finished_item_id', $itemId);
            })
            ->whereRaw('qty_ok - finished_qty > 0')
            ->orderBy('id')
            ->lockForUpdate()
            ->get();

        $remaining = $qtyOkNeed;

        foreach ($lines as $rl) {
            $avail = (float) $rl->qty_ok - (float) $rl->finished_qty;
            if ($avail <= 0.0000001) {
                continue;
            }

            $take = min($avail, $remaining);

            // ⚠️ finished_qty kamu INTEGER. Kalau qty bisa desimal, sebaiknya ubah ke decimal nanti.
            $rl->finished_qty = (int) ((float) $rl->finished_qty + $take);
            $rl->save();

            $remaining -= $take;
            if ($remaining <= 0.0000001) {
                break;
            }
        }

        if ($remaining > 0.0000001) {
            throw ValidationException::withMessages([
                'finishing' => "Sewing Return (bundle {$bundleId}, item {$itemId}) tidak cukup untuk finishing OK {$qtyOkNeed}. Kurang {$remaining}. Job: {$jobCode}",
            ]);
        }
    }

    /**
     * Pastikan total Finishing OK di job ini tidak melebihi saldo Sewing Return per bundle.
     * Anchor ke bundle_id via relasi sewingPickupLine, bukan ke item_id langsung di sewing_return_lines.
     */
    protected function assertSewingReturnBalanceForFinishingJob(FinishingJob $job): void
    {
        // Pastikan lines sudah ke-load
        $job->loadMissing('lines');

        // Group per bundle + item, dan hitung total qty_ok di job ini
        $lines = $job->lines()
            ->select('bundle_id', 'item_id', DB::raw('SUM(qty_ok) as qty_ok_total'))
            ->groupBy('bundle_id', 'item_id')
            ->get();

        foreach ($lines as $line) {
            $bundleId = $line->bundle_id;
            $itemId = $line->item_id;
            $qtyOkJob = (float) $line->qty_ok_total;

            if ($qtyOkJob <= 0.0) {
                continue;
            }

            // 🔍 Ambil WIP-FIN real per bundle (fisik)
            $bundle = CuttingJobBundle::find($bundleId);
            $bundleWipQty = (float) ($bundle?->wip_qty ?? 0.0);

            // 🔢 Total OK dari Sewing Return utk bundle + item ini
            // → pakai query yang tadi kamu pakai di Tinker (terbukti dapet 18)
            $totalSewingOk = DB::table('sewing_return_lines as srl')
                ->join('sewing_pickup_lines as spl', 'srl.sewing_pickup_line_id', '=', 'spl.id')
                ->where('spl.cutting_job_bundle_id', $bundleId)
                ->where('spl.finished_item_id', $itemId)
                ->sum('srl.qty_ok');

            $totalSewingOk = (float) $totalSewingOk;

            // 🔢 Total Finishing OK yang SUDAH POSTED di job lain (bukan job ini)
            $totalFinishingOkPosted = DB::table('finishing_job_lines as fjl')
                ->join('finishing_jobs as fj', 'fjl.finishing_job_id', '=', 'fj.id')
                ->where('fjl.bundle_id', $bundleId)
                ->where('fjl.item_id', $itemId)
                ->where('fj.status', 'posted')
                ->where('fj.id', '<>', $job->id)
                ->sum('fjl.qty_ok');

            $totalFinishingOkPosted = (float) $totalFinishingOkPosted;

            // "Logis" menurut Sewing Return:
            $availableFromSew = max(0.0, $totalSewingOk - $totalFinishingOkPosted);
            $sewingDiff = $qtyOkJob - $availableFromSew;

            // "Fisik" menurut WIP-FIN bundle:
            $wipDiff = $qtyOkJob - $bundleWipQty;

            Log::info('DEBUG_FINISHING_GUARD', [
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

            // ✅ CASE 1: Secara Sewing Return sudah cukup
            if ($sewingDiff <= 0.0001) {
                // Tapi kalau WIP-FIN fisik malah kurang, itu indikasi stok minus → wajib blokir
                if ($wipDiff > 0.0001) {
                    $short = $wipDiff;

                    throw ValidationException::withMessages([
                        'finishing_job' =>
                        "WIP-FIN untuk bundle {$bundleId}, item {$itemId} tidak cukup untuk finishing OK {$qtyOkJob}. " .
                        "Kurang {$short}. Job: {$job->code}",
                    ]);
                }

                continue;
            }

            // ❗ Di titik ini: menurut Sewing Return, qty job ini melebihi saldo "logis".
            // → cek WIP-FIN fisik per bundle.

            // ✅ CASE 2: Sewing Return kurang, tapi WIP-FIN fisik masih cukup
            //    → TOLERANSI (mungkin data lama / adjustment), jangan blokir, cuma log warning.
            if ($bundleWipQty + 0.0001 >= $qtyOkJob) {
                Log::warning('FINISHING_GUARD_SEWING_MISMATCH_WIP_OK', [
                    'job_id' => $job->id,
                    'job_code' => $job->code,
                    'bundle_id' => $bundleId,
                    'item_id' => $itemId,
                    'qtyOkJob' => $qtyOkJob,
                    'totalSewingOk' => $totalSewingOk,
                    'availableFromSew' => $availableFromSew,
                    'bundle_wip_qty' => $bundleWipQty,
                    'note' => 'Sewing Return secara logis kurang, tapi stok WIP-FIN fisik masih cukup. Diizinkan dengan warning.',
                ]);

                continue;
            }

            // ❌ CASE 3: Sewing Return kurang DAN WIP-FIN fisik juga kurang
            // → ini benar-benar minus, wajib diblokir.
            $shortLogical = max(0.0, $sewingDiff);
            $shortPhysical = max(0.0, $wipDiff);
            $short = max($shortLogical, $shortPhysical);

            throw ValidationException::withMessages([
                'finishing_job' =>
                "Sewing Return / WIP-FIN untuk bundle {$bundleId}, item {$itemId} tidak cukup untuk finishing OK {$qtyOkJob}. " .
                "Kurang {$short}. Job: {$job->code}",
            ]);
        }
    }
}
