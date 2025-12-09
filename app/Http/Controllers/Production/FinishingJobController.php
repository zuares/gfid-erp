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
use Illuminate\View\View;

class FinishingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected HppService $hpp,
    ) {}

    public function index(Request $request)
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

        // filter search
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', '%' . $search . '%')
                    ->orWhere('notes', 'like', '%' . $search . '%');
            });
        }

        // filter status draft / posted
        if ($status === 'draft') {
            $query->whereNull('posted_at');
        } elseif ($status === 'posted') {
            $query->whereNotNull('posted_at');
        }

        // filter reject
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

    /* ============================
     * CREATE
     * ============================ */

    public function create(Request $request): View
    {
        $date = Carbon::today()->toDateString();

        // Gudang WIP-FIN
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        // Siapkan daftar operator jahit (sementara semua employee, nanti bisa difilter)
        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('name')
            ->get();

        if (!$wipFinWarehouseId) {
            return view('production.finishing_jobs.create', [
                'date' => $date,
                'lines' => [],
                'operators' => $operators,
            ])->withErrors([
                'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi. Silakan set warehouse dengan kode WIP-FIN terlebih dahulu.',
            ]);
        }

        // OPTIONAL: subset bundle dari bundles_ready (?bundle_ids[]=...)
        $bundleIds = (array) $request->input('bundle_ids', []);

        // 1) Ambil WIP-FIN per bundle
        $bundlesQuery = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->when(!empty($bundleIds), function ($q) use ($bundleIds) {
                $q->whereIn('id', $bundleIds);
            });

        // 2) Group per item (finished_item_id)
        $itemsWip = $bundlesQuery
            ->selectRaw('finished_item_id as item_id, SUM(wip_qty) as total_wip')
            ->whereNotNull('finished_item_id')
            ->groupBy('finished_item_id')
            ->orderBy('finished_item_id')
            ->get();

        $itemIds = $itemsWip->pluck('item_id')->filter()->unique()->values()->all();

        // 3) Ambil master item untuk label
        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'code', 'name', 'color'])
            ->keyBy('id');

        // 4) Build lines untuk Blade
        $lines = [];

        foreach ($itemsWip as $row) {
            $itemId = (int) $row->item_id;
            $totalWip = (float) $row->total_wip;

            $item = $items[$itemId] ?? null;

            $itemLabel = $item
            ? trim(($item->code ?? '') . ' — ' . ($item->name ?? '') . ' ' . ($item->color ?? ''))
            : 'Item #' . $itemId;

            $lines[] = [
                'item_id' => $itemId,
                'item_label' => $itemLabel,
                'total_wip' => $totalWip,
                'qty_in' => null, // user isi sendiri
                'qty_reject' => 0,
                'reject_reason' => null,
            ];
        }

        return view('production.finishing_jobs.create', [
            'dateDefault' => old('date', $date),
            'lines' => $lines,
            'operators' => $operators,
        ]);
    }

    /* ============================
     * STORE
     * ============================ */

    public function store(Request $request): RedirectResponse
    {
        // 1) Validasi PER ITEM
        $validated = $request->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],

            // operator global (dropdown di header)
            'operator_global_id' => ['nullable', 'integer', 'exists:employees,id'],

            'lines' => ['required', 'array', 'min:1'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],

            // tidak ada operator per baris lagi
            'lines.*.qty_in' => ['nullable', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        // Pastikan gudang WIP-FIN ada
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        if (!$wipFinWarehouseId) {
            return back()
                ->withInput()
                ->withErrors([
                    'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi. Silakan set warehouse dengan kode WIP-FIN terlebih dahulu.',
                ]);
        }

        // Operator global dari header + fallback user login
        $globalOperatorId = $validated['operator_global_id'] ?? null;
        if (!$globalOperatorId && Auth::user() && property_exists(Auth::user(), 'employee_id')) {
            $globalOperatorId = Auth::user()->employee_id;
        }

        $perItemLines = [];

        foreach ($validated['lines'] as $index => $lineData) {
            $itemId = (int) $lineData['item_id'];
            $qtyInRequested = (float) ($lineData['qty_in'] ?? 0);
            $qtyReject = (float) ($lineData['qty_reject'] ?? 0);

            if ($qtyInRequested < 0) {
                $qtyInRequested = 0;
            }
            if ($qtyReject < 0) {
                $qtyReject = 0;
            }

            $rejectReason = $lineData['reject_reason'] ?? null;
            $rejectNotes = $lineData['reject_notes'] ?? null;

            // Ambil semua bundle WIP-FIN untuk item ini (FG)
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

            // Kalau user tidak isi qty_in → kalau ada reject, kita anggap error
            if ($qtyInRequested <= 0.0001) {
                if ($qtyReject > 0.0001) {
                    return back()
                        ->withInput()
                        ->withErrors([
                            "lines.{$index}.qty_in" =>
                            'Qty Proses harus lebih besar dari 0 jika ada Qty Reject.',
                        ]);
                }

                // Qty proses 0 dan reject 0 → line ini di-skip, tidak dibuat Finishing
                continue;
            }

            // Batas atas: tidak boleh lebih dari total WIP item ini
            $qtyIn = min($qtyInRequested, $totalWip);

            // Reject tidak boleh melebihi qty yang DIPROSES
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

        // Kalau semua line di-skip karena qty_in = 0
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

            /** @var FinishingJob $jobLocal */
            $jobLocal = FinishingJob::create([
                'code' => $code,
                'date' => $validated['date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($perItemLines as $line) {
                $itemId = $line['item_id'];
                $qtyIn = $line['qty_in'];
                $qtyOk = $line['qty_ok'];
                $qtyReject = $line['qty_reject'];
                $rejectReason = $line['reject_reason'];
                $rejectNotes = $line['reject_notes'];
                /** @var \Illuminate\Support\Collection $bundles */
                $bundles = $line['bundles'];

                if ($qtyIn <= 0) {
                    continue;
                }

                $remainingIn = $qtyIn;
                $remainingReject = $qtyReject;

                foreach ($bundles as $bundle) {
                    if ($remainingIn <= 0) {
                        break;
                    }

                    $bundleWip = (float) ($bundle->wip_qty ?? 0);
                    if ($bundleWip <= 0) {
                        continue;
                    }

                    // Ambil dari bundle ini max sampai sisa qtyIn
                    $takeIn = min($bundleWip, $remainingIn);

                    // Proporsi reject untuk bundle ini berdasarkan qty_in yang DIPROSES
                    $rejectForThisBundle = 0.0;
                    if ($qtyReject > 0 && $qtyIn > 0) {
                        $ratio = $takeIn / $qtyIn;
                        $rejectForThisBundle = $qtyReject * $ratio;

                        // Kalau ini bundle terakhir → serap semua sisa reject supaya pas
                        if ($bundle->is($bundles->last())) {
                            $rejectForThisBundle = $remainingReject;
                        }
                    }

                    $qtyOkThisBundle = max(0, $takeIn - $rejectForThisBundle);

                    // ambil sewing attribution untuk bundle (latest sewing return -> sewingPickupLine.operator_id)
                    $sewingReturnLine = SewingReturnLine::whereHas('sewingPickupLine', function ($q) use ($bundle) {
                        $q->where('cutting_job_bundle_id', $bundle->id);
                    })->latest('id')->first();

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

        // ============================
        // AUTO-POST jika TIDAK ada reject
        // ============================
        $job->loadMissing('lines');

        $hasReject = $job->lines()
            ->where('qty_reject', '>', 0.0001)
            ->exists();

        if (!$hasReject) {
            // Langsung POST: mutasi stok + snapshot HPP RM-only
            return $this->post($job);
        }

        // Kalau ada reject → tetap draft, kamu review dulu
        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job (per item) berhasil dibuat sebagai draft (Qty proses bisa sebagian).');
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
            'lines.sewingOperator',
            'lines.operator',
            'createdBy',
        ]);

        // 🔹 Snapshots RM-only yang terkait finishing job ini
        $rmSnapshots = ItemCostSnapshot::query()
            ->with('item')
            ->where('reference_type', 'auto_hpp_rm_only_finishing')
            ->where('reference_id', $finishing_job->id)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->get();

        // 🔹 Cek apakah ada reject di salah satu line
        $hasReject = $finishing_job->lines
            ->contains(function (FinishingJobLine $line) {
                return (float) $line->qty_reject > 0.0001;
            });

        // 🔹 Anggap "auto-post" kalau status posted & semua line tanpa reject
        $isAutoPost = $finishing_job->status === 'posted' && !$hasReject;

        return view('production.finishing_jobs.show', [
            'job' => $finishing_job,
            'rmSnapshots' => $rmSnapshots,
            'hasReject' => $hasReject,
            'isAutoPost' => $isAutoPost,
        ]);
    }

    /* ============================
     * EDIT / UPDATE
     * ============================ */

    public function edit(FinishingJob $finishingJob)
    {
        // tidak boleh edit yang sudah posted
        if ($finishingJob->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $finishingJob->id)
                ->with('error', 'Finishing yang sudah POSTED tidak bisa diubah.');
        }

        // siapkan data untuk blade
        $lines = $finishingJob->lines()->with(['bundle.item', 'sewingOperator'])->get();

        return view('production.finishing_jobs.edit', [
            'job' => $finishingJob,
            'dateDefault' => old('date', $finishingJob->date?->toDateString() ?? now()->toDateString()),
            'lines' => $lines,
        ]);
    }

    public function update(Request $req, FinishingJob $finishingJob)
    {

        // jangan izinkan jika sudah posted
        if ($finishingJob->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $finishingJob->id)
                ->with('error', 'Finishing yang sudah POSTED tidak bisa diubah.');
        }

        // validasi: kita pakai struktur lines[...] sesuai blade edit
        $req->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['nullable', 'integer', 'exists:finishing_job_lines,id'],
            'lines.*.bundle_id' => ['required', 'integer', 'exists:cutting_job_bundles,id'],
            'lines.*.item_id' => ['required', 'integer', 'exists:items,id'],
            'lines.*.qty_in' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_ok' => ['required', 'numeric', 'min:0'],
            'lines.*.qty_reject' => ['required', 'numeric', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:255'],
        ]);

        DB::beginTransaction();
        try {
            // update header
            $finishingJob->update([
                'date' => $req->date,
                'notes' => $req->notes,
                'updated_by' => Auth::id(),
            ]);

            // hapus dulu lines lama (karena belum posted)
            // jika kamu butuh menyimpan history, ubah strategi (soft-copy)
            $finishingJob->lines()->delete();

            $hasReject = false;

            foreach ($req->input('lines', []) as $idx => $row) {
                // pastikan ambil bundle model (untuk later reduce WIP)
                $bundle = CuttingJobBundle::find($row['bundle_id']);
                if (!$bundle) {
                    // safety — lewati baris yang bundle-nya tidak ada
                    continue;
                }

                $qtyIn = (float) ($row['qty_in'] ?? 0);
                $qtyOk = (float) ($row['qty_ok'] ?? 0);
                $qtyReject = (float) ($row['qty_reject'] ?? 0);
                $rejectReason = $row['reject_reason'] ?? null;

                // consistency checks: qty_ok + qty_reject should not exceed qty_in
                if ($qtyOk + $qtyReject > $qtyIn + 0.0001) {
                    // adjust: cap qty_ok to qty_in - qty_reject, but prefer to throw validation error
                    DB::rollBack();
                    return back()->withInput()
                        ->withErrors(["lines.{$idx}.qty_ok" => 'Qty OK + Qty Reject melebihi Qty IN pada baris ini.']);
                }

                // dapatkan operator jahit terbaru dari sewing return (jika ada)
                $sewingReturnLine = SewingReturnLine::whereHas('sewingPickupLine', function ($q) use ($bundle) {
                    $q->where('cutting_job_bundle_id', $bundle->id);
                })->latest('id')->first();

                $sewingOperatorId = $sewingReturnLine?->sewingPickupLine?->operator_id;
                $sewingOperatorName = null;
                if ($sewingOperatorId) {
                    $emp = Employee::find($sewingOperatorId);
                    $sewingOperatorName = $emp?->name;
                }

                // create new finishing line (per-bundle granularity)
                FinishingJobLine::create([
                    'finishing_job_id' => $finishingJob->id,
                    'bundle_id' => $bundle->id,
                    'item_id' => $row['item_id'],
                    'qty_in' => $qtyIn,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => $qtyReject,
                    'reject_reason' => $rejectReason,
                    'operator_id' => Auth::user()->employee_id ?? null, // operator finishing (user yang edit)
                    'sewing_operator_id' => $sewingOperatorId,
                    'sewing_operator_name' => $sewingOperatorName,
                    'processed_at' => $req->date,
                ]);

                if ($qtyReject > 0) {
                    $hasReject = true;
                }
            }

            // Jika tidak ada reject -> AUTO POST (sama perilaku seperti store()/post())
            if (!$hasReject) {
                // ambil warehouse ids
                $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJECT'];
                $warehouses = Warehouse::whereIn('code', $requiredCodes)->get()->keyBy('code');
                $missing = array_diff($requiredCodes, $warehouses->keys()->all());
                if (!empty($missing)) {
                    DB::rollBack();
                    return redirect()->route('production.finishing_jobs.show', $finishingJob->id)
                        ->withErrors(['warehouse' => 'Warehouse berikut belum dikonfigurasi: ' . implode(', ', $missing)]);
                }

                $wipFinWarehouseId = $warehouses['WIP-FIN']->id;
                $prodWarehouseId = $warehouses['WH-PRD']->id;
                $rejectWarehouseId = $warehouses['REJECT']->id;

                // reload lines & relations
                $finishingJob->load(['lines.bundle', 'lines.item']);

                $movementDate = $finishingJob->date instanceof \DateTimeInterface  ? $finishingJob->date : Carbon::parse($finishingJob->date);

                foreach ($finishingJob->lines as $line) {
                    $qtyIn = (float) ($line->qty_in ?? ($line->qty_ok + $line->qty_reject));
                    $qtyOk = (float) ($line->qty_ok ?? 0);
                    $qtyReject = (float) ($line->qty_reject ?? 0);

                    if ($qtyIn <= 0 && $qtyOk <= 0 && $qtyReject <= 0) {
                        continue;
                    }

                    // ambil unit cost rata-rata dari WIP-FIN
                    $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                        warehouseId: $wipFinWarehouseId,
                        itemId: $line->item_id,
                    );
                    $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;

                    // OUT dari WIP-FIN: qty_in
                    if ($qtyIn > 0) {
                        $this->inventory->stockOut(
                            warehouseId: $wipFinWarehouseId,
                            itemId: $line->item_id,
                            qty: $qtyIn,
                            date: $movementDate,
                            sourceType: FinishingJob::class,
                            sourceId: $finishingJob->id,
                            notes: 'Finishing ' . $finishingJob->code,
                            allowNegative: false,
                            lotId: null,
                            unitCostOverride: $movementUnitCost,
                            affectLotCost: false,
                        );
                    }

                    // IN ke WH-PRD: qty_ok
                    if ($qtyOk > 0) {
                        $this->inventory->stockIn(
                            warehouseId: $prodWarehouseId,
                            itemId: $line->item_id,
                            qty: $qtyOk,
                            date: $movementDate,
                            sourceType: FinishingJob::class,
                            sourceId: $finishingJob->id,
                            notes: 'Finishing OK ' . $finishingJob->code,
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
                                referenceId: $finishingJob->id,
                                qtyBasis: $qtyOk,
                                rmUnitCost: $movementUnitCost,
                                cuttingUnitCost: 0,
                                sewingUnitCost: 0,
                                finishingUnitCost: 0,
                                packagingUnitCost: 0,
                                overheadUnitCost: 0,
                                notes: 'Auto HPP RM-only dari Finishing ' . $finishingJob->code,
                                setActive: false,
                            );
                        }
                    }

                    // IN ke REJECT: qty_reject
                    if ($qtyReject > 0) {
                        $this->inventory->stockIn(
                            warehouseId: $rejectWarehouseId,
                            itemId: $line->item_id,
                            qty: $qtyReject,
                            date: $movementDate,
                            sourceType: FinishingJob::class,
                            sourceId: $finishingJob->id,
                            notes: 'Finishing REJECT ' . $finishingJob->code,
                            lotId: null,
                            unitCost: $movementUnitCost,
                            affectLotCost: false,
                        );
                    }

                    // Kurangi WIP qty di bundle
                    if ($qtyIn > 0 && $line->bundle) {
                        $bundle = $line->bundle;
                        $current = (float) ($bundle->wip_qty ?? 0);
                        $newWipQty = max(0, $current - $qtyIn);
                        $bundle->wip_qty = $newWipQty;
                        $bundle->save();
                    }
                }

                // tandai posted
                $finishingJob->status = 'posted';
                // $finishingJob->posted_at = now();
                $finishingJob->save();
            }

            DB::commit();

            return redirect()->route('production.finishing_jobs.show', $finishingJob->id)
                ->with('success', $hasReject ? 'Finishing diperbarui (ADA REJECT)' : 'Finishing diperbarui & AUTO POSTED (0 reject)');
        } catch (\Throwable $th) {
            DB::rollBack();
            \Log::error('FinishingJobController@update error: ' . $th->getMessage(), ['exception' => $th]);
            throw $th;
        }
    }

    /* ============================
     * BUNDLE ROW (AJAX)
     * ============================ */

    public function bundle_row(Request $req)
    {
        $index = (int) $req->query('index', 0);
        $bundleId = $req->query('bundle_id');
        $bundle = $bundleId ? CuttingJobBundle::with('item')->find($bundleId) : null;

        return view('production.finishing_jobs._bundle_row', [
            'index' => $index,
            'bundle' => $bundle,
        ])->render();
    }

    /* ============================
     * POST
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

        // sekarang butuh WIP-FIN, WH-PRD, REJECT
        $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJECT'];

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
        $prodWarehouseId = $warehouses['WH-PRD']->id;
        $rejectWarehouseId = $warehouses['REJECT']->id;

        $date = $job->date instanceof \DateTimeInterface
        ? $job->date
        : Carbon::parse($job->date);

        // load line + bundle + item
        $job->load(['lines', 'lines.bundle', 'lines.item']);

        DB::transaction(function () use ($job, $wipFinWarehouseId, $prodWarehouseId, $rejectWarehouseId, $date) {

            foreach ($job->lines as $line) {
                $qtyIn = (float) ($line->qty_in ?? ($line->qty_ok + $line->qty_reject));
                $qtyOk = (float) ($line->qty_ok ?? 0);
                $qtyReject = (float) ($line->qty_reject ?? 0);

                if ($qtyIn <= 0 && $qtyOk <= 0 && $qtyReject <= 0) {
                    continue;
                }

                // Ambil unit_cost rata-rata item ini di WIP-FIN
                $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $line->item_id,
                );

                $movementDate = $date;
                $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;

                // OUT dari WIP-FIN: qty_in (OK + reject)
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
                        lotId: null,
                        unitCostOverride: $movementUnitCost,
                        affectLotCost: false,
                    );
                }

                // IN ke WH-PRD: qty_ok
                if ($qtyOk > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $prodWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyOk,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'Finishing OK ' . $job->code,
                        lotId: null,
                        unitCost: $movementUnitCost,
                        affectLotCost: false,
                    );

                    // TRIGGER SNAPSHOT HPP RM-ONLY
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
                            notes: 'Auto HPP RM-only dari Finishing ' . $job->code,
                            setActive: false,
                        );
                    }
                }

                // IN ke REJECT: qty_reject
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

                // Kurangi WIP qty di bundle
                if ($qtyIn > 0 && $line->bundle) {
                    $bundle = $line->bundle;
                    $current = (float) ($bundle->wip_qty ?? 0);
                    $newWipQty = max(0, $current - $qtyIn);
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
            ->with('status', 'Finishing Job berhasil diposting, stok & HPP RM-only sudah dipindahkan dari WIP-FIN ke WH-PRD/REJECT.');
    }

    /* ============================
     * FORCE POST (MANUAL OVERRIDE)
     * ============================ */

    public function forcePost(FinishingJob $finishingJob): RedirectResponse
    {
        $job = $finishingJob;

        if ($job->status === 'posted') {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('info', 'Finishing ini sudah POSTED.');
        }

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
        $prodWarehouseId = Warehouse::where('code', 'WH-PRD')->value('id');
        $rejectWarehouseId = Warehouse::where('code', 'REJECT')->value('id');

        if (!$wipFinWarehouseId || !$prodWarehouseId || !$rejectWarehouseId) {
            return redirect()->back()->withErrors(['warehouse' => 'Warehouse WIP-FIN, WH-PRD, atau REJECT belum dikonfigurasi.']);
        }

        DB::beginTransaction();
        try {
            // Set status ke posted
            $job->status = 'posted';
            $job->updated_by = Auth::id();
            $job->posted_at = now();
            $job->save();

            // eager load lines
            $job->load(['lines.bundle', 'lines.item']);

            foreach ($job->lines as $line) {
                $qtyIn = (float) ($line->qty_in ?? ($line->qty_ok + $line->qty_reject));
                $qtyOk = (float) ($line->qty_ok ?? 0);
                $qtyReject = (float) ($line->qty_reject ?? 0);

                if ($qtyIn <= 0 && $qtyOk <= 0 && $qtyReject <= 0) {
                    continue;
                }

                // ambil unit cost rata-rata dari WIP-FIN
                $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                    warehouseId: $wipFinWarehouseId,
                    itemId: $line->item_id,
                );
                $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;
                $movementDate = $job->date instanceof \DateTimeInterface  ? $job->date : Carbon::parse($job->date);

                // OUT dari WIP-FIN: qty_in (OK + reject)
                if ($qtyIn > 0) {
                    $this->inventory->stockOut(
                        warehouseId: $wipFinWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyIn,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'ForcePost Finishing ' . $job->code,
                        allowNegative: false,
                        lotId: null,
                        unitCostOverride: $movementUnitCost,
                        affectLotCost: false,
                    );
                }

                // IN ke WH-PRD: hanya qty_ok
                if ($qtyOk > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $prodWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyOk,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'ForcePost Finishing OK ' . $job->code,
                        lotId: null,
                        unitCost: $movementUnitCost,
                        affectLotCost: false,
                    );

                    // buat HPP snapshot RM-only (sama seperti post())
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
                            notes: 'Auto HPP RM-only (force post) dari Finishing ' . $job->code,
                            setActive: false,
                        );
                    }
                }

                // IN ke REJECT: qty_reject
                if ($qtyReject > 0) {
                    $this->inventory->stockIn(
                        warehouseId: $rejectWarehouseId,
                        itemId: $line->item_id,
                        qty: $qtyReject,
                        date: $movementDate,
                        sourceType: FinishingJob::class,
                        sourceId: $job->id,
                        notes: 'ForcePost Finishing REJECT ' . $job->code,
                        lotId: null,
                        unitCost: $movementUnitCost,
                        affectLotCost: false,
                    );
                }

                // Kurangi WIP qty di bundle (sama seperti post())
                if ($qtyIn > 0 && $line->bundle) {
                    $bundle = $line->bundle;
                    $current = (float) ($bundle->wip_qty ?? 0);
                    $newWipQty = max(0, $current - $qtyIn);
                    $bundle->wip_qty = $newWipQty;
                    $bundle->save();
                }
            }

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
}
