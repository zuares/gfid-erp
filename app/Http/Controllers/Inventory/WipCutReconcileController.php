<?php

namespace App\Http\Controllers\Inventory;

use App\Http\Controllers\Controller;
use App\Models\CuttingJobBundle;
use App\Models\Item;
use App\Models\Warehouse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class WipCutReconcileController extends Controller
{
    private const EPS = 0.0001;

    /**
     * Rekonsiliasi WIP-CUT:
     * - Bandingkan stok ledger (inventory_mutations) vs kolom bundle (cutting_job_bundles.wip_qty)
     * - Tandai item yang DRIFT (ledger != sum wip_qty) → indikasi data tidak konsisten
     * - Tampilkan juga "ready for sewing" yang muncul di halaman Ambil Jahit
     */
    public function index(Request $request)
    {
        $search = strtoupper(trim((string) $request->input('search', '')));
        $showAll = $request->boolean('all');

        $wipCut = Warehouse::where('code', 'WIP-CUT')->first();

        if (!$wipCut) {
            return view('inventory.wip_cut_reconcile.index', [
                'rows' => collect(),
                'summary' => null,
                'filters' => ['search' => $search, 'all' => $showAll],
                'missingWarehouse' => true,
            ]);
        }

        // =========================================================
        // 1) Ledger fisik WIP-CUT per item (inventory_mutations)
        // =========================================================
        $ledger = DB::table('inventory_mutations')
            ->where('warehouse_id', $wipCut->id)
            ->select('item_id', DB::raw('SUM(qty_change) AS qty'))
            ->groupBy('item_id')
            ->pluck('qty', 'item_id'); // [item_id => qty]

        // =========================================================
        // 2) Versi bundle (cutting_job_bundles) per item di WIP-CUT
        // =========================================================
        $bundles = CuttingJobBundle::with(['finishedItem', 'qcResults'])
            ->where('cut_wip_warehouse_id', $wipCut->id)
            ->get();

        $bundleAgg = []; // [item_id => [...]]
        foreach ($bundles as $b) {
            $itemId = (int) $b->finished_item_id;

            if (!isset($bundleAgg[$itemId])) {
                $bundleAgg[$itemId] = [
                    'item' => $b->finishedItem,
                    'count' => 0,
                    'wip_qty' => 0.0,
                    'net_wip' => 0.0,
                    'qty_cutting_ok' => 0.0,
                    'picked' => 0.0,
                    'ready' => 0.0,
                ];
            }

            $wip = (float) ($b->cut_wip_qty ?? 0);
            $picked = (float) ($b->sewing_picked_qty ?? 0);

            $bundleAgg[$itemId]['count']++;
            $bundleAgg[$itemId]['wip_qty'] += $wip;
            // NET = sisa fisik bundle (cut_wip_qty dikurangi yang sudah dipick).
            // Inilah angka yang sebanding dengan ledger fisik WIP-CUT.
            $bundleAgg[$itemId]['net_wip'] += max($wip - $picked, 0.0);
            $bundleAgg[$itemId]['qty_cutting_ok'] += (float) $b->qty_cutting_ok;
            $bundleAgg[$itemId]['picked'] += $picked;
            $bundleAgg[$itemId]['ready'] += (float) $b->qty_ready_for_sewing;
        }

        // =========================================================
        // 3) Gabungkan semua item_id (ledger ∪ bundle)
        // =========================================================
        $itemIds = collect($ledger->keys())
            ->merge(array_keys($bundleAgg))
            ->map(fn ($id) => (int) $id)
            ->unique();

        // Ambil data item untuk item yang hanya ada di ledger (tidak ada bundle)
        $itemsMeta = \App\Models\Item::whereIn('id', $itemIds)->get()->keyBy('id');

        $rows = $itemIds->map(function (int $itemId) use ($ledger, $bundleAgg, $itemsMeta) {
            $ledgerQty = (float) ($ledger[$itemId] ?? 0);
            $agg = $bundleAgg[$itemId] ?? null;

            $bundleWip = (float) ($agg['wip_qty'] ?? 0);
            $bundleNet = (float) ($agg['net_wip'] ?? 0);
            $item = $agg['item'] ?? ($itemsMeta[$itemId] ?? null);

            // Drift dibandingkan terhadap NET sisa bundle (wip_qty − dipick),
            // karena ledger fisik WIP-CUT juga sudah dikurangi pengambilan jahit.
            $drift = $ledgerQty - $bundleNet;

            return (object) [
                'item_id' => $itemId,
                'item_code' => $item->code ?? ('#' . $itemId),
                'item_name' => $item->name ?? '(item terhapus)',
                'bundle_count' => (int) ($agg['count'] ?? 0),
                'ledger_qty' => $ledgerQty,
                'bundle_wip_qty' => $bundleWip,
                'bundle_net_wip' => $bundleNet,
                'qty_cutting_ok' => (float) ($agg['qty_cutting_ok'] ?? 0),
                'picked' => (float) ($agg['picked'] ?? 0),
                'ready' => (float) ($agg['ready'] ?? 0),
                'drift' => $drift,
                'is_drift' => abs($drift) > self::EPS,
            ];
        });

        // Search
        if ($search !== '') {
            $rows = $rows->filter(function ($r) use ($search) {
                return str_contains(strtoupper($r->item_code), $search)
                    || str_contains(strtoupper($r->item_name), $search);
            });
        }

        // Default: hanya tampilkan yang drift (kecuali user minta lihat semua)
        if (!$showAll && $search === '') {
            $rows = $rows->filter(fn ($r) => $r->is_drift);
        }

        // Urutkan: drift terbesar dulu, lalu kode
        $rows = $rows->sortBy([
            fn ($a, $b) => abs($b->drift) <=> abs($a->drift),
            fn ($a, $b) => $a->item_code <=> $b->item_code,
        ])->values();

        // =========================================================
        // Summary
        // =========================================================
        $allRowsForSummary = $itemIds->map(function (int $itemId) use ($ledger, $bundleAgg) {
            $ledgerQty = (float) ($ledger[$itemId] ?? 0);
            $bundleNet = (float) ($bundleAgg[$itemId]['net_wip'] ?? 0);
            return abs($ledgerQty - $bundleNet) > self::EPS;
        });

        $summary = [
            'total_items' => $itemIds->count(),
            'drift_items' => $allRowsForSummary->filter()->count(),
        ];

        return view('inventory.wip_cut_reconcile.index', [
            'rows' => $rows,
            'summary' => $summary,
            'filters' => ['search' => $request->input('search', ''), 'all' => $showAll],
            'missingWarehouse' => false,
        ]);
    }

    /**
     * Drill-down per item: lihat penyebab drift.
     * - Pergerakan ledger WIP-CUT dikelompokkan per dokumen (source_type)
     * - Rincian tiap bundle (wip_qty / QC OK / picked / ready)
     * - Bandingkan: ledger vs Σ wip_qty vs ekspektasi produksi (QC OK − picked)
     */
    public function show(Item $item, Request $request)
    {
        $wipCut = Warehouse::where('code', 'WIP-CUT')->firstOrFail();

        // =========================================================
        // 1) Ledger WIP-CUT untuk item ini
        // =========================================================
        $ledgerTotal = (float) DB::table('inventory_mutations')
            ->where('warehouse_id', $wipCut->id)
            ->where('item_id', $item->id)
            ->sum('qty_change');

        // Dikelompokkan per dokumen (source_type)
        $ledgerBySource = DB::table('inventory_mutations')
            ->where('warehouse_id', $wipCut->id)
            ->where('item_id', $item->id)
            ->select(
                'source_type',
                DB::raw('COUNT(*) AS n'),
                DB::raw('SUM(CASE WHEN qty_change > 0 THEN qty_change ELSE 0 END) AS qty_in'),
                DB::raw('SUM(CASE WHEN qty_change < 0 THEN qty_change ELSE 0 END) AS qty_out'),
                DB::raw('SUM(qty_change) AS net')
            )
            ->groupBy('source_type')
            ->orderByRaw('SUM(qty_change) DESC')
            ->get();

        // Pergerakan terakhir (detail)
        $ledgerRecent = DB::table('inventory_mutations')
            ->where('warehouse_id', $wipCut->id)
            ->where('item_id', $item->id)
            ->orderByDesc('date')
            ->orderByDesc('id')
            ->limit(100)
            ->get(['id', 'date', 'source_type', 'source_id', 'qty_change', 'notes']);

        // =========================================================
        // 2) SEMUA bundle untuk item ini (lintas gudang)
        //    supaya bundle "nyangkut" (punya sisa fisik tapi TIDAK
        //    di-flag WIP-CUT) ikut kelihatan.
        // =========================================================
        $allBundles = CuttingJobBundle::with(['qcResults', 'cuttingJob', 'wipWarehouse', 'cutWipWarehouse'])
            ->where('finished_item_id', $item->id)
            ->orderBy('id')
            ->get()
            ->map(function (CuttingJobBundle $b) use ($wipCut) {
                $wip = (float) ($b->cut_wip_qty ?? 0);
                $picked = (float) ($b->sewing_picked_qty ?? 0);
                $qcOk = (float) $b->qty_cutting_ok;
                $netWip = max($wip - $picked, 0.0); // sisa fisik menurut kolom cutting-WIP
                $netProd = max($qcOk - $picked, 0.0); // sisa menurut produksi (QC OK − dipick)
                $inWipCut = (int) $b->cut_wip_warehouse_id === (int) $wipCut->id;

                // "Nyangkut": masih punya sisa produksi tapi TIDAK terdaftar di WIP-CUT
                // (cut_wip_warehouse_id ≠ WIP-CUT / belum di-backfill)
                // → stok ini ada (atau seharusnya ada) tapi tak muncul di Ambil Jahit.
                $isOrphan = !$inWipCut && $netProd > self::EPS;

                return (object) [
                    'id' => $b->id,
                    'bundle_code' => $b->bundle_code,
                    'status' => $b->status,
                    'qty_pcs' => (float) ($b->qty_pcs ?? 0),
                    'qty_cutting_ok' => $qcOk,
                    'wip_qty' => $wip,
                    'net_wip' => $netWip,
                    'picked' => $picked,
                    'ready' => (float) $b->qty_ready_for_sewing,
                    'wip_warehouse_id' => $b->cut_wip_warehouse_id,
                    'wip_warehouse_code' => $b->cutWipWarehouse->code ?? ($b->wipWarehouse->code ?? null),
                    'in_wip_cut' => $inWipCut,
                    'is_orphan' => $isOrphan,
                ];
            });

        // Bundle yang resmi terdaftar di WIP-CUT
        $bundles = $allBundles->where('in_wip_cut', true)->values();
        // Bundle "nyangkut": sisa produksi > 0 tapi bukan WIP-CUT
        $orphanBundles = $allBundles->where('is_orphan', true)->values();

        $sumWip = (float) $bundles->sum('wip_qty');
        $sumNetWip = (float) $bundles->sum('net_wip');
        $sumQcOk = (float) $bundles->sum('qty_cutting_ok');
        $sumPicked = (float) $bundles->sum('picked');
        $sumReady = (float) $bundles->sum('ready');

        // Total sisa produksi yang "nyangkut" di luar WIP-CUT
        $orphanNetProd = (float) $orphanBundles->sum(fn ($b) => max($b->qty_cutting_ok - $b->picked, 0.0));

        // Ekspektasi produksi murni (bundle WIP-CUT): QC OK − sudah dipick
        $prodExpected = max($sumQcOk - $sumPicked, 0.0);

        $summary = [
            'ledger_total' => $ledgerTotal,
            'sum_wip_qty' => $sumWip,
            'sum_net_wip' => $sumNetWip,
            'sum_qty_cutting_ok' => $sumQcOk,
            'sum_picked' => $sumPicked,
            'sum_ready' => $sumReady,
            'prod_expected' => $prodExpected,
            'orphan_count' => $orphanBundles->count(),
            'orphan_net_prod' => $orphanNetProd,
            // Drift utama: ledger fisik vs sisa NET bundle (wip_qty − dipick)
            'drift_ledger_vs_wip' => $ledgerTotal - $sumNetWip,
            // Sekunder: sisa NET bundle vs ekspektasi produksi
            'drift_wip_vs_prod' => $sumNetWip - $prodExpected,
        ];

        return view('inventory.wip_cut_reconcile.show', [
            'item' => $item,
            'summary' => $summary,
            'ledgerBySource' => $ledgerBySource,
            'ledgerRecent' => $ledgerRecent,
            'bundles' => $bundles,
            'orphanBundles' => $orphanBundles,
        ]);
    }
}
