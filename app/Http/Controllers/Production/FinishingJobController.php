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
            'finishingJobs', 'search', 'status', 'rejectFlag'
        ));
    }

    /* ============================================================
     * CREATE
     * ============================================================
     */

    public function create(Request $request): View
    {
        $today = \Carbon\Carbon::today()->toDateString();

        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        // dropdown modal (operator jahit / finishing) - kamu bisa sesuaikan rolenya
        $operators = Employee::query()
            ->where('role', 'sewing') // <- kalau operator finishing beda role, ganti jadi ['sewing','finishing'] dll
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

        // schema beda2 (sqlite / mysql)
        $hasSplVoided = \Schema::hasColumn('sewing_pickup_lines', 'voided_at');
        $hasSrVoided = \Schema::hasColumn('sewing_returns', 'voided_at');

        /**
         * 1) Ambil daftar item yang punya WIP-FIN real
         */
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

        /**
         * 2) WIP-FIN SUM per item (REAL)
         */
        $wipByItem = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->whereIn('finished_item_id', $itemIds)
            ->where('wip_qty', '>', 0)
            ->selectRaw('finished_item_id as item_id, SUM(wip_qty) as wip_sum')
            ->groupBy('finished_item_id')
            ->get()
            ->keyBy('item_id'); // {item_id: wip_sum}

        /**
         * 3) Base query remaining dari sewing_return_lines:
         * remaining_ok = SUM(qty_ok - finished_qty)
         * + last pickup/setor date (optional)
         */
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

        /**
         * 4A) Remaining per ITEM+OP (BYOP)
         */
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

        /**
         * 4B) Remaining per ITEM (ALL)
         */
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

        /**
         * 5) WIP “eligible” per ITEM+OP
         *    Artinya: jumlah wip_qty dari bundle yang memang punya remaining untuk operator tsb
         *    (ini yang bikin angka BYOP tidak “lebih besar dari real allocatable”)
         *
         * NOTE: join besar bisa dup karena banyak srl per bundle, jadi:
         * - groupBy(bundle_id, item_id, operator_id) dulu (ambil MAX(wip_qty))
         * - lalu SUM di luar
         */
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

        // sum eligible wip per (item_id, operator_id)
        $eligibleWipByItemOp = [];
        foreach ($eligibleBundleRows as $r) {
            $key = (int) $r->item_id . ':' . (int) $r->operator_id;
            $eligibleWipByItemOp[$key] = ($eligibleWipByItemOp[$key] ?? 0) + (int) round((float) ($r->bundle_wip ?? 0));
        }

        /**
         * 6) Build linesByOp dan linesAll
         *    total_wip = MIN(remaining_ok, eligible_wip) (BYOP)
         *    total_wip = MIN(remaining_ok, wip_sum_item) (ALL)
         */
        $linesByOp = collect($rowsByOp)->map(function ($r) use ($items, $eligibleWipByItemOp) {
            $itemId = (int) $r->item_id;
            $opId = (int) ($r->operator_id ?? 0);

            $it = $items->get($itemId);

            $remaining = (int) round((float) ($r->remaining_ok ?? 0));
            $eligibleWip = (int) ($eligibleWipByItemOp["{$itemId}:{$opId}"] ?? 0);

            $cap = min($remaining, $eligibleWip);
            if ($cap < 0) {
                $cap = 0;
            }

            return [
                'item_id' => $itemId,
                'item_code' => strtoupper($it?->code ?? ('ITEM-' . $itemId)),
                'item_name' => $it?->name ?? null,

                // ✅ yang ditampilkan di UI (sudah dicap)
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

            $cap = min($remaining, $wipSum);
            if ($cap < 0) {
                $cap = 0;
            }

            return [
                'item_id' => $itemId,
                'item_code' => strtoupper($it?->code ?? ('ITEM-' . $itemId)),
                'item_name' => $it?->name ?? null,

                // ✅ yang ditampilkan di UI (cap remaining vs WIP real)
                'total_wip' => $cap,

                // operator kosong (dipilih di modal)
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
     * STORE (INTEGER)
     * ============================================================
     */

    public function store(Request $request): RedirectResponse
    {
        // ============================================================
        // 0) Mode & lines key (harus match Blade)
        // ============================================================
        $mode = $request->input('operator_mode', 'all');
        $mode = in_array($mode, ['all', 'byop'], true) ? $mode : 'all';

        $linesKey = $mode === 'all' ? 'lines_all' : 'lines_byop';

        // Operator finishing (dipakai isi finishing_job_lines.operator_id)
        // - Mode ALL: wajib dipilih dari modal => operator_global_id
        // - Mode BYOP: boleh kosong, nanti fallback ke operator jahit per baris
        $finishingOperatorId = (int) ($request->input('operator_global_id') ?? 0);

        // Schema compatibility
        $hasSplVoided = \Schema::hasColumn('sewing_pickup_lines', 'voided_at');
        $hasSrVoided = \Schema::hasColumn('sewing_returns', 'voided_at');

        // ============================================================
        // 1) Validate request
        // ============================================================
        $rules = [
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'operator_mode' => ['required', 'in:all,byop'],

            // mode ALL wajib operator_global_id (modal)
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

            // byop wajib operator_id per baris
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

        // ============================================================
        // 2) Build workLines + VALIDASI KERAS
        // - Validasi remaining operator per item
        // - Validasi total WIP per item
        // ============================================================
        $workLines = [];

        foreach (($validated[$linesKey] ?? []) as $index => $line) {
            $itemId = (int) ($line['item_id'] ?? 0);
            $qtyIn = max(0, (int) ($line['qty_in'] ?? 0));
            $qtyRj = max(0, (int) ($line['qty_reject'] ?? 0));

            if ($qtyIn <= 0 && $qtyRj > 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => 'Qty Setor harus > 0 jika ada Reject.',
                ]);
            }

            if ($qtyIn <= 0) {
                continue;
            }

            if ($qtyRj > $qtyIn) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_reject" => 'Qty Reject tidak boleh > Qty Setor.',
                ]);
            }

            // sewing operator sumber jatah:
            $sewingOperatorId = ($mode === 'all')
            ? $finishingOperatorId
            : (int) ($line['operator_id'] ?? 0);

            if ($sewingOperatorId <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$linesKey}.{$index}.operator_id" => 'Operator wajib dipilih.',
                ]);
            }

            // Remaining OK operator per item
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
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => 'Tidak ada sisa jahitan untuk operator ini.',
                ]);
            }

            if ($qtyIn > $remaining) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$linesKey}.{$index}.qty_in" => "Qty melebihi sisa operator. Maks: {$remaining}.",
                ]);
            }

            // Total WIP-FIN per item (anti mismatch)
            $totalWip = (int) round((float) CuttingJobBundle::query()
                    ->readyForFinishing($wipFinWarehouseId)
                    ->where('finished_item_id', $itemId)
                    ->sum('wip_qty'));

            if ($totalWip <= 0) {
                throw \Illuminate\Validation\ValidationException::withMessages([
                    "{$linesKey}.{$index}.item_id" => 'Tidak ada saldo WIP-FIN untuk item ini.',
                ]);
            }

            if ($qtyIn > $totalWip) {
                throw \Illuminate\Validation\ValidationException::withMessages([
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

        // cache nama operator
        $empNameCache = [];

        // ============================================================
        // 3) TRANSACTION: create job + allocate bundles + consume finished_qty + create lines
        //    ⚠️ store tidak mengurangi WIP bundle (sesuai request kamu)
        // ============================================================
        $job = DB::transaction(function () use (
            $request,
            $validated,
            $mode,
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

                // bundles WIP-FIN yang match operator tsb (anti makan jatah operator lain)
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
                    throw \Illuminate\Validation\ValidationException::withMessages([
                        "{$linesKey}.{$wl['index']}.qty_in" => 'Tidak ada bundle WIP-FIN yang match operator ini.',
                    ]);
                }

                $sewingOperatorName = $empNameCache[$sewingOperatorId] ??= (Employee::whereKey($sewingOperatorId)->value('name') ?: null);

                foreach ($bundles as $bundle) {
                    if ($needIn <= 0) {
                        break;
                    }

                    $bundleWip = (int) round((float) ($bundle->wip_qty ?? 0));
                    if ($bundleWip <= 0) {
                        continue;
                    }

                    // remaining operator untuk bundle ini
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

                    // ambil maksimum yang aman
                    $takeIn = min($needIn, $bundleWip, $bundleRemaining);

                    // reject diambil dari qty_in
                    $takeReject = 0;
                    if ($needReject > 0) {
                        $takeReject = min($needReject, $takeIn);
                    }

                    $takeOk = $takeIn - $takeReject;

                    // consume finished_qty FIFO
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
                        throw \Illuminate\Validation\ValidationException::withMessages([
                            "{$linesKey}.{$wl['index']}.qty_in" => 'Sisa operator berubah saat proses. Refresh lalu ulang.',
                        ]);
                    }

                    // ==== operator_id (FK) harus valid ====
                    // - Mode ALL: operator finishing = operator_global_id (modal)
                    // - Mode BYOP: jika operator_global_id kosong, fallback pakai sewingOperatorId
                    $lineOperatorId = $finishingOperatorId > 0 ? $finishingOperatorId : $sewingOperatorId;

                    FinishingJobLine::create([
                        'finishing_job_id' => $job->id,
                        'bundle_id' => $bundle->id,

                        // ✅ selalu valid (menghindari 0)
                        'operator_id' => (int) $lineOperatorId,

                        // ✅ operator jahit sumber
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
                    throw \Illuminate\Validation\ValidationException::withMessages([
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
