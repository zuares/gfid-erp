<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Models\Warehouse;
use App\Services\Payroll\PieceRateService;
use App\Services\Production\ProductionFlowService;
use App\Services\Production\ProductionPriorityService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;
use Illuminate\View\View;

/**
 * Dashboard Produksi (redesign — berbasis pertanyaan keputusan harian).
 *
 * Tab:
 *  - Ringkasan   : KPI keputusan harian + funnel alur + tren harian.
 *  - Siap Jahit  : stok siap jahit per bundle + prioritas cutting/bagi.
 *  - Sedang Jahit: WIP jahit yang sedang dikerjakan penjahit (outstanding + umur).
 *  - Setor & QC  : setoran jahit harian (OK/reject/yield).
 *  - Penjahit    : performa & skor operator jahit.
 *  - Prioritas   : skor prioritas 0–100 per SKU (cutting/bagi mana dulu).
 */
class ProductionDashboardController extends Controller
{
    /** Daftar tab valid + builder-nya. */
    private const TABS = ['ringkasan', 'siap-jahit', 'sedang-jahit', 'setor-qc', 'reject', 'prioritas'];

    public function __construct(
        private ProductionFlowService $flow,
        private ProductionPriorityService $priority,
        private PieceRateService $pieceRate,
    ) {
    }

    /**
     * Shell dashboard: render kerangka + HANYA tab awal (lazy).
     * Tab lain di-fetch via API saat dibuka (lihat data()).
     */
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);

        $initialTab = $request->input('tab');
        if (!in_array($initialTab, self::TABS, true)) {
            $initialTab = 'ringkasan';
        }

        // Default 7 hari (untuk tombol reset di front-end)
        $today = Carbon::today();
        $defaults = [
            'date_from' => $today->copy()->subDays(6)->toDateString(),
            'date_to' => $today->toDateString(),
        ];

        return view('production.dashboard.index', array_merge(
            [
                'filters' => $filters,
                'defaults' => $defaults,
                'initialTab' => $initialTab,
                'initialPartial' => $this->partialFor($initialTab),
                'periodLabel' => $this->periodLabel($filters),
                'operatorOptions' => Employee::where('role', 'sewing')->orderBy('code')->get(),
                'itemOptions' => Item::where('type', 'finished_good')->orderBy('code')->get(),
                'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
            ],
            $this->tabData($initialTab, $filters),
        ));
    }

    /**
     * API: kembalikan HTML satu tab + meta (untuk AJAX lazy-load & filter).
     */
    public function data(Request $request): \Illuminate\Http\JsonResponse
    {
        $tab = $request->input('tab');
        if (!in_array($tab, self::TABS, true)) {
            return response()->json(['message' => 'Tab tidak dikenal.'], 422);
        }

        $filters = $this->resolveFilters($request);
        $html = view($this->partialFor($tab), $this->tabData($tab, $filters))->render();

        return response()->json([
            'tab' => $tab,
            'html' => $html,
            'meta' => [
                'date_from' => $filters['date_from'],
                'date_to' => $filters['date_to'],
                'operator_id' => $filters['operator_id'],
                'item_id' => $filters['item_id'],
                'category_id' => $filters['category_id'],
                'period_label' => $this->periodLabel($filters),
            ],
        ]);
    }

    /**
     * Slip upah borongan per operator (siap cetak).
     * Sumber upah = nilai REAL yang dibayar:
     *   - sewing  → setoran jahit lolos QC (qty_ok × rate)
     *   - cutting → hasil potong lolos QC (qty_qc_ok × rate)
     * Dirinci per kategori → per SKU, dengan subtotal & grand total.
     */
    public function slip(Request $request): View
    {
        $module = $request->query('module') === 'cutting' ? 'cutting' : 'sewing';
        $code = trim((string) $request->query('operator', ''));

        $employee = $code !== '' ? Employee::where('code', $code)->first() : null;
        abort_if(!$employee, 404, 'Operator tidak ditemukan.');

        $f = $this->resolveFilters($request);
        $f['operator_id'] = $employee->id;

        // Pisahkan upah RIIL (hasil disetor & lolos QC) dari ESTIMASI (masih diambil, belum disetor).
        $activity = $module === 'cutting'
            ? $this->buildCuttingActivity($f)
            : $this->buildPenjahitActivity($f);

        $rows = $module === 'cutting'
            ? $activity
            : $activity->where('type', 'Setor')->values();

        // Item yang masih dipegang penjahit (belum disetor) → dasar estimasi. Hanya modul jahit.
        $ambil = $module === 'cutting'
            ? collect()
            : $activity->where('type', 'Ambil')->filter(fn($r) => $r->qty_outstanding > 0)->values();

        // Closure grouping kategori → SKU. $qtyKey & $amountFn menentukan basis riil vs estimasi.
        $groupBy = function ($items, callable $qtyOf, callable $amountOf) {
            return $items->groupBy('category')
                ->map(function ($g, $cat) use ($qtyOf, $amountOf) {
                    $lines = $g->groupBy('sku')
                        ->map(function ($s) use ($qtyOf, $amountOf) {
                            $qty = (float) $s->sum($qtyOf);
                            $amount = (float) $s->sum($amountOf);
                            $first = $s->first();
                            return (object) [
                                'sku' => $first->sku,
                                'product_name' => $first->product_name,
                                'qty' => $qty,
                                'rate' => $qty > 0 ? $amount / $qty : (float) $first->rate,
                                'amount' => $amount,
                            ];
                        })
                        ->sortBy('sku')
                        ->values();

                    return (object) [
                        'category' => $cat,
                        'lines' => $lines,
                        'qty' => (float) $g->sum($qtyOf),
                        'amount' => (float) $g->sum($amountOf),
                    ];
                })
                ->sortBy('category')
                ->values();
        };

        // Riil: qty = qty_ok, upah = amount (sudah final).
        $groups = $groupBy($rows, fn($r) => $r->qty_ok, fn($r) => $r->amount);

        // Estimasi: qty = sisa belum disetor, upah = tarif × sisa (BELUM final).
        $estGroups = $groupBy($ambil, fn($r) => $r->qty_outstanding, fn($r) => $r->rate * $r->qty_outstanding);
        $estQty = (float) $ambil->sum('qty_outstanding');
        $estAmount = (float) $ambil->sum(fn($r) => $r->rate * $r->qty_outstanding);

        // Recap per kategori (gabungan riil + estimasi) — tampil ringkas di hero slip.
        $recap = $groups->concat($estGroups)
            ->groupBy('category')
            ->map(fn($g, $cat) => (object) [
                'category' => $cat,
                'qty' => (float) $g->sum('qty'),
                'amount' => (float) $g->sum('amount'),
            ])
            ->sortByDesc('amount')
            ->values();

        $role = $module === 'cutting' ? 'Pemotong' : 'Penjahit';

        // Rentang tanggal ambil (dari pickup) — hanya relevan utk penjahit.
        $pickupDates = $activity->pluck('pickup_date')->filter()->unique()->sort()->values();
        $pickupFrom = $pickupDates->first();
        $pickupTo = $pickupDates->last();

        // Nama file unduhan: NAMAOPERATOR_ROLE_RANGETANGGAL
        $clean = fn(string $s) => preg_replace('/[^A-Za-z0-9]+/', '', $s);
        $range = Carbon::parse($f['date_from'])->format('Ymd') . '-' . Carbon::parse($f['date_to'])->format('Ymd');
        $fileName = trim($clean($employee->name) . '_' . $role . '_' . $range, '_');

        return view('production.dashboard.slip', [
            'module' => $module,
            'moduleLabel' => $module === 'cutting' ? 'Cutting (Potong)' : 'Jahit (Setor)',
            'role' => $role,
            'employee' => $employee,
            'period' => $this->periodLabel($f),
            'dateFrom' => $f['date_from'],
            'dateTo' => $f['date_to'],
            'pickupFrom' => $pickupFrom,
            'pickupTo' => $pickupTo,
            'groups' => $groups,
            'grandQty' => (float) $rows->sum('qty_ok'),
            'grandAmount' => (float) $rows->sum('amount'),
            'estGroups' => $estGroups,
            'estQty' => $estQty,
            'estAmount' => $estAmount,
            'recap' => $recap,
            'printedAt' => Carbon::now(),
            'fileName' => $fileName,
        ]);
    }

    /** Parse & normalisasi filter (default: 7 hari terakhir). */
    private function resolveFilters(Request $request): array
    {
        $today = Carbon::today();
        $dateFrom = $request->input('date_from');
        $dateTo = $request->input('date_to');

        if (!$dateFrom && !$dateTo) {
            $dateTo = $today->toDateString();
            $dateFrom = $today->copy()->subDays(6)->toDateString();
        } elseif ($dateFrom && !$dateTo) {
            $dateTo = $dateFrom;
        } elseif (!$dateFrom && $dateTo) {
            $dateFrom = $dateTo;
        }
        if ($dateFrom > $dateTo) {
            [$dateFrom, $dateTo] = [$dateTo, $dateFrom];
        }

        return [
            'date_from' => $dateFrom,
            'date_to' => $dateTo,
            'operator_id' => $request->input('operator_id') ?: null,
            'item_id' => $request->input('item_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
        ];
    }

    /** Label periode untuk header. */
    private function periodLabel(array $f): string
    {
        return Carbon::parse($f['date_from'])->format('d M Y')
            . ' – ' . Carbon::parse($f['date_to'])->format('d M Y');
    }

    /** Nama blade partial untuk sebuah tab. */
    private function partialFor(string $tab): string
    {
        return 'production.dashboard.partials._' . $tab;
    }

    /** Hitung HANYA data yang diperlukan tab terkait. */
    private function tabData(string $tab, array $f): array
    {
        return match ($tab) {
            'siap-jahit' => $this->buildSiapJahit($f),
            'sedang-jahit' => $this->buildSedangJahit($f),
            'setor-qc' => $this->buildSetorQc($f),
            'reject' => $this->buildReject($f),
            'prioritas' => ['priority' => $this->priority->priorityList($f, 100)],
            default => [
                'summary' => $this->buildSummary($f),
                'timeline' => $this->buildActivityTimeline($f),
            ],
        };
    }

    /**
     * Helper: kecualikan dokumen void (auto-detect kolom, SQLite-safe).
     */
    private function applyNotVoid($q, string $table, ?string $alias = null): void
    {
        $prefix = $alias ?: $table;

        // Canonical void marker in this app = timestamp column (voided_at).
        foreach (['voided_at', 'canceled_at', 'cancelled_at'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $q->whereNull("$prefix.$col");
                return;
            }
        }
        foreach (['is_void', 'is_voided', 'void', 'voided', 'is_canceled', 'is_cancelled'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $q->where("$prefix.$col", 0);
                return;
            }
        }
        foreach (['status', 'state'] as $col) {
            if (Schema::hasColumn($table, $col)) {
                $q->whereNotIn("$prefix.$col", ['void', 'VOID', 'canceled', 'CANCELED', 'cancelled', 'CANCELLED']);
                return;
            }
        }
    }

    // ==========================================================
    // 1) RINGKASAN (KPI)
    // ==========================================================
    private function buildSummary(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // Cutting OK / Reject (QC cutting)
        $cutQ = DB::table('qc_results as qc')
            ->where('qc.stage', 'cutting')
            ->whereRaw('DATE(qc.qc_date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['item_id']) {
            $cutQ->join('cutting_job_bundles as b', 'b.id', '=', 'qc.cutting_job_bundle_id')
                ->where('b.finished_item_id', $f['item_id']);
        }
        $cutting = $cutQ->selectRaw('COALESCE(SUM(qc.qty_ok),0) ok, COALESCE(SUM(qc.qty_reject),0) rj')->first();

        // Pickup total (qty_bundle)
        $pickQ = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereRaw('DATE(p.date) BETWEEN ? AND ?', [$from, $to]);
        $this->applyNotVoid($pickQ, 'sewing_pickups', 'p');
        if ($f['operator_id']) {
            $pickQ->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pickQ->where('pl.finished_item_id', $f['item_id']);
        }
        $pickupTotal = (float) $pickQ->sum('pl.qty_bundle');

        // Sewing OK / Reject (return lines)
        $sewQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to]);
        $this->applyNotVoid($sewQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $sewQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sewQ->join('sewing_pickup_lines as pl2', 'pl2.id', '=', 'rl.sewing_pickup_line_id')
                ->where('pl2.finished_item_id', $f['item_id']);
        }
        $sewing = $sewQ->selectRaw('COALESCE(SUM(rl.qty_ok),0) ok, COALESCE(SUM(rl.qty_reject),0) rj')->first();

        // Finishing OK / Reject (posted)
        $finQ = DB::table('finishing_job_lines as fl')
            ->join('finishing_jobs as fj', 'fj.id', '=', 'fl.finishing_job_id')
            ->where('fj.status', 'posted')
            ->whereRaw('DATE(fj.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $finQ->where('fl.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $finQ->where('fl.item_id', $f['item_id']);
        }
        $fin = $finQ->selectRaw('COALESCE(SUM(fl.qty_in),0) in_qty, COALESCE(SUM(fl.qty_ok),0) ok, COALESCE(SUM(fl.qty_reject),0) rj')->first();

        $cutOk = (float) $cutting->ok;
        $cutRj = (float) $cutting->rj;
        $sewOk = (float) $sewing->ok;
        $sewRj = (float) $sewing->rj;
        $finOk = (float) $fin->ok;
        $finRj = (float) $fin->rj;
        $totalRj = $cutRj + $sewRj + $finRj;
        $totalOk = $cutOk + $sewOk + $finOk;

        return [
            'cutting_ok' => $cutOk,
            'cutting_reject' => $cutRj,
            'pickup_total' => $pickupTotal,
            'sewing_ok' => $sewOk,
            'sewing_reject' => $sewRj,
            'finishing_in' => (float) $fin->in_qty,
            'finishing_ok' => $finOk,
            'finishing_reject' => $finRj,
            'total_ok' => $totalOk,
            'total_reject' => $totalRj,
            'reject_rate' => ($totalOk + $totalRj) > 0 ? round($totalRj / ($totalOk + $totalRj) * 100, 1) : 0,
            'sewing_yield' => ($sewOk + $sewRj) > 0 ? round($sewOk / ($sewOk + $sewRj) * 100, 1) : 0,
        ];
    }

    // ==========================================================
    // 1c) TIMELINE AKTIVITAS — kronologis "siapa ngerjain apa"
    // ==========================================================
    /**
     * Feed kronologis kejadian produksi pada periode terpilih (terbaru dulu):
     *  - cutting : cutting job selesai QC (beres cutting) — per dokumen, pemotong.
     *  - pickup  : penjahit MENGAMBIL bundle untuk dijahit (ambil jahit).
     *  - return  : penjahit MENYETOR hasil jahit (setor jahit) — OK + reject.
     *
     * Tiap event: type, code, date, operator, qty ringkas, jumlah SKU.
     */
    private function buildActivityTimeline(array $f): \Illuminate\Support\Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // ---- Beres Cutting (cutting_jobs status qc_done) ----
        $cut = DB::table('cutting_jobs as cj')
            ->leftJoin('employees as e', 'e.id', '=', 'cj.operator_id')
            ->leftJoin('cutting_job_bundles as cb', 'cb.cutting_job_id', '=', 'cj.id')
            ->where('cj.status', 'qc_done')
            ->whereRaw('DATE(cj.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['item_id']) {
            $cut->where('cb.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $cut->where('cb.item_category_id', $f['category_id']);
        }
        $cut = $cut->groupBy('cj.id', 'cj.code', 'cj.date', 'cj.created_at', 'e.code', 'e.name')
            ->selectRaw("
                'cutting' as type, cj.id as ref_id, cj.code as code,
                DATE(cj.date) as date, cj.created_at as created_at,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                COALESCE(SUM(cb.qty_qc_ok),0) as qty_ok,
                COALESCE(SUM(cb.qty_qc_reject),0) as qty_reject,
                COALESCE(SUM(cb.qty_pcs),0) as qty_total,
                COUNT(DISTINCT cb.finished_item_id) as sku_count
            ")
            ->get();

        // ---- Ambil Jahit (sewing_pickups) ----
        $pick = DB::table('sewing_pickups as p')
            ->leftJoin('employees as e', 'e.id', '=', 'p.operator_id')
            ->leftJoin('sewing_pickup_lines as pl', 'pl.sewing_pickup_id', '=', 'p.id')
            ->leftJoin('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->whereNull('p.voided_at')
            // Sertakan pickup 'draft' (baru ambil, belum disetor) — selaras tab Penjahit & KPI.
            // Hanya kecualikan yang benar-benar void.
            ->where('p.status', '!=', 'void')
            ->whereRaw('DATE(p.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $pick->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pick->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $pick->where('it.item_category_id', $f['category_id']);
        }
        $pick = $pick->groupBy('p.id', 'p.code', 'p.date', 'p.created_at', 'e.code', 'e.name')
            ->selectRaw("
                'pickup' as type, p.id as ref_id, p.code as code,
                DATE(p.date) as date, p.created_at as created_at,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                0 as qty_ok, 0 as qty_reject,
                COALESCE(SUM(pl.qty_bundle),0) as qty_total,
                COUNT(DISTINCT pl.finished_item_id) as sku_count
            ")
            ->get();

        // ---- Setor Jahit (sewing_returns) ----
        $ret = DB::table('sewing_returns as r')
            ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
            ->leftJoin('sewing_return_lines as rl', 'rl.sewing_return_id', '=', 'r.id')
            ->leftJoin('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->leftJoin('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->whereNull('r.voided_at')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $ret->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $ret->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $ret->where('it.item_category_id', $f['category_id']);
        }
        $ret = $ret->groupBy('r.id', 'r.code', 'r.date', 'r.created_at', 'e.code', 'e.name')
            ->selectRaw("
                'return' as type, r.id as ref_id, r.code as code,
                DATE(r.date) as date, r.created_at as created_at,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                COALESCE(SUM(rl.qty_ok),0) as qty_ok,
                COALESCE(SUM(rl.qty_reject),0) as qty_reject,
                COALESCE(SUM(rl.qty_ok),0) + COALESCE(SUM(rl.qty_reject),0) as qty_total,
                COUNT(DISTINCT pl.finished_item_id) as sku_count
            ")
            ->get();

        return $cut->concat($pick)->concat($ret)
            ->map(fn($r) => (object) [
                'type' => $r->type,
                'code' => $r->code,
                'date' => $r->date,
                'created_at' => $r->created_at,
                'operator_code' => $r->operator_code,
                'operator_name' => $r->operator_name,
                'qty_ok' => (float) $r->qty_ok,
                'qty_reject' => (float) $r->qty_reject,
                'qty_total' => (float) $r->qty_total,
                'sku_count' => (int) $r->sku_count,
            ])
            ->sortBy([['date', 'desc'], ['created_at', 'desc']])
            ->values();
    }

    // ==========================================================
    // B) SIAP JAHIT — stok siap jahit per bundle + prioritas (cutting/bagi)
    // ==========================================================
    /**
     * Tab "Siap Jahit" menjawab: barang apa siap dijahit, sudah dibagi berapa,
     * sisa berapa, dan SKU mana yang harus diprioritaskan (di-cutting / dibagikan
     * ke penjahit lebih dulu).
     *
     *  - priority : skor 0–100 per SKU (ProductionPriorityService) → rekomendasi cutting/bagi.
     *  - bundles  : detail bundle yang masih punya sisa siap jahit
     *               (qty_qc_ok − sewing_picked_qty > 0).
     */
    private function buildSiapJahit(array $f): array
    {
        // ---- Prioritas (section G): cutting/bagi mana dulu ----
        $priority = $this->priority->priorityList($f, 100);
        $gradeBySku = $priority->mapWithKeys(fn($p) => [$p->sku => $p])->all();

        // ---- Sumber data SAMA dengan halaman buat Sewing Pickup ----
        // (CuttingJobBundle::readyForSewing + qty_ready_for_sewing) lalu di-GROUP BY SKU.
        $wipCutId = Warehouse::where('code', 'WIP-CUT')->value('id');

        $q = CuttingJobBundle::query()
            ->with(['finishedItem.category', 'cuttingJob', 'latestCuttingQc'])
            ->withLedgerBalances(['WIP-CUT'])
            ->readyForSewing($wipCutId);
        if ($f['item_id']) {
            $q->where('finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $q->whereHas('finishedItem', fn($qq) => $qq->where('item_category_id', $f['category_id']));
        }

        $today = Carbon::today();
        $costCache = []; // item_id => HPP satuan (cache agar tidak query berulang)

        // Hitung kesiapan per bundle (pakai accessor yang sama dgn halaman pickup).
        $mapped = $q->get()
            ->map(function (CuttingJobBundle $b) use ($today, &$costCache) {
                $cutDate = $b->cuttingJob?->date;
                $itemId = $b->finished_item_id;
                if (!array_key_exists($itemId, $costCache)) {
                    $costCache[$itemId] = (float) ($b->finishedItem?->effective_unit_cost ?? 0);
                }
                return (object) [
                    'item_id' => $itemId,
                    'sku' => $b->finishedItem?->code ?? '-',
                    'product_name' => $b->finishedItem?->name ?? '-',
                    'category' => $b->finishedItem?->category?->name ?? '-',
                    'ads' => (float) ($b->finishedItem?->avg_daily_sales ?? 0),
                    'qty_ready' => (float) $b->qty_ready_for_sewing,
                    'hpp_unit' => $costCache[$itemId],
                    'cut_date' => $cutDate,
                    'age_days' => $cutDate ? Carbon::parse($cutDate)->startOfDay()->diffInDays($today) : null,
                ];
            })
            ->filter(fn($r) => $r->qty_ready > 0.0001);

        // ---- Stok barang jadi siap jual (per item, per gudang) ----
        // Sumber: tabel inventory_stocks (snapshot on-hand) untuk WH-PRD & WH-RTS.
        $itemIds = $mapped->pluck('item_id')->unique()->values()->all();
        $whIds = Warehouse::whereIn('code', ['WH-PRD', 'WH-RTS'])->pluck('id', 'code');
        $prdWhId = $whIds['WH-PRD'] ?? null;
        $rtsWhId = $whIds['WH-RTS'] ?? null;
        $stockByItem = []; // item_id => ['prd'=>x,'rts'=>y]
        if (!empty($itemIds) && ($prdWhId || $rtsWhId)) {
            \App\Models\InventoryStock::whereIn('item_id', $itemIds)
                ->whereIn('warehouse_id', array_filter([$prdWhId, $rtsWhId]))
                ->selectRaw('item_id, warehouse_id, SUM(qty) as qty')
                ->groupBy('item_id', 'warehouse_id')
                ->get()
                ->each(function ($r) use (&$stockByItem, $prdWhId, $rtsWhId) {
                    $key = (int) $r->warehouse_id === (int) $prdWhId ? 'prd' : 'rts';
                    $stockByItem[$r->item_id]['prd'] ??= 0.0;
                    $stockByItem[$r->item_id]['rts'] ??= 0.0;
                    $stockByItem[$r->item_id][$key] += (float) $r->qty;
                });
        }

        // ---- Kelompokkan per SKU ----
        $grouped = $mapped
            ->groupBy('sku')
            ->map(function ($rows, $sku) use ($gradeBySku, $stockByItem) {
                $p = $gradeBySku[$sku] ?? null;
                $grade = $p->grade ?? null;
                $ages = $rows->pluck('age_days')->filter(fn($a) => $a !== null);
                $maxAge = $ages->isNotEmpty() ? (int) $ages->max() : null;
                $oldestCut = $rows->pluck('cut_date')->filter()->min();
                $qtyReady = (float) $rows->sum('qty_ready');
                $hppUnit = (float) $rows->first()->hpp_unit;
                $itemId = $rows->first()->item_id;

                $stokPrd = (float) ($stockByItem[$itemId]['prd'] ?? 0);
                $stokRts = (float) ($stockByItem[$itemId]['rts'] ?? 0);
                $stokJadi = $stokPrd + $stokRts;
                $ads = (float) ($p->ads ?? $rows->first()->ads ?? 0);

                if ($grade === 'Kritis' || $grade === 'Tinggi') {
                    $action = 'Segera bagikan';
                } elseif ($maxAge !== null && $maxAge >= 14) {
                    $action = 'Lama menunggu';
                } else {
                    $action = 'Normal';
                }

                return (object) [
                    'sku' => $sku,
                    'product_name' => $rows->first()->product_name,
                    'category' => $rows->first()->category,
                    'qty_ready' => $qtyReady,
                    'bundle_count' => $rows->count(),
                    'hpp_unit' => $hppUnit,
                    'hpp_total' => $qtyReady * $hppUnit,
                    'stok_prd' => $stokPrd,
                    'stok_rts' => $stokRts,
                    'stok_jadi' => $stokJadi,
                    'ads' => $ads,
                    'oldest_cut_date' => $oldestCut,
                    'max_age_days' => $maxAge,
                    'grade' => $grade,
                    'score' => $p->score ?? null,
                    'cover_days' => $p->cover_days ?? null,
                    'action' => $action,
                ];
            })
            ->sortByDesc('qty_ready')
            ->values();

        return [
            'priority' => $priority,
            'skus' => $grouped,
            'total_remaining' => (float) $grouped->sum('qty_ready'),
            'total_hpp' => (float) $grouped->sum('hpp_total'),
            'total_stok_jadi' => (float) $grouped->sum('stok_jadi'),
        ];
    }

    // ==========================================================
    // C) SEDANG JAHIT — WIP jahit yang masih dipegang penjahit
    // ==========================================================
    /**
     * Baris WIP jahit yang masih outstanding (qty_bundle − returned > 0),
     * beserta penjahit pemegang, SKU, dan umur sejak tanggal pickup.
     */
    private function buildSedangJahit(array $f): array
    {
        $today = Carbon::today();

        // ---- Sumber data SAMA dengan halaman buat Sewing Return ----
        // SewingPickupLine outstanding (belum void) yang itemnya masih punya stok
        // di gudang WIP-SEW. Sisa = qty_bundle − (returned_ok + returned_reject
        //                                        + direct_picked + progress_adjusted).
        $wipSewId = Warehouse::whereIn('code', ['WIP-SEW', 'WH-SEWING'])->value('id');

        $remainingExpr = 'pl.qty_bundle'
            . ' - COALESCE(pl.qty_returned_ok,0)'
            . ' - COALESCE(pl.qty_returned_reject,0)'
            . ' - COALESCE(pl.qty_direct_picked,0)'
            . ' - COALESCE(pl.qty_progress_adjusted,0)';

        $q = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->join('employees as e', 'e.id', '=', 'p.operator_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->whereNull('p.voided_at')
            ->whereNull('pl.voided_at')
            ->whereRaw("$remainingExpr > 0.0001");

        // hanya item yang masih punya stok di WIP-SEW (sama dgn form return)
        if ($wipSewId) {
            $q->whereExists(function ($sub) use ($wipSewId) {
                $sub->from('inventory_stocks as ws')
                    ->whereColumn('ws.item_id', 'pl.finished_item_id')
                    ->where('ws.warehouse_id', $wipSewId)
                    ->where('ws.qty', '>', 0.0001);
            });
        }

        if ($f['operator_id']) {
            $q->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $q->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $q->where('it.item_category_id', $f['category_id']);
        }

        $rows = $q->selectRaw("
                it.id as item_id,
                p.date as pickup_date, p.created_at as created_at,
                p.operator_id as operator_id,
                e.code as operator_code, e.name as operator_name,
                it.code as sku, it.name as product_name,
                COALESCE(cat.name,'-') as category,
                pl.qty_bundle as qty_picked,
                COALESCE(pl.qty_returned_ok,0) + COALESCE(pl.qty_returned_reject,0)
                    + COALESCE(pl.qty_direct_picked,0) + COALESCE(pl.qty_progress_adjusted,0) as qty_returned,
                ($remainingExpr) as qty_outstanding
            ")
            ->orderBy('p.date')
            ->get();

        // ---- HPP satuan per item (sama dgn tab Siap Jahit: effective_unit_cost) ----
        $costByItem = Item::whereIn('id', $rows->pluck('item_id')->unique()->values()->all())
            ->get()
            ->mapWithKeys(fn($it) => [$it->id => (float) ($it->effective_unit_cost ?? 0)]);

        $lines = $rows
            ->map(function ($r) use ($today, $costByItem) {
                $r->age_days = $r->pickup_date
                    ? Carbon::parse($r->pickup_date)->startOfDay()->diffInDays($today)
                    : null;
                $r->hpp_unit = (float) ($costByItem[$r->item_id] ?? 0);
                $r->hpp_total = (float) $r->qty_outstanding * $r->hpp_unit;
                return $r;
            })
            ->sortBy([['pickup_date', 'desc'], ['created_at', 'desc'], ['qty_outstanding', 'desc']])
            ->values();

        return [
            'lines' => $lines,
            'total_outstanding' => (float) $lines->sum('qty_outstanding'),
            'total_hpp' => (float) $lines->sum('hpp_total'),
            'operator_count' => $lines->pluck('operator_code')->unique()->count(),
        ];
    }

    // ==========================================================
    // D) SETOR & QC — setoran jahit harian (OK / reject / yield)
    // ==========================================================
    /**
     * Rekap setoran jahit per hari di periode terpilih: qty OK, reject,
     * yield (OK / total), beserta total ringkas.
     */
    private function buildSetorQc(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // Fokus: stok barang jadi yang MASUK gudang WH-PRD lewat setoran jahit (QC OK).
        $whPrdId = Warehouse::where('code', 'WH-PRD')->value('id');

        $q = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to])
            ->whereNull('r.voided_at');
        if ($whPrdId) {
            $q->where('r.destination_warehouse_id', $whPrdId);
        }
        if ($f['operator_id']) {
            $q->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $q->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $q->where('it.item_category_id', $f['category_id']);
        }

        $q->join('item_categories as cat', 'cat.id', '=', 'it.item_category_id', 'left')
            ->join('employees as e', 'e.id', '=', 'r.operator_id', 'left');

        $raw = $q->selectRaw("
                rl.id as line_id,
                DATE(r.date) as date, r.created_at as created_at,
                it.id as item_id, it.code as sku, it.name as product_name,
                COALESCE(cat.name,'-') as category,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                COALESCE(rl.qty_ok,0) as qty_ok,
                COALESCE(rl.qty_reject,0) as qty_reject
            ")
            ->orderByDesc(DB::raw('DATE(r.date)'))
            ->orderByDesc('r.created_at')
            ->orderByDesc('rl.qty_ok')
            ->get();

        // HPP satuan per item (sama dgn tab lain: effective_unit_cost)
        $costByItem = Item::whereIn('id', $raw->pluck('item_id')->filter()->unique()->values()->all())
            ->get()
            ->mapWithKeys(fn($it) => [$it->id => (float) ($it->effective_unit_cost ?? 0)]);

        $lines = $raw->map(function ($r) use ($costByItem) {
            $ok = (float) $r->qty_ok;
            $rj = (float) $r->qty_reject;
            $base = $ok + $rj;
            return (object) [
                'date' => $r->date,
                'created_at' => $r->created_at,
                'operator_code' => $r->operator_code,
                'operator_name' => $r->operator_name,
                'sku' => $r->sku,
                'product_name' => $r->product_name,
                'category' => $r->category,
                'qty_ok' => $ok,
                'qty_reject' => $rj,
                'total' => $base,
                'yield' => $base > 0 ? round($ok / $base * 100, 1) : null,
                'hpp_total' => $ok * (float) ($costByItem[$r->item_id] ?? 0),
            ];
        })->values();

        $totalOk = (float) $lines->sum('qty_ok');
        $totalRj = (float) $lines->sum('qty_reject');
        $base = $totalOk + $totalRj;

        return [
            'lines' => $lines,
            'total_ok' => $totalOk,
            'total_reject' => $totalRj,
            'total_hpp' => (float) $lines->sum('hpp_total'),
            'yield' => $base > 0 ? round($totalOk / $base * 100, 1) : null,
            'operator_count' => $lines->pluck('operator_code')->unique()->reject(fn($c) => $c === '-')->count(),
        ];
    }

    // ==========================================================
    // E) REJECT — barang gagal QC (cutting + jahit), per kejadian
    // ==========================================================
    /**
     * Gabungan reject dari dua tahap QC dalam periode:
     *  - Cutting : qc_results (stage=cutting, qty_reject>0)
     *  - Jahit   : sewing_return_lines (qty_reject>0)
     * Tiap baris berisi tahap, operator, SKU, qty reject, alasan, & nilai HPP.
     */
    private function buildReject(array $f): array
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // ---- Reject CUTTING ----
        $cut = DB::table('qc_results as qc')
            ->join('cutting_job_bundles as cb', 'cb.id', '=', 'qc.cutting_job_bundle_id')
            ->join('items as it', 'it.id', '=', 'cb.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'qc.operator_id')
            ->where('qc.stage', 'cutting')
            ->where('qc.qty_reject', '>', 0)
            ->whereRaw('DATE(qc.qc_date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $cut->where('qc.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $cut->where('cb.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $cut->where('it.item_category_id', $f['category_id']);
        }
        $cut = $cut->selectRaw("
                'Cutting' as stage,
                NULL as line_id,
                DATE(qc.qc_date) as date, qc.created_at as created_at,
                it.id as item_id, it.code as sku, it.name as product_name,
                COALESCE(cat.name,'-') as category,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                qc.qty_reject as qty,
                COALESCE(NULLIF(qc.reject_reason,''),'-') as reason
            ")
            ->get();

        // ---- Reject JAHIT ----
        $sew = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->leftJoin('employees as e', 'e.id', '=', 'r.operator_id')
            ->where('rl.qty_reject', '>', 0)
            ->whereNull('r.voided_at')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $sew->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $sew->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $sew->where('it.item_category_id', $f['category_id']);
        }
        $sew = $sew->selectRaw("
                'Jahit' as stage,
                rl.id as line_id,
                DATE(r.date) as date, r.created_at as created_at,
                it.id as item_id, it.code as sku, it.name as product_name,
                COALESCE(cat.name,'-') as category,
                COALESCE(e.code,'-') as operator_code, COALESCE(e.name,'-') as operator_name,
                rl.qty_reject as qty,
                COALESCE(NULLIF(rl.notes,''),'-') as reason
            ")
            ->get();

        $all = $cut->concat($sew);

        // HPP satuan per item (sama dgn tab lain: effective_unit_cost)
        $costByItem = Item::whereIn('id', $all->pluck('item_id')->filter()->unique()->values()->all())
            ->get()
            ->mapWithKeys(fn($it) => [$it->id => (float) ($it->effective_unit_cost ?? 0)]);

        // Qty yang sudah diperbaiki (→ WH-PRD) per baris reject jahit
        $repairedByLine = $this->repairedQtyByLine(
            $all->pluck('line_id')->filter()->map(fn($v) => (int) $v)->unique()->values()->all()
        );

        $lines = $all->map(function ($r) use ($costByItem, $repairedByLine) {
            $qty = (float) $r->qty;
            $lineId = isset($r->line_id) ? (int) $r->line_id : 0;
            $repaired = $lineId > 0 ? max((float) ($repairedByLine[$lineId] ?? 0), 0.0) : 0.0;
            $repaired = min($repaired, $qty);
            return (object) [
                'stage' => $r->stage,
                'date' => $r->date,
                'created_at' => $r->created_at ?? null,
                'operator_code' => $r->operator_code,
                'operator_name' => $r->operator_name,
                'sku' => $r->sku,
                'product_name' => $r->product_name,
                'category' => $r->category,
                'qty' => $qty,
                'repaired' => $repaired,
                'remaining' => max($qty - $repaired, 0.0),
                'reason' => $r->reason,
                'hpp_total' => $qty * (float) ($costByItem[$r->item_id] ?? 0),
            ];
        })
            ->sortBy([['date', 'desc'], ['created_at', 'desc'], ['qty', 'desc']])
            ->values();

        return [
            'lines' => $lines,
            'total_reject' => (float) $lines->sum('qty'),
            'reject_cutting' => (float) $lines->where('stage', 'Cutting')->sum('qty'),
            'reject_sewing' => (float) $lines->where('stage', 'Jahit')->sum('qty'),
            'total_repaired' => (float) $lines->sum('repaired'),
            'total_remaining' => (float) $lines->sum('remaining'),
            'total_hpp' => (float) $lines->sum('hpp_total'),
        ];
    }

    /**
     * Net qty yang sudah diperbaiki (masuk WH-PRD) per sewing_return_line.
     * Perbaikan = IN (+), pembatalan = OUT (-) di ledger gudang WH-PRD.
     *
     * @param  array<int,int>  $lineIds
     * @return array<int,float>
     */
    private function repairedQtyByLine(array $lineIds): array
    {
        if (empty($lineIds)) {
            return [];
        }

        $whPrd = \App\Models\Warehouse::query()->where('code', 'WH-PRD')->first();
        if (!$whPrd) {
            return [];
        }

        return DB::table('inventory_mutations')
            ->where('warehouse_id', $whPrd->id)
            ->whereIn('source_type', ['reject_repair', 'reject_repair_void'])
            ->whereIn('source_id', $lineIds)
            ->groupBy('source_id')
            ->selectRaw('source_id, SUM(qty_change) as net')
            ->pluck('net', 'source_id')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    // ==========================================================
    // 4a) AKTIVITAS PENJAHIT — per transaksi (tidak diringkas)
    // ==========================================================
    /**
     * Daftar kejadian jahit per baris (tidak di-grouping per penjahit):
     *  - Ambil : tiap sewing_pickup_line (penjahit mengambil bundle) → qty pcs.
     *  - Setor : tiap sewing_return_line (penjahit menyetor hasil)    → OK + reject.
     * Diurutkan terbaru dulu. Skor/grade/efisiensi tidak relevan di mode detail.
     */
    private function buildPenjahitActivity(array $f): \Illuminate\Support\Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // ---- Ambil jahit (pickup lines) ----
        $pick = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->join('employees as e', 'e.id', '=', 'p.operator_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->whereNull('p.voided_at')
            ->whereNull('pl.voided_at')
            ->whereRaw('DATE(p.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $pick->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pick->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $pick->where('it.item_category_id', $f['category_id']);
        }
        $pick = $pick->selectRaw("
                'Ambil' as type, p.code as code, DATE(p.date) as date, p.created_at as created_at,
                DATE(p.date) as pickup_date,
                e.id as operator_emp_id, e.code as operator_code, e.name as operator_name,
                it.id as item_id, it.code as sku, it.name as product_name, COALESCE(cat.name,'-') as category,
                COALESCE(pl.qty_bundle,0) as qty, 0 as qty_ok, 0 as qty_reject,
                COALESCE(pl.qty_bundle,0) - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0) as qty_outstanding
            ")
            ->get();

        // ---- Setor jahit (return lines) ----
        $ret = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('sewing_pickups as pp', 'pp.id', '=', 'pl.sewing_pickup_id')
            ->join('items as it', 'it.id', '=', 'pl.finished_item_id')
            ->join('employees as e', 'e.id', '=', 'r.operator_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            ->whereNull('r.voided_at')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to]);
        if ($f['operator_id']) {
            $ret->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $ret->where('pl.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $ret->where('it.item_category_id', $f['category_id']);
        }
        $ret = $ret->selectRaw("
                'Setor' as type, r.code as code, DATE(r.date) as date, r.created_at as created_at,
                DATE(pp.date) as pickup_date,
                e.id as operator_emp_id, e.code as operator_code, e.name as operator_name,
                it.id as item_id, it.code as sku, it.name as product_name, COALESCE(cat.name,'-') as category,
                COALESCE(rl.qty_ok,0) as qty, COALESCE(rl.qty_ok,0) as qty_ok, COALESCE(rl.qty_reject,0) as qty_reject,
                0 as qty_outstanding
            ")
            ->get();

        // Memo agar resolusi PieceRate tidak query berulang utk kombinasi sama.
        $rateMemo = [];

        return $pick->concat($ret)
            ->map(function ($r) use (&$rateMemo) {
                $qtyOk = (float) $r->qty_ok;
                $rate = 0.0;
                $amount = 0.0;
                // Piece rate ditampilkan utk Ambil & Setor (acuan tarif borongan).
                $key = $r->operator_emp_id . '|' . $r->item_id . '|' . $r->date;
                $rate = $rateMemo[$key] ??= (float) $this->pieceRate->getRatePerPcs(
                    'sewing',
                    (int) $r->operator_emp_id,
                    (int) $r->item_id,
                    $r->date,
                );
                // Upah borongan jahit hanya dihitung dari hasil yang DISETOR & lolos QC.
                if ($r->type === 'Setor' && $qtyOk > 0) {
                    $amount = $rate * $qtyOk;
                }
                return (object) [
                    'type' => $r->type,
                    'code' => $r->code,
                    'date' => $r->date,
                    'pickup_date' => $r->pickup_date,
                    'created_at' => $r->created_at,
                    'operator_code' => $r->operator_code,
                    'operator_name' => $r->operator_name,
                    'sku' => $r->sku,
                    'product_name' => $r->product_name,
                    'category' => $r->category,
                    'qty' => (float) $r->qty,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => (float) $r->qty_reject,
                    'qty_outstanding' => (float) ($r->qty_outstanding ?? 0),
                    'rate' => $rate,
                    'amount' => $amount,
                ];
            })
            ->sortBy([['date', 'desc'], ['created_at', 'desc']])
            ->values();
    }

    /**
     * Daftar kejadian cutting per baris (tidak di-grouping per pemotong):
     *  - tiap cutting_job_bundle (status job = qc_done) → qty potong, OK, reject.
     *  - upah borongan cutting = piece rate (module=cutting) × qty_qc_ok.
     * Selaras dgn tab Penjahit: KPI + toolbar + tabel per transaksi + footer.
     */
    private function buildCuttingActivity(array $f): \Illuminate\Support\Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        $q = DB::table('cutting_job_bundles as cb')
            ->join('cutting_jobs as cj', 'cj.id', '=', 'cb.cutting_job_id')
            ->join('items as it', 'it.id', '=', 'cb.finished_item_id')
            ->leftJoin('item_categories as cat', 'cat.id', '=', 'it.item_category_id')
            // operator pemotong: utamakan operator bundle, fallback ke operator job
            ->leftJoin('employees as e', 'e.id', '=', DB::raw('COALESCE(cb.operator_id, cj.operator_id)'))
            ->where('cj.status', 'qc_done')
            ->whereRaw('DATE(cj.date) BETWEEN ? AND ?', [$from, $to]);

        if ($f['operator_id']) {
            $q->whereRaw('COALESCE(cb.operator_id, cj.operator_id) = ?', [$f['operator_id']]);
        }
        if ($f['item_id']) {
            $q->where('cb.finished_item_id', $f['item_id']);
        }
        if ($f['category_id']) {
            $q->where('it.item_category_id', $f['category_id']);
        }

        $rows = $q->selectRaw("
                cj.code as code, DATE(cj.date) as date, cj.created_at as created_at,
                COALESCE(cb.operator_id, cj.operator_id) as operator_emp_id,
                COALESCE(e.code, '-') as operator_code, COALESCE(e.name, 'Tanpa Operator') as operator_name,
                it.id as item_id, it.code as sku, it.name as product_name, COALESCE(cat.name,'-') as category,
                COALESCE(cb.qty_pcs,0) as qty, COALESCE(cb.qty_qc_ok,0) as qty_ok, COALESCE(cb.qty_qc_reject,0) as qty_reject
            ")
            ->get();

        // Memo agar resolusi PieceRate tidak query berulang utk kombinasi sama.
        $rateMemo = [];

        return $rows
            ->map(function ($r) use (&$rateMemo) {
                $qtyOk = (float) $r->qty_ok;
                $rate = 0.0;
                $amount = 0.0;
                if ($r->operator_emp_id) {
                    $key = $r->operator_emp_id . '|' . $r->item_id . '|' . $r->date;
                    $rate = $rateMemo[$key] ??= (float) $this->pieceRate->getRatePerPcs(
                        'cutting',
                        (int) $r->operator_emp_id,
                        (int) $r->item_id,
                        $r->date,
                    );
                    // Upah borongan cutting dihitung dari hasil yang LOLOS QC.
                    if ($qtyOk > 0) {
                        $amount = $rate * $qtyOk;
                    }
                }
                return (object) [
                    'code' => $r->code,
                    'date' => $r->date,
                    'pickup_date' => null,
                    'created_at' => $r->created_at,
                    'operator_code' => $r->operator_code,
                    'operator_name' => $r->operator_name,
                    'sku' => $r->sku,
                    'product_name' => $r->product_name,
                    'category' => $r->category,
                    'qty' => (float) $r->qty,
                    'qty_ok' => $qtyOk,
                    'qty_reject' => (float) $r->qty_reject,
                    'rate' => $rate,
                    'amount' => $amount,
                ];
            })
            ->sortBy([['date', 'desc'], ['created_at', 'desc']])
            ->values();
    }

    // ==========================================================
    // 4) PERFORMA OPERATOR (pickup + return + lead + score)
    // ==========================================================
    private function buildOperatorPerformance(array $f): \Illuminate\Support\Collection
    {
        $from = $f['date_from'];
        $to = $f['date_to'];

        // pickup-side: picked + outstanding (pickup date in range)
        $pickQ = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->join('employees as e', 'e.id', '=', 'p.operator_id')
            ->whereRaw('DATE(p.date) BETWEEN ? AND ?', [$from, $to]);
        $this->applyNotVoid($pickQ, 'sewing_pickups', 'p');
        if ($f['operator_id']) {
            $pickQ->where('p.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $pickQ->where('pl.finished_item_id', $f['item_id']);
        }
        $pick = $pickQ->selectRaw('
                p.operator_id, e.code as operator_code, e.name as operator_name,
                COUNT(DISTINCT p.id) as total_pickups,
                COALESCE(SUM(pl.qty_bundle),0) as picked,
                COALESCE(SUM(pl.qty_bundle - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0)),0) as outstanding
            ')
            ->groupBy('p.operator_id', 'e.code', 'e.name')
            ->get()->keyBy('operator_id');

        // return-side: ok + reject (return date in range)
        $retQ = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->whereRaw('DATE(r.date) BETWEEN ? AND ?', [$from, $to]);
        $this->applyNotVoid($retQ, 'sewing_returns', 'r');
        if ($f['operator_id']) {
            $retQ->where('r.operator_id', $f['operator_id']);
        }
        if ($f['item_id']) {
            $retQ->join('sewing_pickup_lines as pl2', 'pl2.id', '=', 'rl.sewing_pickup_line_id')
                ->where('pl2.finished_item_id', $f['item_id']);
        }
        $ret = $retQ->selectRaw('
                r.operator_id,
                COALESCE(SUM(rl.qty_ok),0) as total_ok,
                COALESCE(SUM(rl.qty_reject),0) as total_reject
            ')
            ->groupBy('r.operator_id')->get()->keyBy('operator_id');

        // lead time: avg(return.date - pickup.date) per operator (pickup date in range)
        $leadRows = DB::table('sewing_return_lines as rl')
            ->join('sewing_returns as r', 'r.id', '=', 'rl.sewing_return_id')
            ->join('sewing_pickup_lines as pl', 'pl.id', '=', 'rl.sewing_pickup_line_id')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereRaw('DATE(p.date) BETWEEN ? AND ?', [$from, $to])
            ->when($f['operator_id'], fn($q) => $q->where('r.operator_id', $f['operator_id']))
            ->when($f['item_id'], fn($q) => $q->where('pl.finished_item_id', $f['item_id']))
            ->selectRaw('r.operator_id, r.date as return_date, p.date as pickup_date')
            ->get()
            ->groupBy('operator_id')
            ->map(function ($g) {
                $vals = $g->map(function ($row) {
                    if (!$row->pickup_date || !$row->return_date) {
                        return null;
                    }
                    return Carbon::parse($row->pickup_date)->startOfDay()
                        ->diffInDays(Carbon::parse($row->return_date)->startOfDay());
                })->filter(fn($v) => $v !== null);
                return $vals->count() ? round($vals->avg(), 1) : null;
            });

        // gabung per operator
        $opIds = collect($pick->keys())->merge($ret->keys())->unique()->filter();

        return $opIds->map(function ($opId) use ($pick, $ret, $leadRows) {
            $p = $pick->get($opId);
            $r = $ret->get($opId);
            $picked = (float) ($p->picked ?? 0);
            $ok = (float) ($r->total_ok ?? 0);
            $reject = (float) ($r->total_reject ?? 0);
            $outstanding = (float) ($p->outstanding ?? 0);
            $base = $ok + $reject;
            $efficiency = $base > 0 ? round($ok / $base * 100, 1) : null;
            $avgLead = $leadRows->get($opId);

            // Behavior score (0-100)
            $okRate = $picked > 0 ? $ok / $picked : 0;
            $rejectRate = $base > 0 ? $reject / $base : 0;
            $outRatio = $picked > 0 ? max($outstanding, 0) / $picked : 0;
            $scoreCompletion = min($okRate * 100, 100) * 0.40;
            $scoreReject = (1 - min($rejectRate, 0.20) / 0.20) * 25;
            $scoreOutstanding = (1 - min($outRatio, 0.50) / 0.50) * 20;
            $scoreLead = $avgLead === null ? 7.5 : (1 - min($avgLead, 7) / 7) * 15;
            $score = max(0, min(100, round($scoreCompletion + $scoreReject + $scoreOutstanding + $scoreLead, 1)));
            $grade = $score >= 85 ? 'Excellent' : ($score >= 70 ? 'Good' : ($score >= 50 ? 'Cukup' : 'Perlu Perhatian'));

            return (object) [
                'operator_code' => $p->operator_code ?? '-',
                'operator_name' => $p->operator_name ?? '-',
                'total_pickups' => (int) ($p->total_pickups ?? 0),
                'picked' => $picked,
                'total_ok' => $ok,
                'total_reject' => $reject,
                'outstanding' => max($outstanding, 0),
                'efficiency' => $efficiency,
                'avg_lead_days' => $avgLead,
                'score' => $score,
                'grade' => $grade,
            ];
        })->sortByDesc('score')->values();
    }

}
