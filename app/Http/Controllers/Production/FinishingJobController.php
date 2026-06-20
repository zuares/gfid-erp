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
use App\Services\Accounting\JournalService;
use App\Services\Costing\HppService;
use App\Services\Inventory\InventoryService;
use App\Services\Production\FinishingBomService;
use Carbon\Carbon;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class FinishingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected HppService $hpp,
        protected FinishingBomService $finBom,
        protected JournalService $journal,
    ) {}

    /**
     * FASE 3b — apakah kesiapan finishing dibaca dari ledger (bukan cache).
     */
    private function readinessFromLedger(): bool
    {
        return config('inventory.readiness_source') === 'ledger';
    }

    /**
     * FASE 3b — SQL fragment saldo WIP-FIN per bundle dari LEDGER.
     * $alias = alias tabel cutting_job_bundles pada query pemanggil.
     * Kode gudang berasal dari config (tepercaya), aman di-inline.
     */
    private function wipFinLedgerSql(string $alias = 'b'): string
    {
        $code = config('inventory.warehouses.wip_fin', 'WIP-FIN');

        return 'COALESCE((SELECT SUM(im.qty_change) FROM inventory_mutations im '
            . 'INNER JOIN warehouses w ON w.id = im.warehouse_id '
            . "WHERE im.cutting_job_bundle_id = {$alias}.id AND w.code = '{$code}'), 0)";
    }

    private function finishingDestinationWarehouses()
    {
        return Warehouse::whereIn('code', ['WH-PRD', 'WH-RTS'])
            ->get(['id', 'code', 'name'])
            ->sortBy(fn($w) => ['WH-PRD' => 0, 'WH-RTS' => 1][$w->code] ?? 99)
            ->values();
    }

    private function defaultDestinationCodeForUser($user): string
    {
        $role = strtolower(trim((string) ($user?->role ?? '')));

        return in_array($role, ['admin', 'operating']) ? 'WH-PRD' : 'WH-RTS';
    }

    private function defaultDestinationIdForUser($user, $destinationWarehouses): ?int
    {
        $defaultCode = $this->defaultDestinationCodeForUser($user);

        return $destinationWarehouses->firstWhere('code', $defaultCode)?->id
            ?? $destinationWarehouses->first()?->id;
    }

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
            ->withSum('lines as total_in', 'qty_in')
            // ✅ untuk chip detail barang di index
            ->with(['lines.item:id,code,name'])
            ->orderByDesc('date')
            ->orderByDesc('updated_at')
            ->orderByDesc('id');

        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                    ->orWhere('notes', 'like', "%{$search}%");
            });
        }

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
     * CREATE
     * ============================================================
     */
    public function create(Request $request): View
    {
        $today = Carbon::today()->toDateString();
        $wipFinWarehouseId = Warehouse::where('code', 'WIP-FIN')->value('id');

        // Admin default ke WH-PRD, role lain tetap ke WH-RTS.
        $destinationWarehouses = $this->finishingDestinationWarehouses();
        $defaultDestinationId = $this->defaultDestinationIdForUser($request->user(), $destinationWarehouses);

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
                'destinationWarehouses' => $destinationWarehouses,
                'defaultDestinationId' => $defaultDestinationId,
            ])->withErrors(['warehouse' => 'Gudang WIP-FIN belum dikonfigurasi.']);
        }

        $hasSplVoided = \Schema::hasColumn('sewing_pickup_lines', 'voided_at');
        $hasSrVoided = \Schema::hasColumn('sewing_returns', 'voided_at');

        // 1) daftar item yang punya WIP-FIN real
        $itemIds = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->whereNotNull('finished_item_id')
            ->selectRaw('finished_item_id as item_id')
            ->groupBy('finished_item_id')
            ->pluck('item_id')
            ->map(fn($v) => (int) $v)
            ->values();

        if ($itemIds->isEmpty()) {
            return view('production.finishing_jobs.create', [
                'dateDefault'           => $today,
                'linesAll'              => [],
                'linesByOp'             => [],
                'operators'             => $operators,
                'destinationWarehouses' => $destinationWarehouses,
                'defaultDestinationId'  => $defaultDestinationId,
                'packingSupplies'       => collect(),
            ]);
        }

        $items = Item::query()
            ->whereIn('id', $itemIds)
            ->get(['id', 'code', 'name'])
            ->keyBy('id');

        // 2) WIP sum per item
        $wipSumExpr = $this->readinessFromLedger()
            ? 'finished_item_id as item_id, SUM(' . $this->wipFinLedgerSql('cutting_job_bundles') . ') as wip_sum'
            : 'finished_item_id as item_id, SUM(wip_qty) as wip_sum';

        $wipByItem = CuttingJobBundle::query()
            ->readyForFinishing($wipFinWarehouseId)
            ->whereIn('finished_item_id', $itemIds)
            ->selectRaw($wipSumExpr)
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
            ->where(function ($q) use ($wipFinWarehouseId) {
                if ($this->readinessFromLedger()) {
                    $q->whereRaw($this->wipFinLedgerSql('b') . ' > 0.0001');
                } else {
                    $q->where('b.wip_warehouse_id', $wipFinWarehouseId)
                        ->where('b.wip_qty', '>', 0);
                }
            })
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

        // 5) eligible WIP per item+op
        $qEligibleBundles = DB::table('cutting_job_bundles as b')
            ->join('sewing_pickup_lines as spl', 'spl.cutting_job_bundle_id', '=', 'b.id')
            ->join('sewing_return_lines as srl', 'srl.sewing_pickup_line_id', '=', 'spl.id')
            ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
            ->where(function ($q) use ($wipFinWarehouseId) {
                if ($this->readinessFromLedger()) {
                    $q->whereRaw($this->wipFinLedgerSql('b') . ' > 0.0001');
                } else {
                    $q->where('b.wip_warehouse_id', $wipFinWarehouseId)
                        ->where('b.wip_qty', '>', 0);
                }
            })
            ->whereIn('b.finished_item_id', $itemIds)
            ->whereNotNull('b.finished_item_id')
            ->whereRaw('(COALESCE(srl.qty_ok,0) - COALESCE(srl.finished_qty,0)) > 0');

        if ($hasSplVoided) {
            $qEligibleBundles->whereNull('spl.voided_at');
        }

        if ($hasSrVoided) {
            $qEligibleBundles->whereNull('sr.voided_at');
        }

        $bundleWipExpr = $this->readinessFromLedger()
            ? 'MAX(' . $this->wipFinLedgerSql('b') . ')'
            : 'MAX(b.wip_qty)';

        $eligibleBundleRows = $qEligibleBundles
            ->selectRaw('
                b.id as bundle_id,
                b.finished_item_id as item_id,
                sr.operator_id as operator_id,
                ' . $bundleWipExpr . ' as bundle_wip
            ')
            ->groupBy('b.id', 'b.finished_item_id', 'sr.operator_id')
            ->get();

        $eligibleWipByItemOp = [];
        foreach ($eligibleBundleRows as $r) {
            $key = (int) $r->item_id . ':' . (int) $r->operator_id;
            $eligibleWipByItemOp[$key] = ($eligibleWipByItemOp[$key] ?? 0) + (int) round((float) ($r->bundle_wip ?? 0));
        }

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

        // 6) Packing supply status — kebutuhan kelengkapan packing vs stok RM
        $packingSupplies = collect();
        $rmWarehouseId   = Warehouse::where('code', 'RM')->value('id');
        if ($rmWarehouseId && $itemIds->isNotEmpty()) {
            $bomLines = DB::table('item_bom_lines as ibl')
                ->join('item_boms as ib', 'ib.id', '=', 'ibl.item_bom_id')
                ->join('items as mat', 'mat.id', '=', 'ibl.material_item_id')
                ->whereIn('ib.item_id', $itemIds)
                ->where('ib.active', true)
                ->where('ibl.usage_stage', 'packing_supply')
                ->where('ibl.is_optional', false)
                ->select([
                    'ib.item_id as fg_item_id',
                    'mat.id as mat_id',
                    'mat.code as mat_code',
                    'mat.name as mat_name',
                    'mat.unit as mat_unit',
                    'ibl.qty as bom_qty',
                    'ibl.scrap_pct',
                ])
                ->get();

            $required = [];
            foreach ($bomLines as $bom) {
                $fgId = (int) $bom->fg_item_id;
                $wip  = (float) optional($wipByItem->get($fgId))->wip_sum;
                if ($wip <= 0) continue;
                $need  = $wip * (float) $bom->bom_qty * (1 + ((float) $bom->scrap_pct / 100));
                $matId = (int) $bom->mat_id;
                $required[$matId] ??= [
                    'id'           => $matId,
                    'code'         => $bom->mat_code,
                    'name'         => $bom->mat_name,
                    'unit'         => $bom->mat_unit ?: 'pcs',
                    'required_qty' => 0.0,
                ];
                $required[$matId]['required_qty'] += $need;
            }

            if (!empty($required)) {
                $rmStocks = DB::table('inventory_stocks')
                    ->where('warehouse_id', $rmWarehouseId)
                    ->whereIn('item_id', array_keys($required))
                    ->pluck('qty', 'item_id');

                $packingSupplies = collect($required)->map(function ($row) use ($rmStocks) {
                    $stock    = (float) ($rmStocks[$row['id']] ?? 0);
                    $shortage = max($row['required_qty'] - $stock, 0);
                    return (object) [
                        'id'           => $row['id'],
                        'code'         => $row['code'],
                        'name'         => $row['name'],
                        'unit'         => $row['unit'],
                        'required_qty' => round($row['required_qty'], 2),
                        'stock_qty'    => round($stock, 2),
                        'shortage_qty' => round($shortage, 2),
                        'has_shortage' => $shortage > 0.001,
                    ];
                })->sortByDesc('has_shortage')->values();
            }
        }

        return view('production.finishing_jobs.create', [
            'dateDefault'         => $today,
            'linesAll'            => $linesAll,
            'linesByOp'           => $linesByOp,
            'operators'           => $operators,
            'destinationWarehouses' => $destinationWarehouses,
            'defaultDestinationId'  => $defaultDestinationId,
            'packingSupplies'     => $packingSupplies,
        ]);
    }

    /* ============================================================
     * STORE (Draft)
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
            'destination_warehouse_id' => ['nullable', 'integer', 'exists:warehouses,id'],
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
            $linesKey . '.*.reject_cause' => ['nullable', 'in:finishing,sewing'],
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
                'warehouse' => 'Gudang WIP-FIN belum dikonfigurasi.',
            ]);
        }

        $destinationWarehouses = $this->finishingDestinationWarehouses();
        $defaultDestinationId = $this->defaultDestinationIdForUser($request->user(), $destinationWarehouses);
        $destinationWarehouseId = (int) ($validated['destination_warehouse_id'] ?? 0);

        if ($destinationWarehouseId <= 0) {
            $destinationWarehouseId = (int) ($defaultDestinationId ?? 0);
        }

        if ($destinationWarehouseId <= 0 || !$destinationWarehouses->contains('id', $destinationWarehouseId)) {
            throw ValidationException::withMessages([
                'destination_warehouse_id' => 'Tujuan gudang finishing hanya boleh WH-PRD atau WH-RTS.',
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

            $totalWipQuery = CuttingJobBundle::query()
                ->readyForFinishing($wipFinWarehouseId)
                ->where('finished_item_id', $itemId);
            $totalWip = (int) round((float) ($this->readinessFromLedger()
                ? $totalWipQuery->sum(DB::raw($this->wipFinLedgerSql('cutting_job_bundles')))
                : $totalWipQuery->sum('wip_qty')));

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
                'reject_cause' => $line['reject_cause'] ?? 'finishing',
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
            $destinationWarehouseId,
            &$empNameCache
        ) {
            $job = FinishingJob::create([
                'code' => CodeGenerator::generate('FIN'),
                'date' => $validated['date'],
                'status' => 'draft',
                'notes' => $validated['notes'] ?? null,
                'destination_warehouse_id' => $destinationWarehouseId,
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

                    $bundleWip = (int) round($this->readinessFromLedger()
                        ? $bundle->ledgerBalanceAt(config('inventory.warehouses.wip_fin', 'WIP-FIN'))
                        : (float) ($bundle->wip_qty ?? 0));
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

                    // lock reserve sewing_return_lines.finished_qty untuk qty_in
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
                        'reject_cause' => $takeReject > 0 ? ($wl['reject_cause'] ?? 'finishing') : 'finishing',
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

        try {
            $this->postCreatedJob($job);
        } catch (\Throwable $e) {
            return redirect()
                ->route('production.finishing_jobs.show', ['finishing_job' => $job->id])
                ->with('error', 'Finishing berhasil dibuat, tapi gagal auto-post: ' . $e->getMessage());
        }

        $destinationCode = Warehouse::whereKey($destinationWarehouseId)->value('code') ?: 'WH-PRD';

        return redirect()
            ->route('production.finishing_jobs.index')
            ->with('status', "Finishing {$job->code} berhasil diposting. OK ke {$destinationCode}, reject jahit ke REJ-SEW, reject finishing ke REJ-FIN.");
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

        $validated = $req->validate([
            'date' => ['required', 'date'],
            'notes' => ['nullable', 'string'],
            'lines' => ['required', 'array', 'min:1'],
            'lines.*.id' => ['required', 'integer', 'exists:finishing_job_lines,id'],
            'lines.*.qty_ok' => ['required', 'integer', 'min:0'],
            'lines.*.qty_reject' => ['required', 'integer', 'min:0'],
            'lines.*.reject_reason' => ['nullable', 'string', 'max:100'],
            'lines.*.reject_cause' => ['nullable', 'in:finishing,sewing'],
            'lines.*.reject_notes' => ['nullable', 'string'],
        ]);

        $finishingJob->loadMissing(['lines']);
        $lineById = $finishingJob->lines->keyBy('id');

        $hasSplVoided = \Schema::hasColumn('sewing_pickup_lines', 'voided_at');
        $hasSrVoided = \Schema::hasColumn('sewing_returns', 'voided_at');

        DB::transaction(function () use ($finishingJob, $validated, $lineById, $hasSplVoided, $hasSrVoided) {
            $finishingJob->update([
                'date' => $validated['date'],
                'notes' => $validated['notes'] ?? null,
                'updated_by' => auth()->id(),
            ]);

            foreach ($validated['lines'] as $i => $payload) {
                $lineId = (int) $payload['id'];

                /** @var \App\Models\FinishingJobLine|null $line */
                $line = $lineById->get($lineId);
                if (!$line) {
                    throw ValidationException::withMessages([
                        "lines.{$i}.id" => 'Line tidak ditemukan / bukan milik finishing job ini.',
                    ]);
                }

                $oldIn = (int) round((float) ($line->qty_in ?? 0));
                $qtyOk = max(0, (int) ($payload['qty_ok'] ?? 0));
                $qtyRj = max(0, (int) ($payload['qty_reject'] ?? 0));
                $newUsed = $qtyOk + $qtyRj;

                if ($newUsed <= 0) {
                    throw ValidationException::withMessages([
                        "lines.{$i}.qty_ok" => 'Minimal OK+Reject harus > 0 (kalau mau batal, lebih aman void/cancel flow khusus).',
                    ]);
                }

                if ($newUsed > $oldIn) {
                    throw ValidationException::withMessages([
                        "lines.{$i}.qty_ok" => "OK+Reject tidak boleh melebihi Qty IN lama ({$oldIn}).",
                    ]);
                }

                // ✅ kalau turun, release lock finished_qty di sewing_return_lines
                $release = $oldIn - $newUsed;
                if ($release > 0) {
                    $bundleId = (int) $line->bundle_id;
                    $itemId = (int) $line->item_id;
                    $opId = (int) $line->sewing_operator_id;

                    // ambil baris srl yang punya finished_qty > 0, lalu kurangi dari yang paling akhir (LIFO)
                    $q = DB::table('sewing_return_lines as srl')
                        ->join('sewing_returns as sr', 'sr.id', '=', 'srl.sewing_return_id')
                        ->join('sewing_pickup_lines as spl', 'spl.id', '=', 'srl.sewing_pickup_line_id')
                        ->where('spl.cutting_job_bundle_id', $bundleId)
                        ->where('spl.finished_item_id', $itemId)
                        ->where('sr.operator_id', $opId)
                        ->whereRaw('COALESCE(srl.finished_qty,0) > 0');

                    if ($hasSplVoided) {
                        $q->whereNull('spl.voided_at');
                    }

                    if ($hasSrVoided) {
                        $q->whereNull('sr.voided_at');
                    }

                    $rows = $q->select([
                        'srl.id',
                        DB::raw('COALESCE(srl.finished_qty,0) as finished_qty'),
                    ])
                        ->orderByDesc('srl.id')
                        ->lockForUpdate()
                        ->get();

                    $toRelease = $release;

                    foreach ($rows as $r) {
                        if ($toRelease <= 0) {
                            break;
                        }

                        $cur = (int) $r->finished_qty;
                        if ($cur <= 0) {
                            continue;
                        }

                        $dec = min($cur, $toRelease);

                        DB::table('sewing_return_lines')
                            ->where('id', $r->id)
                            ->update([
                                'finished_qty' => DB::raw('COALESCE(finished_qty,0) - ' . (int) $dec),
                                'updated_at' => now(),
                            ]);

                        $toRelease -= $dec;
                    }

                    if ($toRelease > 0) {
                        throw ValidationException::withMessages([
                            "lines.{$i}.qty_ok" => "Gagal release {$release}. Data berubah/lock tidak cukup. Refresh lalu coba lagi.",
                        ]);
                    }

                    // ✅ qty_in kita turunkan supaya konsisten (dan sisa dianggap kembali available WIP-FIN)
                    $line->qty_in = $newUsed;
                }

                if ($qtyRj > $line->qty_in) {
                    throw ValidationException::withMessages([
                        "lines.{$i}.qty_reject" => "Reject tidak boleh > Qty IN ({$line->qty_in}).",
                    ]);
                }

                $line->qty_ok = $qtyOk;
                $line->qty_reject = $qtyRj;
                $line->reject_reason = $payload['reject_reason'] ?? null;
                $line->reject_cause = $qtyRj > 0 ? ($payload['reject_cause'] ?? 'finishing') : 'finishing';
                $line->reject_notes = $payload['reject_notes'] ?? null;
                $line->save();
            }
        });

        return redirect()
            ->route('production.finishing_jobs.show', $finishingJob->id)
            ->with('status', 'Draft Finishing berhasil di-update. Sisa qty sudah direlease kembali ke WIP-FIN (available).');
    }

    /* ============================================================
     * POST
     * ============================================================
     */
    public function post(FinishingJob $finishing_job): RedirectResponse
    {
        $job = $finishing_job->loadMissing(['lines', 'lines.bundle.cuttingJob', 'lines.item']);

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

        try {
            $this->postCreatedJob($job);
        } catch (\RuntimeException $e) {
            return back()->withErrors(['warehouse' => $e->getMessage()]);
        }

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'Finishing Job berhasil diposting. OK ke WH-PRD, reject finishing ke REJ-FIN, reject jahit ke REJ-SEW.');
    }


    protected function postCreatedJob(FinishingJob $job): void
    {
        $job->loadMissing(['lines', 'lines.bundle.cuttingJob', 'lines.item']);

        if (($job->status ?? null) === 'posted') {
            return;
        }

        $requiredCodes = ['WIP-FIN', 'WH-PRD', 'REJ-FIN', 'REJ-SEW', 'RM'];
        // Jika ada destination_warehouse, load juga kodenya agar masuk koleksi
        $destCode = $job->destination_warehouse_id
            ? Warehouse::where('id', $job->destination_warehouse_id)->value('code')
            : null;
        if ($destCode && !in_array(strtoupper($destCode), $requiredCodes, true)) {
            $requiredCodes[] = strtoupper($destCode);
        }
        $warehouses = $this->getRequiredWarehouses($requiredCodes);

        $baseCodes = ['WIP-FIN', 'WH-PRD', 'REJ-FIN', 'REJ-SEW', 'RM'];
        $missing = array_diff($baseCodes, $warehouses->keys()->all());
        if (!empty($missing)) {
            throw new \RuntimeException('Warehouse berikut belum dikonfigurasi: ' . implode(', ', $missing) . '. Silakan setting dulu di Master Gudang.');
        }

        // Resolve destination: pakai destination_warehouse jika diset, fallback WH-PRD
        $prodWarehouse = ($destCode && $warehouses->has(strtoupper($destCode)))
            ? $warehouses->get(strtoupper($destCode))
            : $warehouses['WH-PRD'];

        $movementDate = $this->resolveMovementDate($job);

        DB::transaction(function () use ($job, $warehouses, $prodWarehouse, $movementDate) {
            $this->finBom->applySupOnlyForPostedJob($job, $movementDate);

            $this->applyPostingMovements(
                job: $job,
                wipFinWarehouseId: $warehouses['WIP-FIN']->id,
                prodWarehouseId: $prodWarehouse->id,
                rejectFinWarehouseId: $warehouses['REJ-FIN']->id,
                rejectSewWarehouseId: $warehouses['REJ-SEW']->id,
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

        // ✅ Jurnal akuntansi SETELAH inventory transaction commit
        // (query inventory_mutations di postFinishingJob butuh data yang sudah committed)
        try {
            $this->journal->postFinishingJob($job);
        } catch (\Throwable $e) {
            // Journal gagal tidak boleh rollback inventory — log saja
            \Log::warning('[FinishingJob] Jurnal tidak terbuat: ' . $e->getMessage(), [
                'finishing_job_id' => $job->id,
            ]);
        }

        try {
            $this->journal->postFinishingBom($job);
        } catch (\Throwable $e) {
            \Log::warning('[FinishingJob] Jurnal BOM finishing tidak terbuat: ' . $e->getMessage(), [
                'finishing_job_id' => $job->id,
            ]);
        }
    }

    /* ============================================================
     * HELPERS
     * ============================================================
     */
    protected function getRequiredWarehouses(array $codes): \Illuminate\Support\Collection
    {
        $norm = fn($v) => strtoupper(trim((string) $v));
        $want = array_values(array_unique(array_map($norm, $codes)));

        $rows = \App\Models\Warehouse::query()
            ->whereIn('code', $want)
            ->get();

        return $rows->keyBy(fn($w) => $norm($w->code));
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
     * NOTE:
     * - finished_qty sewing_return_lines SUDAH di-lock saat store().
     * - Jadi di sini TIDAK BOLEH allocate / update finished_qty lagi.
     */
    /**
     * Posting movements Finishing:
     * - OUT  : WIP-FIN (qty_ok + qty_reject)
     * - IN   : WH-PRD (qty_ok)
     * - IN   : REJ-FIN / REJ-SEW (qty_reject, sesuai reject_cause)
     *
     * Notes:
     * - source of truth = qty_ok + qty_reject
     * - qty_in hanya sebagai optional consistency check (kalau dipakai di UI)
     */
    protected function applyPostingMovements(
        FinishingJob $job,
        int $wipFinWarehouseId,
        int $prodWarehouseId,
        int $rejectFinWarehouseId,
        int $rejectSewWarehouseId,
        \DateTimeInterface $movementDate,
        string $notesPrefix = 'Finishing',
        bool $enforceQtyInMatch = false// ✅ sementara optional
    ): void {
        $job->loadMissing(['lines.bundle', 'lines.item']);

        // helper: normalize jadi int aman
        $toInt = static function ($v): int {
            if ($v === null || $v === '') {
                return 0;
            }

            return (int) round((float) $v);
        };

        DB::transaction(function () use (
            $job,
            $wipFinWarehouseId,
            $prodWarehouseId,
            $rejectFinWarehouseId,
            $rejectSewWarehouseId,
            $movementDate,
            $notesPrefix,
            $enforceQtyInMatch,
            $toInt
        ) {
            foreach ($job->lines as $line) {
                // =========================
                // 0) Normalize qty
                // =========================
                $qtyOk = $toInt($line->qty_ok ?? 0);
                $qtyReject = $toInt($line->qty_reject ?? 0);
                $qtyIn = $toInt($line->qty_in ?? 0);

                $qtyUsed = $qtyOk + $qtyReject;

                // kalau kosong ya skip
                if ($qtyUsed <= 0) {
                    // tapi kalau qty_in diisi (di UI) -> warning keras biar kebaca
                    if ($qtyIn > 0) {
                        throw new \RuntimeException(
                            "Finishing line {$line->id} invalid: qty_in={$qtyIn} tapi qty_ok+qty_reject=0. "
                            . "Isi qty_ok/qty_reject, atau kosongkan qty_in."
                        );
                    }
                    continue;
                }

                // optional consistency check: qty_in harus sama qtyUsed
                if ($enforceQtyInMatch && $qtyIn > 0 && $qtyIn !== $qtyUsed) {
                    throw new \RuntimeException(
                        "Finishing line {$line->id} inconsistent: qty_in={$qtyIn} != qty_ok+qty_reject={$qtyUsed}."
                    );
                }

                // =========================
                // 1) Ambil unit cost dari incoming WIP-FIN (moving average / last incoming)
                // =========================
                $unitCostWipFin = (float) $this->inventory->getItemIncomingUnitCost(
                    $wipFinWarehouseId,
                    (int) $line->item_id
                );

                $movementUnitCost = ($unitCostWipFin > 0) ? $unitCostWipFin : null;

                // =========================
                // 2) Guard stok WIP-FIN cukup (pakai lock biar aman saat concurrent)
                // =========================
                $currentStock = (float) DB::table('inventory_stocks')
                    ->where('warehouse_id', $wipFinWarehouseId)
                    ->where('item_id', (int) $line->item_id)
                    ->lockForUpdate()
                    ->value('qty');

                $eps = 0.0000001;
                if (($currentStock + $eps) < $qtyUsed) {
                    throw new \RuntimeException(
                        "WIP-FIN stock tidak cukup untuk item {$line->item_id}. "
                        . "Stok: {$currentStock}, mau keluar: {$qtyUsed}. "
                        . "Cek sumber WIP-FIN (sewing_return_ok / transfer / adjustment)."
                    );
                }

                // FASE 1: tag dimensi produksi (per bundle) di ledger
                $lineBundleId = $line->bundle_id ? (int) $line->bundle_id : null;

                // =========================
                // 3) OUT WIP-FIN (OK+Reject)
                // =========================
                // ✅ hindari named-arg mismatch -> pakai positional args
                // Pastikan signature InventoryService::stockOut sesuai urutan ini:
                // stockOut($warehouseId, $itemId, $qty, $date, $sourceType, $sourceId, $notes, $allowNegative=false, $lotId=null, $unitCostOverride=null, $affectLotCost=false, $cuttingJobBundleId=null)
                $this->inventory->stockOut(
                    $wipFinWarehouseId,
                    (int) $line->item_id,
                    (int) $qtyUsed,
                    $movementDate,
                    FinishingJob::class,
                    $job->id,
                    $notesPrefix . " OUT (WIP-FIN)",
                    false,
                    null,
                    $movementUnitCost,
                    false,
                    $lineBundleId
                );

                // =========================
                // 4) IN WH-PRD (OK)
                // =========================
                if ($qtyOk > 0) {
                    $supUnitCost = (float) $this->getSupUnitCostFromFinishingBom(
                        (int) $line->id,
                        (float) $qtyOk
                    );
                    $finishedUnitCost = $movementUnitCost !== null
                        ? $movementUnitCost + $supUnitCost
                        : ($supUnitCost > 0 ? $supUnitCost : null);

                    // stockIn($warehouseId, $itemId, $qty, $date, $sourceType, $sourceId, $notes, $lotId=null, $unitCost=null, $affectLotCost=false)
                    $this->inventory->stockIn(
                        $prodWarehouseId,
                        (int) $line->item_id,
                        (int) $qtyOk,
                        $movementDate,
                        FinishingJob::class,
                        $job->id,
                        $notesPrefix . " OK (WIP-FIN→PRD)",
                        null,
                        $finishedUnitCost,
                        false,
                        $lineBundleId
                    );

                    // snapshot HPP (kalau cost ada)
                    if ($finishedUnitCost !== null && $finishedUnitCost > 0) {
                        $this->hpp->createSnapshot([
                            'item_id' => (int) $line->item_id,
                            'warehouse_id' => null,
                            'snapshot_date' => $movementDate->format('Y-m-d'),
                            'reference_type' => 'auto_hpp_rm_only_finishing',
                            'reference_id' => $job->id,
                            'qty_basis' => (int) $qtyOk,

                            'rm_unit_cost' => (float) $movementUnitCost,
                            'cutting_unit_cost' => 0,
                            'sewing_unit_cost' => 0,
                            'finishing_unit_cost' => (float) $supUnitCost,
                            'packaging_unit_cost' => 0,
                            'overhead_unit_cost' => 0,

                            'notes' => 'Auto HPP: RM(WIP-FIN) + SUP(BOM) dari ' . $notesPrefix,
                            'is_active' => false,
                        ]);
                    }
                }

                // =========================
                // 5) IN REJECT sesuai sumber masalah
                // =========================
                if ($qtyReject > 0) {
                    $rejectCause = (string) ($line->reject_cause ?? 'finishing');
                    $rejectWarehouseId = $rejectCause === 'sewing'
                        ? $rejectSewWarehouseId
                        : $rejectFinWarehouseId;
                    $rejectCode = $rejectCause === 'sewing' ? 'REJ-SEW' : 'REJ-FIN';

                    $this->inventory->stockIn(
                        $rejectWarehouseId,
                        (int) $line->item_id,
                        (int) $qtyReject,
                        $movementDate,
                        FinishingJob::class,
                        $job->id,
                        $notesPrefix . " REJECT {$rejectCode} (WIP-FIN→{$rejectCode})",
                        null,
                        $movementUnitCost,
                        false,
                        $lineBundleId
                    );
                }

                // =========================
                // 6) Reduce bundle->wip_qty (OK+Reject)
                // =========================
                if ($line->bundle) {
                    $bundle = $line->bundle;

                    $currentWip = $toInt($bundle->wip_qty ?? 0);
                    $newWip = $currentWip - $qtyUsed;

                    if ($newWip < 0) {
                        if ($this->readinessFromLedger()) {
                            // Mode ledger: penjaga fisik sudah di ledger (lock stok WIP-FIN
                            // + stockOut allowNegative=false). Cache wip_qty hanya cermin —
                            // jangan blokir operasi yang sah, cukup floor ke 0.
                            // TAPI catat drift-nya supaya bisa diselidiki & jadi tolok ukur
                            // kesiapan Fase 4 (kalau log ini nol = cache aman dibuang).
                            \Log::warning('[FASE3b] cache wip_qty drift saat finishing', [
                                'bundle_id' => $bundle->id,
                                'finishing_line_id' => $line->id,
                                'job_id' => $job->id,
                                'item_id' => (int) $line->item_id,
                                'cache_wip_qty' => $currentWip,
                                'qty_used' => $qtyUsed,
                                'shortfall' => $qtyUsed - $currentWip,
                            ]);
                            $newWip = 0;
                        } else {
                            // ✅ keras biar kelihatan mismatch data bundle vs movement
                            throw new \RuntimeException(
                                "Bundle {$bundle->id} wip_qty tidak cukup. "
                                . "wip_qty={$currentWip}, dipakai={$qtyUsed} (line {$line->id})."
                            );
                        }
                    }

                    if ($newWip !== $currentWip) {
                        $bundle->wip_qty = $newWip;
                        $bundle->save();
                    }
                }
            }
        });
    }

    protected function getSupUnitCostFromFinishingBom(int $finishingLineId, float $qtyOk): float
    {
        if ($qtyOk <= 0) {
            return 0.0;
        }

        $totalSupCost = (float) DB::table('inventory_mutations')
            ->where('source_type', 'finishing_bom')
            ->where('source_id', $finishingLineId)
            ->sum('total_cost');

        // total_cost stockOut biasanya NEGATIF, jadi dibuat positif
        $totalSupCost = abs($totalSupCost);

        return round($totalSupCost / $qtyOk, 4);
    }

    /**
     * Apply ulang BOM untuk baris yang bom_has_gaps=true (setelah GRN diinput).
     * Hanya reset baris yang belum lengkap — menghindari double stockOut.
     */
    public function reapplyBom(FinishingJob $finishingJob): RedirectResponse
    {
        $job = $finishingJob->loadMissing(['lines', 'lines.bundle.cuttingJob', 'lines.item']);

        if (($job->status ?? null) !== 'posted') {
            return back()->withErrors(['bom' => 'Hanya finishing job yang sudah diposting yang bisa di-apply ulang BOM-nya.']);
        }

        $gapLines = $job->lines->filter(fn($l) => (bool) ($l->bom_has_gaps ?? false));

        if ($gapLines->isEmpty()) {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('status', 'Tidak ada BOM gap yang perlu di-apply ulang.');
        }

        // Reset hanya baris yang gap — bom_applied_at=null agar service mau proses ulang
        foreach ($gapLines as $line) {
            $line->bom_applied_at = null;
            $line->bom_has_gaps   = false;
            $line->save();
        }

        $movementDate = $this->resolveMovementDate($job);

        try {
            DB::transaction(function () use ($job, $movementDate) {
                $this->finBom->applySupOnlyForPostedJob($job, $movementDate);
            });
        } catch (\Throwable $e) {
            return back()->withErrors(['bom' => 'Gagal apply BOM: ' . $e->getMessage()]);
        }

        $job->refresh()->loadMissing(['lines']);
        $stillGap = $job->lines->filter(fn($l) => (bool) ($l->bom_has_gaps ?? false))->count();

        if ($stillGap > 0) {
            return redirect()
                ->route('production.finishing_jobs.show', $job->id)
                ->with('warning', "BOM di-apply ulang, tapi masih ada {$stillGap} baris yang belum lengkap. Pastikan GRN sudah diinput untuk semua material.");
        }

        return redirect()
            ->route('production.finishing_jobs.show', $job->id)
            ->with('status', 'BOM berhasil di-apply ulang. Semua material sudah ter-cover dan dicatat ke stok.');
    }
}
