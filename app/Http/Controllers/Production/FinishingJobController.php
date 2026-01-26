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
use App\Models\Warehouse;
use App\Services\Costing\HppService;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
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
        // urut terbaru: yang terakhir berubah di atas
            ->orderByDesc('date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

        // FILTER STATUS pakai kolom status (bukan posted_at)
        if ($status === 'draft') {
            $query->where('status', 'draft');
        } elseif ($status === 'posted') {
            $query->where('status', 'posted');
        }

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
            'rejectFlag'
        ));
    }

    /* ============================================================
     * CREATE S
     * ============================================================
     */
    public function create(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        $operators = Employee::query()
            ->where('role', 'sewing')
            ->orderBy('name')
            ->get(['id', 'code', 'name']);

        if (!$wipFinWarehouseId) {
            return view('production.finishing_jobs.create', [
                'dateDefault' => $today,
                'linesAll' => [],
                'linesByOp' => [],
                'operators' => $operators,
            ])->withErrors(['warehouse' => 'Gudang WIP-FIN belum dikonfigurasi.']);
        }

        $hasSplVoided = \Schema::hasColumn('sewing_pickup_lines', 'voided_at');
        $hasSrVoided = \Schema::hasColumn('sewing_returns', 'voided_at');

        // 1) daftar item yang punya WIP-FIN real
        $itemIds = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->whereNotNull('finished_item_id')
            ->where('wip_qty', '>', 0)
            ->selectRaw('finished_item_id as item_id')
            ->groupBy('finished_item_id')
            ->pluck('item_id')
            ->map(fn($v) => (int) $v)
            ->values();

        if ($itemIds->isEmpty()) {
            return view('production.finishing_jobs.create', [
                'dateDefault' => $today,
                'linesAll' => [],
                'linesByOp' => [],
                'operators' => $operators,
            ]);
        }

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        // 2) WIP sum per item
        $wipByItem = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->whereIn('finished_item_id', $itemIds)
            ->where('wip_qty', '>', 0)
            ->selectRaw('finished_item_id as item_id, SUM(wip_qty) as wip_sum')
            ->groupBy('finished_item_id')
            ->get()
            ->keyBy('item_id');

        // 3) base query remaining dari sewing_return_lines (qty_ok - finished_qty)
        $base = DB::table('cutting_job_bundles as b')
            ->join('sewing_pickup_lines as spl', 'spl.cutting_job_bundle_id', '=', 'b.id')
            ->join('sewing_return_lines as srl', 'srl.sewing_pickup_line_id', '=', 'spl.id')
            ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
            ->leftJoin('sewing_pickups as sp', 'sp.id', '=', 'spl.sewing_pickup_id')
            ->leftJoin('employees as e', 'e.id', '=', 'sr.operator_id')
            ->where('b.wip_warehouse_id', $wipFinWarehouseId)
            ->where('b.wip_qty', '>', 0)
            ->whereIn('b.finished_item_id', $itemIds)
            ->whereNotNull('b.finished_item_id')
            ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

        if ($hasSplVoided) {
            $base->whereNull('spl.voided_at');
        }

        if ($hasSrVoided) {
            $base->whereNull('sr.voided_at');
        }

        // 4A) remaining per item+op
        $rowsByOp = (clone $base)
            ->selectRaw('
                b.finished_item_id as item_id,
                sr.operator_id as operator_id,
                MAX(e.code) as operator_code,
                MAX(e.name) as operator_name,
                SUM(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) as remaining_ok,
                MAX(sp.date) as pickup_date,
                MAX(sr.date) as setor_date
            ')
            ->groupBy('b.finished_item_id', 'sr.operator_id')
            ->orderBy('b.finished_item_id')
            ->orderByRaw('COALESCE(MAX(e.code), "")')
            ->get();

        // 4B) remaining per item
        $rowsAll = (clone $base)
            ->selectRaw('
                b.finished_item_id as item_id,
                SUM(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) as remaining_ok,
                MAX(sp.date) as pickup_date,
                MAX(sr.date) as setor_date
            ')
            ->groupBy('b.finished_item_id')
            ->orderBy('b.finished_item_id')
            ->get();

        // 5) eligible WIP per item+op (biar BYOP ga ngaco)
        $qEligibleBundles = DB::table('cutting_job_bundles as b')
            ->join('sewing_pickup_lines as spl', 'spl.cutting_job_bundle_id', '=', 'b.id')
            ->join('sewing_return_lines as srl', 'srl.sewing_pickup_line_id', '=', 'spl.id')
            ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
            ->where('b.wip_warehouse_id', $wipFinWarehouseId)
            ->where('b.wip_qty', '>', 0)
            ->whereIn('b.finished_item_id', $itemIds)
            ->whereNotNull('b.finished_item_id')
            ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

        if ($hasSplVoided) {
            $qEligibleBundles->whereNull('spl.voided_at');
        }

        if ($hasSrVoided) {
            $qEligibleBundles->whereNull('sr.voided_at');
        }

        $eligibleBundleRows = $qEligibleBundles
            ->selectRaw('
                b.id as bundle_id,
                b.finished_item_id as item_id,
                sr.operator_id as operator_id,
                MAX(b.wip_qty) as bundle_wip
            ')
            ->groupBy('b.id', 'b.finished_item_id', 'sr.operator_id')
            ->get();

        $eligibleWipByItemOp = [];
        foreach ($eligibleBundleRows as $r) {
            $key = (int) $r->item_id . ':' . (int) $r->operator_id;
            $eligibleWipByItemOp[$key] = ($eligibleWipByItemOp[$key] ?? 0) + (int) round((float) ($r->bundle_wip ?? 0));
        }

        // 6) build lines
        $linesByOp = collect($rowsByOp)->map(function ($r) use ($items, $eligibleWipByItemOp) {
            $itemId = (int) $r->item_id;
            $opId = (int) ($r->operator_id ?? 0);

            $it = $items->get($itemId);
            $remaining = (int) round((float) ($r->remaining_ok ?? 0));
            $eligibleWip = (int) ($eligibleWipByItemOp["{$itemId}:{$opId}"] ?? 0);
            $cap = max(0, min($remaining, $eligibleWip));

            return [
                'item_id' => $itemId,
                'item_code' => strtoupper($it?->code ?? ('ITEM-' . $itemId)),
                'item_name' => $it?->name ?? null,
                'total_wip' => $cap,

                'operator_id' => $opId,
                'operator_code' => $r->operator_code ?? null,
                'operator_name' => $r->operator_name ?? null,

                'pickup_date' => $r->pickup_date ?? null,
                'setor_date' => $r->setor_date ?? null,

                'qty_in' => null,
                'qty_reject' => 0,
                'reject_notes' => null,
            ];
        })->filter(fn($l) => (int) $l['total_wip'] > 0)->values()->all();

        $linesAll = collect($rowsAll)->map(function ($r) use ($items, $wipByItem) {
            $itemId = (int) $r->item_id;
            $it = $items->get($itemId);

            $remaining = (int) round((float) ($r->remaining_ok ?? 0));
            $wipSum = (int) round((float) (optional($wipByItem->get($itemId))->wip_sum ?? 0));
            $cap = max(0, min($remaining, $wipSum));

            return [
                'item_id' => $itemId,
                'item_code' => strtoupper($it?->code ?? ('ITEM-' . $itemId)),
                'item_name' => $it?->name ?? null,
                'total_wip' => $cap,

                'operator_id' => 0,
                'operator_code' => null,
                'operator_name' => null,

                'pickup_date' => $r->pickup_date ?? null,
                'setor_date' => $r->setor_date ?? null,

                'qty_in' => null,
                'qty_reject' => 0,
                'reject_notes' => null,
            ];
        })->filter(fn($l) => (int) $l['total_wip'] > 0)->values()->all();

        return view('production.finishing_jobs.create', [
            'dateDefault' => $today,
            'linesAll' => $linesAll,
            'linesByOp' => $linesByOp,
            'operators' => $operators,
        ]);
    }

    /* ============================================================
     * STORE (Draft + LOCK SewingReturn finished_qty)
     * ============================================================
     */
    public function store(Request $request): RedirectResponse
    {
        $mode = $request->input('operator_mode', 'all');
        $mode = in_array($mode, ['all', 'byop'], true) ? $mode : 'all';
        $linesKey = $mode === 'all' ? 'lines_all' : 'lines_byop';

        $finishingOperatorId = (int) ($request->input('operator_global_id') ?? 0);

        $hasSplVoided = \Schema::hasColumn('sewing_pickup_lines', 'voided_at');
        $hasSrVoided = \Schema::hasColumn('sewing_returns', 'voided_at');

        $rules = [
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'operator_mode' => ['required', 'in:all,byop'],

            'operator_global_id' => [
                $mode === 'all' ? 'required' : 'nullable',
                'integer',
                'exists:employees,id',
            ],

            $linesKey => ['required', 'array', 'min:1'],
            $linesKey . '.*.item_id' => ['required', 'integer', 'exists:items,id'],
            $linesKey . '.*.qty_in' => ['nullable', 'integer', 'min:0'],
            $linesKey . '.*.qty_reject' => ['nullable', 'integer', 'min:0'],
            $linesKey . '.*.reject_reason' => ['nullable', 'string', 'max:100'],
            $linesKey . '.*.reject_notes' => ['nullable', 'string'],

            $linesKey . '.*.operator_id' => [
                $mode === 'byop' ? 'required' : 'nullable',
                'integer',
                'exists:employees,id',
            ],
        ];

        $validated = $request->validate($rules);

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');
        if (!$wipFinWarehouseId) {
            return back()->withInput()->withErrors([
                'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi. Set kode warehouse WIP-FIN terlebih dahulu.',
            ]);
        }

        if ($mode === 'all' && $finishingOperatorId <= 0) {
            return back()->withInput()->withErrors([
                'operator_global_id' => 'Wajib pilih operator jahit (mode ALL).',
            ]);
        }

        $workLines = [];

        foreach (($validated[$linesKey] ?? []) as $index => $line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $qtyIn = max(0, (int) ($line['qty_in'] ?? 0));
            $qtyRj = max(0, (int) ($line['qty_reject'] ?? 0));

            if ($qtyIn <= 0 && $qtyRj > 0) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => 'Qty Setor harus > 0 jika ada Reject.',
                ]);
            }
            if ($qtyIn <= 0) {
                continue;
            }

            if ($qtyRj > $qtyIn) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_reject" => 'Qty Reject tidak boleh > Qty Setor.',
                ]);
            }

            $sewingOperatorId = ($mode === 'all')
            ? $finishingOperatorId
            : (int) ($line['operator_id'] ?? 0);

            if ($sewingOperatorId <= 0) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.operator_id" => 'Operator wajib dipilih.',
                ]);
            }

            // Remaining OK operator per item (berdasarkan sewing_return_lines)
            $qRemaining = DB::table('sewing_return_lines as srl')
                ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
                ->join('sewing_pickup_lines as spl', 'spl.id', '=', 'srl.sewing_pickup_line_id')
                ->where('spl.finished_item_id', $itemId)
                ->where('sr.operator_id', $sewingOperatorId)
                ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

            if ($hasSplVoided) {
                $qRemaining->whereNull('spl.voided_at');
            }

            if ($hasSrVoided) {
                $qRemaining->whereNull('sr.voided_at');
            }

            $remaining = (int) ($qRemaining
                    ->selectRaw('SUM(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) as remaining')
                    ->value('remaining') ?? 0);

            if ($remaining <= 0) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => 'Tidak ada sisa jahitan untuk operator ini.',
                ]);
            }

            if ($qtyIn > $remaining) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => "Qty melebihi sisa operator. Maks: {$remaining}.",
                ]);
            }

            // Total WIP-FIN per item
            $totalWip = (int) round((float) CuttingJobBundle::query()
                    ->readyForFinishing($wipFinWarehouseId)
                    ->where('finished_item_id', $itemId)
                    ->sum('wip_qty'));

            if ($totalWip <= 0) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.item_id" => 'Tidak ada saldo WIP-FIN untuk item ini.',
                ]);
            }

            if ($qtyIn > $totalWip) {
                throw ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => "Qty melebihi saldo WIP-FIN item. Maks: {$totalWip}.",
                ]);
            }

            $workLines[] = [
                'index' => $index,
                'item_id' => $itemId,
                'sewing_operator_id' => $sewingOperatorId,
                'qty_in' => $qtyIn,
                'qty_reject' => $qtyRj,
                'reject_reason' => $line['reject_reason'] ?? null,
                'reject_notes' => $line['reject_notes'] ?? null,
            ];
        }

        if (empty($workLines)) {
            return back()->withInput()->withErrors([
                $linesKey => 'Isi minimal satu Qty Setor > 0.',
            ]);
        }

        $empNameCache = [];

        $job = DB::transaction(function () use (
            $request,
            $validated,
            $linesKey,
            $wipFinWarehouseId,
            $workLines,
            $hasSplVoided,
            $hasSrVoided,
            $finishingOperatorId,
            &$empNameCache
        ) {
            $job = FinishingJob::create([
                'code' => CodeGenerator::generate('FIN'),
                'date' => $validated['date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'created_by' => $request->user()->id,
                'updated_by' => $request->user()->id,
            ]);

            foreach ($workLines as $wl) {
                $itemId = (int) $wl['item_id'];
                $sewingOperatorId = (int) $wl['sewing_operator_id'];

                $needIn = (int) $wl['qty_in'];
                $needReject = (int) $wl['qty_reject'];

                $bundles = CuttingJobBundle::query()
                    ->readyForFinishing($wipFinWarehouseId)
                    ->where('finished_item_id', $itemId)
                    ->whereExists(function ($q) use ($sewingOperatorId, $itemId, $hasSplVoided, $hasSrVoided) {
                        $q->select(DB::raw(1))
                            ->from('sewing_return_lines as srl')
                            ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
                            ->join('sewing_pickup_lines as spl', 'spl.id', '=', 'srl.sewing_pickup_line_id')
                            ->whereColumn('spl.cutting_job_bundle_id', 'cutting_job_bundles.id')
                            ->where('spl.finished_item_id', $itemId)
                            ->where('sr.operator_id', $sewingOperatorId)
                            ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

                        if ($hasSplVoided) {
                            $q->whereNull('spl.voided_at');
                        }

                        if ($hasSrVoided) {
                            $q->whereNull('sr.voided_at');
                        }

                    })
                    ->orderBy('id')
                    ->get();

                if ($bundles->isEmpty()) {
                    throw ValidationException::withMessages([
                        "{$linesKey}.{$wl['index']}.qty_in" => 'Tidak ada bundle WIP-FIN yang match operator ini.',
                    ]);
                }

                $sewingOperatorName = $empNameCache[$sewingOperatorId] ??=
                    (Employee::whereKey($sewingOperatorId)->value('name') ?: null);

                foreach ($bundles as $bundle) {
                    if ($needIn <= 0) {
                        break;
                    }

                    $bundleWip = (int) round((float) ($bundle->wip_qty ?? 0));
                    if ($bundleWip <= 0) {
                        continue;
                    }

                    $qBundleRemaining = DB::table('sewing_return_lines as srl')
                        ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
                        ->join('sewing_pickup_lines as spl', 'spl.id', '=', 'srl.sewing_pickup_line_id')
                        ->where('spl.cutting_job_bundle_id', $bundle->id)
                        ->where('spl.finished_item_id', $itemId)
                        ->where('sr.operator_id', $sewingOperatorId)
                        ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

                    if ($hasSplVoided) {
                        $qBundleRemaining->whereNull('spl.voided_at');
                    }

                    if ($hasSrVoided) {
                        $qBundleRemaining->whereNull('sr.voided_at');
                    }

                    $bundleRemaining = (int) ($qBundleRemaining
                            ->selectRaw('SUM(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) as remaining')
                            ->value('remaining') ?? 0);

                    if ($bundleRemaining <= 0) {
                        continue;
                    }

                    $takeIn = min($needIn, $bundleWip, $bundleRemaining);

                    $takeReject = 0;
                    if ($needReject > 0) {
                        $takeReject = min($needReject, $takeIn);
                    }

                    $takeOk = $takeIn - $takeReject;

                    // ✅ LOCK/RESERVE sewing_return_lines.finished_qty untuk qty_in (OK+Reject)
                    $toConsume = $takeIn;

                    $qRows = DB::table('sewing_return_lines as srl')
                        ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
                        ->join('sewing_pickup_lines as spl', 'spl.id', '=', 'srl.sewing_pickup_line_id')
                        ->where('spl.cutting_job_bundle_id', $bundle->id)
                        ->where('spl.finished_item_id', $itemId)
                        ->where('sr.operator_id', $sewingOperatorId)
                        ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

                    if ($hasSplVoided) {
                        $qRows->whereNull('spl.voided_at');
                    }

                    if ($hasSrVoided) {
                        $qRows->whereNull('sr.voided_at');
                    }

                    $srlRows = $qRows
                        ->select([
                            'srl.id',
                            DB::raw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) as remaining'),
                        ])
                        ->orderBy('srl.id')
                        ->get();

                    foreach ($srlRows as $r) {
                        if ($toConsume <= 0) {
                            break;
                        }

                        $rem = (int) $r->remaining;
                        if ($rem <= 0) {
                            continue;
                        }

                        $use = min($rem, $toConsume);

                        DB::table('sewing_return_lines')
                            ->where('id', $r->id)
                            ->update([
                                'finished_qty' => DB::raw('COALESCE(finished_qty,0) + ' . (int) $use),
                                'updated_at' => now(),
                            ]);

                        $toConsume -= $use;
                    }

                    if ($toConsume > 0) {
                        throw ValidationException::withMessages([
                            "{$linesKey}.{$wl['index']}.qty_in" => 'Sisa operator berubah saat proses. Refresh lalu ulang.',
                        ]);
                    }

                    $lineOperatorId = $finishingOperatorId > 0 ? $finishingOperatorId : $sewingOperatorId;

                    FinishingJobLine::create([
                        'finishing_job_id' => $job->id,
                        'bundle_id' => $bundle->id,
                        'operator_id' => (int) $lineOperatorId,

                        'sewing_operator_id' => $sewingOperatorId,
                        'sewing_operator_name' => $sewingOperatorName,

                        'item_id' => $itemId,
                        'qty_in' => $takeIn,
                        'qty_ok' => $takeOk,
                        'qty_reject' => $takeReject,
                        'reject_reason' => $wl['reject_reason'],
                        'reject_notes' => $wl['reject_notes'],
                        'processed_at' => $validated['date'],
                    ]);

                    $needIn -= $takeIn;
                    $needReject -= $takeReject;
                }

                if ($needIn > 0) {
                    throw ValidationException::withMessages([
                        "{$linesKey}.{$wl['index']}.qty_in" => "Tidak cukup WIP/operator untuk alokasi. Sisa: {$needIn}.",
                    ]);
                }
            }

            return $job;
        });

        return redirect()
            ->route('production.finishing_jobs.show', ['finishing_job' => $job->id])
            ->with('status', 'Finishing Job berhasil dibuat sebagai draft.');
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

        // ⚠️ WARNING: job draft sudah reserve finished_qty pada sewing_return_lines.
        // Kalau diedit, idealnya release reservation dulu. Kalau belum ada mekanismenya:
        // DISARANKAN: jangan edit draft, bikin job baru / batalkan.
        // Kamu bisa block dengan:
        // return redirect()->route('production.finishing_jobs.show', $finishingJob->id)
        //     ->with('error', 'Draft ini sudah melakukan reservasi Sewing Return. Untuk saat ini tidak bisa diedit.');

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

        // ⚠️ PENTING:
        // Karena store() sudah reserve finished_qty, update draft tanpa release reservation akan bikin mismatch.
        // Rekomendasi aman: BLOCK update draft.
        return redirect()
            ->route('production.finishing_jobs.show', $finishingJob->id)
            ->with('error', 'Untuk saat ini draft finishing yang sudah dibuat tidak bisa di-edit karena sudah mengunci saldo Sewing Return. Buat Finishing baru atau tambahkan fitur "release reservation" dulu.');

        // Kalau kamu mau aku bikinin versi update yang "release reservation + reserve ulang",
        // tinggal bilang: nanti aku buat fungsi releaseFinishedQtyForDraftJob($job) + reserve ulang.
    }

    /* ============================================================
     * POST
     * ============================================================
     */
    public function post(FinishingJob $finishing_job): RedirectResponse
    {
        $job = $finishing_job->loadMissing(['lines', 'lines.bundle', 'lines.item']);

        if (!$job || !$job->id) {
            return redirect()
                ->route('production.finishing_jobs.index')
                ->withErrors(['finishing_job' => 'Finishing Job tidak ditemukan.']);
        }

        if (($job->status ?? null) === 'posted') {
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

        // ✅ PENTING: karena finished_qty sudah di-lock di store(),
        // post() JANGAN validasi sewing_return lagi dan JANGAN allocate finished_qty lagi.

        DB::transaction(function () use ($job, $warehouses, $movementDate) {
            $this->applyPostingMovements(
                job: $job,
                wipFinWarehouseId: $warehouses['WIP-FIN']->id,
                prodWarehouseId: $warehouses['WH-PRD']->id,
                rejectWarehouseId: $warehouses['REJECT']->id,
                movementDate: $movementDate,
                notesPrefix: 'Finishing ' . $job->code,
            );

            $update = [
                'status' => 'posted',
                'updated_by' => auth()->id(),
            ];

            if (\Schema::hasColumn('finishing_jobs', 'posted_at')) {
                $update['posted_at'] = now();
            }

            $job->update($update);
        });

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil diposting. Stok dipindahkan dari WIP-FIN ke WH-PRD/REJECT.');
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

    protected function resolveMovementDate(FinishingJob $job): \DateTimeInterface
    {
        $date = $job->date;
        if ($date instanceof \DateTimeInterface) {
            return $date;
        }

        return Carbon::parse($date ?? now());
    }

    /**
     * Apply mutasi stok + snapshot HPP RM-only.
     * NOTE PENTING:
     * - finished_qty sewing_return_lines SUDAH di-lock saat store().
     * - Jadi di sini TIDAK BOLEH allocate / update finished_qty lagi.
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

            if ($qtyUsed <= 0) {
                continue;
            }

            $unitCostWipFin = $this->inventory->getItemIncomingUnitCost(
                warehouseId: $wipFinWarehouseId,
                itemId: (int) $line->item_id,
            );
            $movementUnitCost = $unitCostWipFin > 0 ? $unitCostWipFin : null;

            // 1) OUT WIP-FIN (OK+Reject)
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

            // 4) Kurangi wip_qty bundle (OK+Reject)
            if ($line->bundle) {
                $bundle = $line->bundle;
                $current = (int) round((float) ($bundle->wip_qty ?? 0));
                $usedFromBundle = $qtyUsed;

                if ($usedFromBundle > 0) {
                    $bundle->wip_qty = max(0, $current - $usedFromBundle);
                    $bundle->save();
                }
            }
        }
    }
}
