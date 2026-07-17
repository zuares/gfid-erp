<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\CuttingJob;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemRole;
use App\Models\Lot;
use App\Models\QcResult;
use App\Models\Warehouse;
use App\Models\CuttingJobLot;
use App\Models\WipOpnamePeriod;
use App\Services\Accounting\JournalService;
use App\Services\Inventory\InventoryService;
use App\Services\Inventory\LotCostService;
use App\Services\Production\CuttingService;
use Illuminate\Validation\ValidationException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

// <-- sesuaikan nama model saldo stok kamu

class CuttingJobController extends Controller
{
    public function __construct(
        protected InventoryService $inventory,
        protected CuttingService $cutting,
        protected JournalService $journal,
        protected LotCostService $lotCost,
    ) {}

    /** Kata konfirmasi yang harus diketik untuk membersihkan data produksi. */
    public const CLEAN_PROD_PHRASE = 'BERSIHKAN PRODUKSI';

    /**
     * Bersihkan SEMUA data transaksi produksi (owner-only).
     *
     * Tersedia di semua mode (dev/ops/production), TAPI dikunci berlapis:
     *   1) role owner
     *   2) konfirmasi ketik frasa CLEAN_PROD_PHRASE (anti klik tidak sengaja)
     *
     * Menghapus: cutting/sewing/QC/finishing/packing/WIP-opname + mutasi &
     * jurnal produksi + adjustment yang ber-referensi dokumen produksi,
     * lalu menghitung ulang stok gudang & saldo lot dari mutasi tersisa.
     * Master data, GRN/pembelian, stock opname, & storefront TIDAK disentuh.
     *
     * ⚠️ Data transaksi yang dihapus TIDAK bisa dikembalikan. Backup dulu di prod.
     */
    public function devCleanProductionData(Request $request)
    {
        if (!$request->user() || $request->user()->role !== 'owner') {
            abort(403, 'Hanya owner yang bisa membersihkan data produksi.');
        }

        // Konfirmasi ketik — wajib, terutama di lingkungan ops/production.
        $typed = strtoupper(trim((string) $request->input('confirm_text')));
        if ($typed !== self::CLEAN_PROD_PHRASE) {
            return back()->with('error',
                'Konfirmasi salah. Ketik persis "' . self::CLEAN_PROD_PHRASE . '" untuk membersihkan data produksi.'
            );
        }

        // 🔐 Verifikasi password owner (re-auth) — lapis terakhir sebelum hapus.
        $password = (string) $request->input('confirm_password');
        if ($password === '' || !\Illuminate\Support\Facades\Hash::check($password, (string) $request->user()->password)) {
            return back()->with('error', 'Password owner salah. Pembersihan dibatalkan.');
        }

        // 🛡️ BACKUP DB DULU — wajib. Kalau backup gagal, pembersihan DIBATALKAN.
        $backupRel = $this->backupDatabaseBeforeClean();
        if ($backupRel === null) {
            return back()->with('error',
                'Backup database gagal / tidak didukung (butuh SQLite). Pembersihan dibatalkan demi keamanan.'
            );
        }

        // Source type mutasi & jurnal milik alur produksi (lihat JournalService::SRC_*)
        $prodSources = [
            'cutting_job', 'cutting_job_void', 'cutting_job_sisa', 'cutting_job_scrap', 'cutting_job_wage',
            'cutting_qc_adjust_in', 'cutting_qc_adjust_out', 'cutting_reject', 'cutting_wip',
            // ✅ Reversal/void QC cutting — WAJIB ikut dihapus. Kalau tertinggal (originalnya
            // 'cutting_wip' terhapus di atas), mutasi ini jadi yatim & bikin saldo WIP-CUT negatif.
            'cutting_qc_void',
            'sewing_qc_in', 'sewing_qc_out', 'sewing_qc_reject',
            'sewing_reject_rework_ok', 'sewing_return_ok', 'sewing_return_reject',
            'sewing_pickup_wage',
            'sewing_pickup_supply', 'sewing_pickup_supply_followup',
            'sewing_pickup_supply_void', 'sewing_pickup_supply_void_line',
            'finishing_bom', 'finishing_job', 'finishing_qc_in_fg', 'finishing_qc_out',
            'finishing_qc_reject', 'finishing_reject_convert', 'finishing_repair',
            'production_movement', 'wip_fin_adjustment',
            // WIP normalize / cleanup beserta void-nya (agar tak menyisakan saldo yatim).
            'wip_normalization', 'wip_normalization_reversal',
            'wip_cleanup', 'wip_cleanup_void',
            \App\Models\CuttingJob::class,
            \App\Models\SewingPickup::class,
            'App\\Models\\SewingReturn',
            'App\\Models\\FinishingJob',
            'App\\Models\\PackingJob',
        ];
        $prodSources = array_values(array_filter($prodSources));

        // Tabel transaksi produksi (urutan: child dulu)
        $prodTables = [
            'qc_results',
            'sewing_return_lines', 'sewing_returns',
            'sewing_pickup_line_supply_lines', 'sewing_pickup_supply_lines',
            'sewing_pickup_lines', 'sewing_pickups',
            'cutting_job_lots', 'cutting_job_bundles', 'cutting_jobs',
            'finishing_repair_lines', 'finishing_repairs',
            'finishing_job_lines', 'finishing_jobs',
            'packing_job_lines', 'packing_jobs',
            'production_issue_lines', 'production_issues',
            'production_receipt_lines', 'production_receipts',
            'production_order_lines', 'production_orders',
            'production_movements', 'production_activities',
            'wip_fin_adjustment_lines', 'wip_fin_adjustments',
            'wip_opname_lines', 'wip_opname_periods',
        ];

        $prodAdjRefs = [
            \App\Models\SewingPickup::class,
            \App\Models\CuttingJob::class,
            'App\\Models\\SewingReturn',
            'App\\Models\\FinishingJob',
        ];

        $deleted = [];

        DB::transaction(function () use ($prodSources, $prodTables, $prodAdjRefs, &$deleted) {
            // 1) Adjustment yang lahir dari dokumen produksi (beserta mutasi & jurnalnya)
            $prodAdjIds = DB::table('inventory_adjustments')
                ->whereIn('reference_type', $prodAdjRefs)
                ->pluck('id');

            if ($prodAdjIds->isNotEmpty()) {
                $deleted['mutasi adjustment produksi'] = DB::table('inventory_mutations')
                    ->where('source_type', \App\Models\InventoryAdjustment::class)
                    ->whereIn('source_id', $prodAdjIds)->delete();

                $adjJournalIds = DB::table('journals')
                    ->where('source_type', 'inventory_adjustment')
                    ->whereIn('source_id', $prodAdjIds)->pluck('id');
                DB::table('journal_lines')->whereIn('journal_id', $adjJournalIds)->delete();
                DB::table('journals')->whereIn('id', $adjJournalIds)->delete();

                DB::table('inventory_adjustment_lines')->whereIn('inventory_adjustment_id', $prodAdjIds)->delete();
                $deleted['adjustment produksi'] = DB::table('inventory_adjustments')->whereIn('id', $prodAdjIds)->delete();
            }

            // 2) Jurnal produksi
            $journalIds = DB::table('journals')->whereIn('source_type', $prodSources)->pluck('id');
            $deleted['baris jurnal'] = DB::table('journal_lines')->whereIn('journal_id', $journalIds)->delete();
            $deleted['jurnal'] = DB::table('journals')->whereIn('id', $journalIds)->delete();

            // 3) Mutasi inventory produksi
            $deleted['mutasi inventory'] = DB::table('inventory_mutations')->whereIn('source_type', $prodSources)->delete();

            // 4) Tabel transaksi produksi
            foreach ($prodTables as $table) {
                if (!\Illuminate\Support\Facades\Schema::hasTable($table)) {
                    continue;
                }
                $count = DB::table($table)->delete();
                if ($count > 0) {
                    $deleted[$table] = $count;
                }
            }

            // 4b) Jaring pengaman: hapus mutasi yatim yang masih menunjuk bundle cutting
            //     yang sudah terhapus di langkah 4 (mis. cutting_qc_void yang originalnya
            //     ikut terhapus). Tanpa ini, recompute di langkah 5 meninggalkan saldo
            //     WIP-CUT negatif yang memblok pickup jahit.
            $deleted['mutasi yatim (bundle terhapus)'] = DB::table('inventory_mutations')
                ->whereNotNull('cutting_job_bundle_id')
                ->whereNotIn('cutting_job_bundle_id', function ($q) {
                    $q->select('id')->from('cutting_job_bundles');
                })
                ->delete();

            // 5) Recompute stok gudang dari mutasi tersisa
            DB::statement("
                UPDATE inventory_stocks SET qty = COALESCE(
                    (SELECT SUM(m.qty_change) FROM inventory_mutations m
                     WHERE m.warehouse_id = inventory_stocks.warehouse_id
                       AND m.item_id = inventory_stocks.item_id), 0)
            ");

            // 6) Recompute saldo & cost lot dari mutasi tersisa
            DB::statement("
                UPDATE lots SET
                    qty_onhand = COALESCE((SELECT SUM(m.qty_change) FROM inventory_mutations m WHERE m.lot_id = lots.id), 0),
                    total_cost = COALESCE((SELECT SUM(COALESCE(m.total_cost,0)) FROM inventory_mutations m WHERE m.lot_id = lots.id), 0)
            ");
            DB::statement("
                UPDATE lots SET avg_cost = CASE WHEN qty_onhand > 0.000001 THEN total_cost / qty_onhand ELSE 0 END
            ");
        });

        $summary = collect($deleted)
            ->filter(fn($v) => $v > 0)
            ->map(fn($v, $k) => "{$k}: {$v}")
            ->implode(', ');

        Log::warning('Data produksi dibersihkan oleh ' . $request->user()->name . ' (backup: ' . $backupRel . ')', $deleted);

        // Audit trail (tabel production_logs) — aman walau tabel belum di-migrate.
        \App\Models\ProductionLog::record(
            event: 'clean_production',
            summary: 'Bersihkan data produksi oleh ' . $request->user()->name . '. '
                . ($summary !== '' ? "Terhapus — {$summary}." : 'Tidak ada data yang dihapus.')
                . ' Backup: ' . $backupRel,
            meta: ['deleted' => $deleted, 'backup' => $backupRel],
        );

        return redirect()
            ->route('production.cutting_jobs.index')
            ->with('success', 'Data produksi berhasil dibersihkan & stok dihitung ulang. '
                . ($summary !== '' ? "Terhapus — {$summary}. " : 'Tidak ada data yang perlu dihapus. ')
                . '📦 Backup tersimpan: ' . $backupRel);
    }

    /**
     * Backup DB (SQLite) sebelum pembersihan. Return path relatif untuk
     * ditampilkan, atau null jika gagal / bukan SQLite (pemanggil membatalkan).
     * Menyisakan maksimal 30 file backup_*.sqlite terbaru.
     */
    protected function backupDatabaseBeforeClean(): ?string
    {
        try {
            $conn = config('database.default');
            if ($conn !== 'sqlite') {
                return null;
            }
            $dbPath = config('database.connections.sqlite.database');
            if (!$dbPath || !is_file($dbPath)) {
                return null;
            }

            $backupDir = storage_path('backups');
            if (!is_dir($backupDir)) {
                @mkdir($backupDir, 0755, true);
            }

            $name = 'backup_before_clean_prod_' . now()->format('Ymd_His') . '.sqlite';
            $target = $backupDir . DIRECTORY_SEPARATOR . $name;

            if (!@copy($dbPath, $target) || !is_file($target)) {
                return null;
            }

            // Batasi jumlah backup (maks 30 terbaru).
            $files = glob($backupDir . DIRECTORY_SEPARATOR . 'backup_*.sqlite') ?: [];
            usort($files, fn ($a, $b) => filemtime($b) <=> filemtime($a));
            foreach (array_slice($files, 30) as $old) {
                @unlink($old);
            }

            return 'storage/backups/' . $name;
        } catch (\Throwable $e) {
            \Log::error('Backup sebelum bersihkan produksi gagal: ' . $e->getMessage());
            return null;
        }
    }

    /**
     * List Cutting Job.
     */
    public function index(Request $request)
    {
        $q = CuttingJob::query()
            ->with([
                'warehouse',
                'lot.item',
                'lots',
                'fabricItem',
                'bundles.finishedItem',
                'operator',
            ])
            ->withCount('bundles')
            ->withSum('bundles', 'qty_pcs')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->status);
        }

        if ($request->filled('warehouse_id')) {
            $q->where('warehouse_id', $request->warehouse_id);
        }

        $jobs = $q->paginate(20)->withQueryString();
        $warehouses = Warehouse::orderBy('code')->get();

        // KPI counts — selalu dari seluruh data, tanpa filter
        $kpis = CuttingJob::query()
            ->selectRaw('status, count(*) as cnt')
            ->groupBy('status')
            ->pluck('cnt', 'status');

        return view('production.cutting_jobs.index', [
            'jobs'       => $jobs,
            'warehouses' => $warehouses,
            'filters'    => $request->only(['status', 'warehouse_id']),
            'kpis'       => $kpis,
        ]);
    }

    /**
     * Form Cutting Job - versi MEDIUM:
     * - User pilih item kain.
     * - Centang beberapa LOT (multi-LOT) dari gudang RM.
     * - Bundles punya lot_id masing-masing (dropdown hanya LOT yang dicentang).
     */
    public function create(Request $request)
    {
        // Blok jika ada WIP opname aktif
        if (WipOpnamePeriod::cutting()->active()->exists()) {
            return redirect()->route('production.wip_opname.index')
                ->with('error', 'Transaksi cutting dibekukan — sedang ada WIP opname berjalan. Selesaikan opname terlebih dahulu.');
        }

        // 1️⃣ Cari gudang RM (wajib ada, konfigurasi awal di warehouses)
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');

        if (!$rmWarehouseId) {
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi di tabel warehouses (code = RM).');
        }

        // 2️⃣ Ambil LOT di gudang RM yang masih ada saldo (> 0)
        //    LOT dengan saldo kurang dari kebutuhan BOM tetap bisa menyebabkan minus di RM.
        $lotStocks = $this->availableCuttingLotStocks($rmWarehouseId);
        $lotSupplierMap = $this->lotSupplierMap($lotStocks->pluck('lot_id')->filter()->unique()->values()->all());

        // 3️⃣ Data master item jadi (finished_good) untuk combobox di bundle
        $items = Item::query()
            ->select('id', 'code', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        // 4️⃣ Data master operator cutting
        $operators = Employee::query()
            ->select('id', 'code', 'name', 'role')
            ->whereIn('role', ['cutting', 'operating'])
            ->orderBy('code')
            ->get();

        // 5️⃣ Warehouse untuk header cutting job
        $warehouses = Warehouse::orderBy('code')->get();

        // 6️⃣ BOM data — untuk estimasi pemakaian kain di frontend & kalkulasi backend
        //    Hanya bahan baku utama (usage_stage=main_material) yang dikirim ke frontend.
        //    Format: { finished_item_id => { fabric_item_id => { qty, scrap_pct } } }
        $bomLines = \App\Models\ItemBomLine::query()
            ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
            ->whereHas('bom', fn($q) => $q->where('active', true))
            ->with('bom:id,item_id')
            ->get();

        $bomData = $bomLines
            ->groupBy(fn($line) => (int) $line->bom->item_id)
            ->map(fn($lines) => $lines->keyBy(fn($l) => (int) $l->material_item_id)
                ->map(fn($l) => ['qty' => (float) $l->qty, 'scrap_pct' => (float) $l->scrap_pct])
            );

        // URL edit BOM per finished item: { finishedItemId => editUrl }
        // URL quick-update line BOM: { finishedItemId => quickUrl }
        $bomEditUrls = $bomLines
            ->unique(fn($l) => (int) $l->bom->item_id)
            ->mapWithKeys(fn($l) => [
                (int) $l->bom->item_id => route('master.item_boms.edit', $l->bom->id),
            ]);

        $bomQuickUrls = $bomLines
            ->unique(fn($l) => (int) $l->bom->item_id)
            ->mapWithKeys(fn($l) => [
                (int) $l->bom->item_id => route('master.item_boms.quick_line', $l->bom->id),
            ]);

        // 7️⃣ Riwayat pemakaian kain AKTUAL per (finished_item × fabric_item)
        //    dari cutting job sebelumnya (exclude voided).
        //    Dipakai frontend untuk autofill "pemakaian terakhir" + info riwayat.
        //    Format: { finishedItemId => { fabricItemId => { kg_per_pcs, job_code, date, history: [..max 3] } } }
        $usageRows = \DB::table('cutting_job_bundles as b')
            ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
            ->join('lots as l', 'l.id', '=', 'b.lot_id')
            ->where('j.status', '!=', 'voided')
            ->where('b.qty_pcs', '>', 0)
            ->where('b.qty_used_fabric', '>', 0)
            ->orderByDesc('j.date')
            ->orderByDesc('b.id')
            ->limit(600)
            ->get([
                'b.finished_item_id',
                'l.item_id as fabric_item_id',
                'b.qty_pcs',
                'b.qty_used_fabric',
                'j.code as job_code',
                'j.date',
            ]);

        $lastUsage = [];
        foreach ($usageRows as $row) {
            $fid = (int) $row->finished_item_id;
            $mid = (int) $row->fabric_item_id;
            $kgPerPcs = round((float) $row->qty_used_fabric / max((float) $row->qty_pcs, 0.0001), 4);
            $dateStr = \Illuminate\Support\Carbon::parse($row->date)->format('d/m/y');

            if (!isset($lastUsage[$fid][$mid])) {
                $lastUsage[$fid][$mid] = [
                    'kg_per_pcs' => $kgPerPcs,
                    'job_code'   => $row->job_code,
                    'date'       => $dateStr,
                    'history'    => [],
                ];
            }

            $hist = &$lastUsage[$fid][$mid]['history'];
            $lastJob = end($hist);
            if (count($hist) < 3 && (!$lastJob || $lastJob['job_code'] !== $row->job_code)) {
                $hist[] = ['kg_per_pcs' => $kgPerPcs, 'job_code' => $row->job_code, 'date' => $dateStr];
            }
            unset($hist);
        }

        return view('production.cutting_jobs.create', [
            'lotStocks'      => $lotStocks,
            'lotSupplierMap' => $lotSupplierMap,
            'items'          => $items,
            'operators'      => $operators,
            'warehouses'     => $warehouses,
            'bomData'        => $bomData,
            'bomEditUrls'    => $bomEditUrls,
            'bomQuickUrls'   => $bomQuickUrls,
            'lastUsage'      => $lastUsage,
        ]);
    }

    public function liveLots(Request $request)
    {
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            return response()->json([
                'ok' => false,
                'message' => 'Warehouse RM belum dikonfigurasi.',
            ], 422);
        }

        $lotStocks = $this->availableCuttingLotStocks((int) $rmWarehouseId);
        $supplierMap = $this->lotSupplierMap($lotStocks->pluck('lot_id')->filter()->unique()->values()->all());

        $groups = $lotStocks
            ->groupBy(fn($row) => (int) $row->lot->item_id)
            ->map(function ($rows, $itemId) use ($supplierMap) {
                $first = $rows->first();
                $item = $first->lot->item;
                $warehouses = $rows->pluck('warehouse.code')->filter()->unique()->values();

                return [
                    'item_id' => (int) $itemId,
                    'item_code' => (string) $item->code,
                    'item_name' => (string) $item->name,
                    'total_balance' => (float) $rows->sum('qty_balance'),
                    'lot_count' => $rows->count(),
                    'warehouses' => $warehouses,
                    'lots' => $rows->map(function ($row) use ($supplierMap, $item) {
                        $lot = $row->lot;
                        $date = $lot?->purchased_at
                            ?? $lot?->purchase_date
                            ?? $lot?->received_at
                            ?? $lot?->created_at;

                        return [
                            'lot_id' => (int) $row->lot_id,
                            'lot_code' => (string) ($lot?->code ?? ('LOT#' . $row->lot_id)),
                            'item_id' => (int) $item->id,
                            'item_code' => (string) $item->code,
                            'balance' => (float) $row->qty_balance,
                            'warehouse_code' => (string) ($row->warehouse?->code ?? ''),
                            'supplier_name' => $supplierMap[$row->lot_id] ?? null,
                            'purchase_date' => $date ? \Illuminate\Support\Carbon::parse($date)->format('d/m/y') : null,
                        ];
                    })->values(),
                ];
            })
            ->sortBy('item_code')
            ->values();

        return response()->json([
            'ok' => true,
            'updated_at' => now()->format('H:i:s'),
            'groups' => $groups,
        ]);
    }

    /**
     * Form Edit Cutting Job:
     * (sementara masih versi lama, tapi sudah kompatibel dengan bundles yang punya lot_id)
     */

    public function edit(CuttingJob $cuttingJob)
    {
        $cuttingJob->load([
            'warehouse',
            'bundles.finishedItem.category',
            'bundles.operator',
            'lots.lot.item', // CuttingJobLot -> lot -> item
        ]);

        // 1) Warehouse RM
        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            throw new \RuntimeException('Warehouse RM belum dikonfigurasi di tabel warehouses (code = RM).');
        }

        // 2) list LOT available
        $lotStocks = $this->availableCuttingLotStocks($rmWarehouseId);
        $lotSupplierMap = $this->lotSupplierMap($lotStocks->pluck('lot_id')->filter()->unique()->values()->all());

        // 3) items FG (buat suggest API, tapi di blade kita pakai item-suggest fetch)
        $items = Item::query()
            ->select('id', 'code', 'name', 'item_category_id')
            ->where('type', 'finished_good')
            ->with(['category:id,code,name'])
            ->orderBy('code')
            ->get();

        // 4) operators
        $operators = Employee::query()
            ->select('id', 'code', 'name', 'role')
            ->whereIn('role', ['cutting', 'operating'])
            ->orderBy('code')
            ->get();

        // 5) warehouses
        $warehouses = Warehouse::orderBy('code')->get();

        // 6) BOM data
        $bomLines = \App\Models\ItemBomLine::query()
            ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
            ->whereHas('bom', fn($q) => $q->where('active', true))
            ->with('bom:id,item_id')
            ->get();

        $bomData = $bomLines
            ->groupBy(fn($line) => (int) $line->bom->item_id)
            ->map(fn($lines) => $lines->keyBy(fn($l) => (int) $l->material_item_id)
                ->map(fn($l) => ['qty' => (float) $l->qty, 'scrap_pct' => (float) $l->scrap_pct])
            );

        $bomEditUrls = $bomLines
            ->unique(fn($l) => (int) $l->bom->item_id)
            ->mapWithKeys(fn($l) => [
                (int) $l->bom->item_id => route('master.item_boms.edit', $l->bom->id),
            ]);

        $bomQuickUrls = $bomLines
            ->unique(fn($l) => (int) $l->bom->item_id)
            ->mapWithKeys(fn($l) => [
                (int) $l->bom->item_id => route('master.item_boms.quick_line', $l->bom->id),
            ]);

        // 7) Riwayat pemakaian kain
        $usageRows = \DB::table('cutting_job_bundles as b')
            ->join('cutting_jobs as j', 'j.id', '=', 'b.cutting_job_id')
            ->join('lots as l', 'l.id', '=', 'b.lot_id')
            ->where('j.status', '!=', 'voided')
            ->where('b.qty_pcs', '>', 0)
            ->where('b.qty_used_fabric', '>', 0)
            ->orderByDesc('j.date')
            ->orderByDesc('b.id')
            ->limit(600)
            ->get([
                'b.finished_item_id',
                'l.item_id as fabric_item_id',
                'b.qty_pcs',
                'b.qty_used_fabric',
                'j.code as job_code',
                'j.date',
            ]);

        $lastUsage = [];
        foreach ($usageRows as $row) {
            $fid = (int) $row->finished_item_id;
            $mid = (int) $row->fabric_item_id;
            $kgPerPcs = round((float) $row->qty_used_fabric / max((float) $row->qty_pcs, 0.0001), 4);
            $dateStr = \Illuminate\Support\Carbon::parse($row->date)->format('d/m/y');

            if (!isset($lastUsage[$fid][$mid])) {
                $lastUsage[$fid][$mid] = [
                    'kg_per_pcs' => $kgPerPcs,
                    'job_code'   => $row->job_code,
                    'date'       => $dateStr,
                    'history'    => [],
                ];
            }

            $hist = &$lastUsage[$fid][$mid]['history'];
            $lastJob = end($hist);
            if (count($hist) < 3 && (!$lastJob || $lastJob['job_code'] !== $row->job_code)) {
                $hist[] = ['kg_per_pcs' => $kgPerPcs, 'job_code' => $row->job_code, 'date' => $dateStr];
            }
            unset($hist);
        }

        // 8) selected lots: sumber utama CuttingJobLot
        $selectedLotsExisting = $cuttingJob->lots
            ->pluck('lot_id')
            ->filter()
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        // fallback job lama: derive dari bundles
        if (empty($selectedLotsExisting)) {
            $selectedLotsExisting = $cuttingJob->bundles
                ->pluck('lot_id')
                ->filter()
                ->map(fn($id) => (int) $id)
                ->unique()
                ->values()
                ->all();
        }

        // ringkasan LOT terkunci (kalau pivot ada)
        $selectedLotSummaries = $cuttingJob->lots
            ->filter(fn($cjLot) => !empty($cjLot->lot_id))
            ->map(function ($cjLot) {
                return [
                    'lot_id' => (int) $cjLot->lot_id,
                    'code' => $cjLot->lot?->code ?? ('LOT#' . $cjLot->lot_id),
                    'item_code' => $cjLot->lot?->item?->code ?? '-',
                    'item_name' => $cjLot->lot?->item?->name ?? '-',
                    'planned' => (float) $cjLot->planned_fabric_qty,
                    'used' => (float) $cjLot->effective_used_qty, // accessor kamu
                ];
            })
            ->values()
            ->all();

        // rows bundles (safe)
        $oldBundles = old('bundles');
        if ($oldBundles) {
            $rows = $oldBundles;
        } else {
            $rows = $cuttingJob->bundles->map(function ($b) {
                $fi = $b->finishedItem;
                return [
                    'id' => $b->id,
                    'bundle_no' => $b->bundle_no,
                    'lot_id' => $b->lot_id,
                    'finished_item_id' => $b->finished_item_id,
                    'finished_item_code' => $fi?->code,
                    'finished_item_name' => $fi?->name,
                    'item_category_id' => $fi?->item_category_id,
                    'qty_pcs' => (int) ($b->qty_pcs ?? 0),
                    'qty_used_fabric' => (float) ($b->qty_used_fabric ?? 0),
                    'notes' => $b->notes ?? '',
                ];
            })->values()->all();

            if (empty($rows)) {
                $rows[] = [
                    'id' => null,
                    'bundle_no' => 1,
                    'lot_id' => null,
                    'finished_item_id' => null,
                    'finished_item_code' => null,
                    'finished_item_name' => null,
                    'item_category_id' => null,
                    'qty_pcs' => null,
                    'qty_used_fabric' => 0,
                    'notes' => '',
                ];
            }
        }

        $lotBalance = (float) $cuttingJob->bundles->sum('qty_used_fabric');

        return view('production.cutting_jobs.edit', [
            'job' => $cuttingJob,
            'lotStocks' => $lotStocks,
            'lotSupplierMap' => $lotSupplierMap,
            'items' => $items,
            'operators' => $operators,
            'warehouses' => $warehouses,

            'rows' => $rows,
            'selectedLotsExisting' => $selectedLotsExisting,
            'selectedLotSummaries' => $selectedLotSummaries,

            'lotBalance' => $lotBalance,
            'bomData' => $bomData,
            'bomEditUrls' => $bomEditUrls,
            'bomQuickUrls' => $bomQuickUrls,
            'lastUsage' => $lastUsage,
        ]);
    }

    /**
     * Simpan Cutting Job + bundles (versi medium, multi-LOT).
     *
     * - LOT di level header (lot_id) dibuat optional (bisa diisi LOT pertama).
     * - LOT utama untuk stok ada di cutting_job_bundles.lot_id.
     */
    // public function store(Request $request)
    // {
    //     $validated = $request->validate([
    //         'date' => ['required', 'date'],
    //         'warehouse_id' => ['required', 'exists:warehouses,id'],
    //         'lot_id' => ['nullable', 'integer', 'exists:lots,id'], // header lot opsional
    //         'fabric_item_id' => ['required', 'integer', 'exists:items,id'],

    //         'operator_id' => ['required', 'exists:employees,id'],
    //         'notes' => ['nullable', 'string'],

    //         // LOT yang dipakai (sudah di-hidden dari _form multi-LOT)
    //         'selected_lots' => ['required', 'array', 'min:1'],
    //         'selected_lots.*' => ['integer', 'exists:lots,id'],

    //         // Bundles
    //         'bundles' => ['required', 'array', 'min:1'],
    //         'bundles.*.id' => ['nullable', 'integer'],
    //         'bundles.*.bundle_no' => ['nullable', 'integer'],
    //         'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
    //         'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
    //         'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
    //         'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
    //         'bundles.*.notes' => ['nullable', 'string'],
    //     ], [
    //         'fabric_item_id.required' => 'Item kain wajib dipilih.',
    //         'operator_id.required' => 'Operator cutting wajib dipilih.',
    //         'selected_lots.required' => 'Minimal satu LOT harus dipilih.',
    //         'bundles.*.finished_item_id.required' => 'Item jadi pada setiap baris wajib diisi.',
    //         'bundles.*.qty_pcs.required' => 'Qty pcs pada setiap baris wajib diisi.',
    //     ]);

    //     // ================
    //     // 1) LOT TERPILIH
    //     // ================
    //     $selectedLotIds = collect($validated['selected_lots'] ?? [])
    //         ->map(fn($id) => (int) $id)
    //         ->values()
    //         ->all();

    //     if (empty($selectedLotIds)) {
    //         return back()
    //             ->withErrors(['selected_lots' => 'Minimal satu LOT harus dipilih.'])
    //             ->withInput();
    //     }

    //     $warehouseId = (int) $validated['warehouse_id'];
    //     $fabricItemId = (int) $validated['fabric_item_id'];

    //     // ==========================================
    //     // 2) HITUNG SALDO PER LOT + TOTAL SALDO
    //     // ==========================================
    //     $lotBalances = [];
    //     $totalLotBalance = 0.0;

    //     foreach ($selectedLotIds as $lotId) {
    //         // pakai InventoryService supaya ngikut semua mutasi (GRN, cutting, dsb)
    //         $saldo = (float) $this->inventory->getLotBalance(
    //             warehouseId: $warehouseId,
    //             itemId: $fabricItemId,
    //             lotId: $lotId,
    //         );

    //         // jaga-jaga kalau minus → anggap 0
    //         if ($saldo < 0) {
    //             $saldo = 0.0;
    //         }

    //         $lotBalances[$lotId] = $saldo;
    //         $totalLotBalance += $saldo;
    //     }

    //     if ($totalLotBalance <= 0.000001) {
    //         return back()
    //             ->withErrors(['selected_lots' => 'Saldo kain di LOT yang dipilih sudah habis / 0.'])
    //             ->withInput();
    //     }

    //     // LOT utama (untuk header) → pilih LOT pertama yang masih ada saldo, kalau nggak ada ya pakai index 0
    //     $primaryLotId = collect($selectedLotIds)
    //         ->first(fn($id) => ($lotBalances[$id] ?? 0) > 0) ?? $selectedLotIds[0];

    //     // =========================
    //     // 3) FILTER BUNDLE VALID
    //     // =========================
    //     $bundles = $validated['bundles'] ?? [];
    //     $validBundles = [];

    //     foreach ($bundles as $row) {
    //         $qty = (float) ($row['qty_pcs'] ?? 0);

    //         if (!empty($row['finished_item_id']) && $qty > 0) {
    //             // Versi "medium" dulu:
    //             // - semua bundle diarahkan ke LOT utama (supaya compatible dengan sistem sekarang)
    //             // - nanti kalau mau advanced multi-LOT per bundle, tinggal ganti di sini
    //             $row['lot_id'] = $primaryLotId;

    //             $validBundles[] = $row;
    //         }
    //     }

    //     if (count($validBundles) === 0) {
    //         return back()
    //             ->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item & qty pcs > 0.'])
    //             ->withInput();
    //     }

    //     // =========================
    //     // 4) HEADER lot_id & qty_used_fabric
    //     // =========================

    //     // Kalau header lot_id kosong → pakai LOT utama
    //     if (empty($validated['lot_id']) && $primaryLotId) {
    //         $validated['lot_id'] = $primaryLotId;
    //     }

    //     // Hitung qty_used_fabric per baris (estimasi, TOTAL kain dibagi jumlah bundle valid)
    //     $countValid = count($validBundles);
    //     $perRow = ($countValid > 0 && $totalLotBalance > 0)
    //     ? round($totalLotBalance / $countValid, 2)
    //     : 0.0;

    //     foreach ($validBundles as $i => $row) {
    //         $qty = (float) ($row['qty_pcs'] ?? 0);

    //         if (!empty($row['finished_item_id']) && $qty > 0 && $perRow > 0) {
    //             $validBundles[$i]['qty_used_fabric'] = $perRow;
    //         } else {
    //             $validBundles[$i]['qty_used_fabric'] = 0;
    //         }
    //     }

    //     $validated['bundles'] = $validBundles;

    //     // selected_lots tidak dipakai di CuttingService
    //     unset($validated['selected_lots']);

    //     // =========================
    //     // 5) CREATE JOB
    //     // =========================
    //     $job = $this->cutting->create($validated);

    //     // =========================
    //     // 6) SIMPAN PIVOT LOTS
    //     // =========================
    //     foreach ($selectedLotIds as $lotId) {
    //         $saldoLot = $lotBalances[$lotId] ?? 0.0;

    //         // kalau saldo 0, skip saja
    //         if ($saldoLot <= 0.000001) {
    //             continue;
    //         }

    //         CuttingJobLot::create([
    //             'cutting_job_id' => $job->id,
    //             'lot_id' => $lotId,
    //             // 🔥 sekarang planned_fabric_qty = SALDO REAL per LOT,
    //             // bukan rata-rata / dibagi sama rata.
    //             'planned_fabric_qty' => $saldoLot,
    //         ]);
    //     }

    //     return redirect()
    //         ->route('production.cutting_jobs.show', $job)
    //         ->with('success', 'Cutting job berhasil dibuat.');
    // }

    public function store(Request $request)
    {
        // Blok jika ada WIP opname aktif
        if (WipOpnamePeriod::cutting()->active()->exists()) {
            return redirect()->route('production.wip_opname.index')
                ->with('error', 'Transaksi cutting dibekukan — sedang ada WIP opname berjalan.');
        }

        $this->resolveTypedFinishedItems($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'], // header lot opsional
            'fabric_item_id' => ['required', 'integer', 'exists:items,id'],

            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            // LOT yang dipakai (hasil centang di kartu LOT)
            'selected_lots' => ['required', 'array', 'min:1'],
            'selected_lots.*' => ['integer', 'exists:lots,id'],

            // Bundles
            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            // ⬇️ sekarang bundle WAJIB punya lot_id
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
            'bundles.*.notes' => ['nullable', 'string'],
        ], [
            'fabric_item_id.required' => 'Item kain wajib dipilih.',
            'operator_id.required' => 'Operator cutting wajib dipilih.',
            'selected_lots.required' => 'Minimal satu LOT harus dipilih.',
            'bundles.*.finished_item_id.required' => 'Item jadi pada setiap baris wajib diisi.',
            'bundles.*.qty_pcs.required' => 'Qty pcs pada setiap baris wajib diisi.',
            'bundles.*.lot_id.required' => 'LOT pada setiap baris bundle wajib dipilih.',
        ]);

        // ================
        // 1) LOT TERPILIH
        // ================
        $selectedLotIds = collect($validated['selected_lots'] ?? [])
            ->map(fn($id) => (int) $id)
            ->unique()
            ->values()
            ->all();

        if (empty($selectedLotIds)) {
            return back()
                ->withErrors(['selected_lots' => 'Minimal satu LOT harus dipilih.']);
        }

        $warehouseId = (int) $validated['warehouse_id'];
        $fabricItemId = (int) $validated['fabric_item_id'];

        // 1.a: ambil item_id per LOT (untuk keperluan hitung saldo)
        $lotItems = \App\Models\Lot::query()
            ->whereIn('id', $selectedLotIds)
            ->pluck('item_id', 'id'); // [lot_id => item_id]

        // ==========================================
        // 2) HITUNG SALDO PER LOT (info saja, tidak memblok jika 0)
        // ==========================================
        $lotBalances = [];
        $totalLotBalance = 0.0;

        foreach ($selectedLotIds as $lotId) {
            $saldo = (float) $this->inventory->getLotBalance(
                warehouseId: $warehouseId,
                itemId: $fabricItemId,
                lotId: $lotId,
            );
            // Simpan saldo asli (bisa 0 atau negatif — tidak diblok)
            $lotBalances[$lotId] = max($saldo, 0.0);
            $totalLotBalance += max($saldo, 0.0);
        }

        // LOT utama → preferensi yang punya saldo; fallback ke LOT pertama
        $primaryLotId = collect($selectedLotIds)
            ->first(fn($id) => ($lotBalances[$id] ?? 0) > 0) ?? $selectedLotIds[0];

        // =========================
        // 3) FILTER BUNDLE VALID
        // =========================
        $bundles = $validated['bundles'] ?? [];
        $validBundles = [];
        $bundlesIndexByLot = []; // [lot_id => [index, ...]]

        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            $lotId = !empty($row['lot_id']) ? (int) $row['lot_id'] : 0;

            if (empty($row['finished_item_id']) || $qty <= 0 || !$lotId) {
                continue;
            }

            if (!in_array($lotId, $selectedLotIds, true)) {
                return back()
                    ->withErrors(['bundles' => 'LOT pada baris bundle harus termasuk LOT yang dipilih di atas.']);
            }

            $idx = count($validBundles);
            $validBundles[] = $row;
            $bundlesIndexByLot[$lotId] = $bundlesIndexByLot[$lotId] ?? [];
            $bundlesIndexByLot[$lotId][] = $idx;
        }

        if (count($validBundles) === 0) {
            return back()
                ->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item, LOT & qty pcs > 0.']);
        }

        // =========================
        // 4) HEADER lot_id
        // =========================
        if (empty($validated['lot_id']) && $primaryLotId) {
            $validated['lot_id'] = $primaryLotId;
        }

        // =======================================================
        // 5) HITUNG qty_used_fabric — PAKAI BOM jika tersedia,
        //    fallback ke distribusi saldo LOT jika tidak ada BOM.
        //    LOT dengan saldo 0 tetap diproses (boleh minus di RM).
        // =======================================================

        // Load active BOM lines untuk fabric item ini — hanya main_material, keyed by finished_item_id
        $bomLines = \App\Models\ItemBomLine::query()
            ->where('material_item_id', $fabricItemId)
            ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
            ->whereHas('bom', fn($q) => $q->where('active', true))
            ->with('bom:id,item_id')
            ->get()
            ->keyBy(fn($line) => (int) $line->bom->item_id);

        foreach ($bundlesIndexByLot as $lotId => $indexes) {
            $saldoLot = $lotBalances[$lotId] ?? 0.0;
            $countInLot = count($indexes);
            if ($countInLot <= 0) {
                continue;
            }

            // Hitung qty_used_fabric per baris
            // Prioritas: (1) user submit manual → (2) hitung dari BOM → (3) fallback saldo LOT
            $anyBom = false;
            foreach ($indexes as $idx) {
                $finishedItemId = (int) ($validBundles[$idx]['finished_item_id'] ?? 0);
                $qtyPcs         = (float) ($validBundles[$idx]['qty_pcs'] ?? 0);

                // Prioritas 1: user sudah isi qty_used_fabric manual di form
                $userFabric = (float) ($validBundles[$idx]['qty_used_fabric'] ?? 0);
                if ($userFabric > 0) {
                    // ✅ GUARD BOM: pemakaian tidak boleh melebihi standar BOM (+scrap).
                    //    Kalau realita memang lebih besar → update BOM dulu
                    //    (tombol "Update BOM" tersedia di form cutting).
                    $guardBomLine = $bomLines[$finishedItemId] ?? null;
                    if ($guardBomLine && $qtyPcs > 0) {
                        $maxByBom = $qtyPcs * (float) $guardBomLine->qty * (1 + (float) $guardBomLine->scrap_pct / 100);
                        if ($userFabric > $maxByBom + 0.0005) {
                            $itemCode = Item::whereKey($finishedItemId)->value('code') ?? ('#' . $finishedItemId);
                            throw ValidationException::withMessages([
                                'bundles' => sprintf(
                                    'Pemakaian kain %s = %s kg melebihi standar BOM (maks %s kg untuk %s pcs). '
                                    . 'Jika realita memang segitu, update BOM dulu lewat tombol "Update BOM" di form, lalu simpan ulang.',
                                    $itemCode,
                                    rtrim(rtrim(number_format($userFabric, 4, '.', ''), '0'), '.'),
                                    rtrim(rtrim(number_format($maxByBom, 4, '.', ''), '0'), '.'),
                                    rtrim(rtrim(number_format($qtyPcs, 2, '.', ''), '0'), '.')
                                ),
                            ]);
                        }
                    }

                    $anyBom = true; // ada nilai → skip fallback LOT
                    continue;       // nilai sudah ada di $validBundles[$idx], tidak perlu overwrite
                }

                // Prioritas 2: hitung dari BOM main_material
                $bomLine = $bomLines[$finishedItemId] ?? null;
                if ($bomLine && $qtyPcs > 0) {
                    $bomQty   = (float) $bomLine->qty;
                    $scrapPct = (float) $bomLine->scrap_pct;
                    $validBundles[$idx]['qty_used_fabric'] = round(
                        $qtyPcs * $bomQty * (1 + $scrapPct / 100),
                        4
                    );
                    $anyBom = true;
                }
            }

            if (!$anyBom) {
                // Fallback: distribusi saldo LOT secara merata
                // Jika saldo 0 → qty_used_fabric juga 0 (tidak ada deduction)
                $perRow = $saldoLot > 0 ? round($saldoLot / $countInLot, 2) : 0.0;
                $usedSoFar = 0.0;

                foreach ($indexes as $i => $idx) {
                    if ($i === $countInLot - 1) {
                        $validBundles[$idx]['qty_used_fabric'] = max($saldoLot - $usedSoFar, 0);
                    } else {
                        $validBundles[$idx]['qty_used_fabric'] = $perRow;
                        $usedSoFar += $perRow;
                    }
                }
            }
        }

        $validated['bundles'] = $validBundles;
        unset($validated['selected_lots']);

        // Hitung total planned fabric per LOT dari bundle yang sudah dihitung
        $fabricByLot = [];
        foreach ($validBundles as $b) {
            $lotId = (int) ($b['lot_id'] ?? 0);
            $fabricByLot[$lotId] = ($fabricByLot[$lotId] ?? 0.0) + (float) ($b['qty_used_fabric'] ?? 0);
        }

        $devRollback = $request->boolean('dev_rollback') && !app()->isProduction();
        if ($devRollback) {
            DB::beginTransaction();
        }

        // =========================
        // 6) CREATE JOB
        // =========================
        try {
            $job = $this->cutting->create($validated);

            // =========================
            // 7) SIMPAN PIVOT LOTS
            //    Semua LOT terpilih disimpan, termasuk yang saldo 0
            // =========================
            foreach ($selectedLotIds as $lotId) {
                \App\Models\CuttingJobLot::create([
                    'cutting_job_id'    => $job->id,
                    'lot_id'            => $lotId,
                    // planned_fabric_qty = total BOM/saldo yang direncanakan dari LOT ini
                    'planned_fabric_qty' => $fabricByLot[$lotId] ?? 0.0,
                ]);
            }

            // ✅ Sinkron used_fabric_qty per LOT dari total bundle —
            //    tanpa ini form "Catat Sisa Kain" tidak pernah muncul.
            $this->syncCuttingJobLotUsedFabric($job);

            // ✅ Lakukan pemotongan stok fisik kain (RM OUT) di sini,
            //    karena pivot CuttingJobLot baru saja dibuat.
            $this->cutting->reconsumeFabricFromLots($job);
        } catch (\RuntimeException $e) {
            if ($devRollback && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            return back()
                ->withErrors(['selected_lots' => $this->humanizeStockError($e->getMessage())]);
        } catch (\Throwable $e) {
            if ($devRollback && DB::transactionLevel() > 0) {
                DB::rollBack();
            }

            throw $e;
        }

        if ($devRollback) {
            $summary = [
                'code' => $job->code,
                'bundle_count' => $job->bundles()->count(),
                'qty_pcs' => (float) $job->bundles()->sum('qty_pcs'),
                'used_fabric' => (float) $job->bundles()->sum('qty_used_fabric'),
                'lot_count' => count($selectedLotIds),
            ];

            DB::rollBack();

            return back()
                
                ->with('dev_rollback_result', $summary)
                ->with('success', 'Mode Developer: simulasi cutting berhasil dan sudah di-rollback. Tidak ada data/stok yang berubah.');
        }

        try {
            $this->journal->postCuttingJob($job);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat jurnal cutting_job', [
                'cutting_job_id' => $job->id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $this->journal->postCuttingJobWage($job);
        } catch (\Throwable $e) {
            Log::error('Gagal membuat jurnal upah cutting_job', [
                'cutting_job_id' => $job->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil dibuat.');
    }

    /**
     * Update Cutting Job + bundles (manual per LOT).
     */
    public function update(Request $request, CuttingJob $cuttingJob)
    {
        $this->resolveTypedFinishedItems($request);

        $validated = $request->validate([
            'date' => ['required', 'date'],
            'warehouse_id' => ['required', 'exists:warehouses,id'],
            'lot_id' => ['nullable', 'integer', 'exists:lots,id'],
            // ✅ jangan required di edit: akan kita set dari LOT
            'fabric_item_id' => ['nullable', 'integer'],

            'operator_id' => ['required', 'exists:employees,id'],
            'notes' => ['nullable', 'string'],

            // ✅ wajib ada selected_lots[] (di blade sudah kita hidden)
            'selected_lots' => ['required', 'array', 'min:1'],
            'selected_lots.*' => ['integer', 'exists:lots,id'],

            'bundles' => ['required', 'array', 'min:1'],
            'bundles.*.id' => ['nullable', 'integer'],
            'bundles.*.bundle_no' => ['nullable', 'integer'],
            'bundles.*.lot_id' => ['required', 'integer', 'exists:lots,id'],
            'bundles.*.finished_item_id' => ['required', 'exists:items,id'],
            'bundles.*.item_category_id' => ['nullable', 'integer', 'exists:item_categories,id'],
            'bundles.*.qty_pcs' => ['required', 'numeric', 'min:0.01'],
            // ✅ boleh nullable karena akan dihitung ulang planned/lot dibagi baris
            'bundles.*.qty_used_fabric' => ['nullable', 'numeric', 'min:0'],
            'bundles.*.notes' => ['nullable', 'string'],
        ]);

        $selectedLotIds = collect($validated['selected_lots'])
            ->map(fn($id) => (int) $id)->unique()->values()->all();

        if (empty($selectedLotIds)) {
            return back()->withErrors(['selected_lots' => 'Minimal satu LOT harus dipilih.'])->withInput();
        }

        // ✅ fabric_item_id ambil dari LOT terpilih (bukan dari dropdown RM)
        $lotItems = \App\Models\Lot::query()
            ->whereIn('id', $selectedLotIds)
            ->pluck('item_id', 'id'); // [lot_id => item_id]

        if ($lotItems->isEmpty()) {
            return back()->withErrors(['selected_lots' => 'Data LOT tidak ditemukan.'])->withInput();
        }

        $fabricItemId = (int) ($lotItems->first() ?? 0);
        $validated['fabric_item_id'] = $fabricItemId;

        // valid bundles + pastikan lot_id bundle termasuk selected
        $bundles = $validated['bundles'] ?? [];
        $validBundles = [];
        $bundlesIndexByLot = [];

        foreach ($bundles as $row) {
            $qty = (float) ($row['qty_pcs'] ?? 0);
            $lotId = (int) ($row['lot_id'] ?? 0);

            if (empty($row['finished_item_id']) || $qty <= 0 || !$lotId) {
                continue;
            }

            if (!in_array($lotId, $selectedLotIds, true)) {
                return back()->withErrors(['bundles' => 'LOT pada baris bundle harus termasuk LOT yang dipilih di atas.'])->withInput();
            }

            $idx = count($validBundles);
            $validBundles[] = $row;
            $bundlesIndexByLot[$lotId] = $bundlesIndexByLot[$lotId] ?? [];
            $bundlesIndexByLot[$lotId][] = $idx;
        }

        if (count($validBundles) === 0) {
            return back()->withErrors(['bundles' => 'Minimal 1 baris bundle harus diisi dengan item, LOT & qty pcs > 0.'])->withInput();
        }

        // isi header lot_id kalau kosong
        if (empty($validated['lot_id'])) {
            $validated['lot_id'] = (int) ($validBundles[0]['lot_id'] ?? $selectedLotIds[0]);
        }

        // ✅ sumber planned per LOT:
        // - kalau ada pivot CuttingJobLot: pakai planned_fabric_qty
        // - kalau tidak ada: fallback pakai balance RM (optional, tapi di edit biasanya pivot sudah ada)
        $cuttingJob->loadMissing(['lots']); // relation CuttingJobLot
        $plannedMap = [];

        if ($cuttingJob->lots && $cuttingJob->lots->count()) {
            foreach ($cuttingJob->lots as $cjLot) {
                $plannedMap[(int) $cjLot->lot_id] = (float) $cjLot->planned_fabric_qty;
            }
        } else {
            // fallback: pakai saldo sekarang (kalau kamu mau)
            $warehouseId = (int) $validated['warehouse_id'];
            foreach ($selectedLotIds as $lotId) {
                $plannedMap[$lotId] = (float) $this->inventory->getLotBalance(
                    warehouseId: $warehouseId,
                    itemId: $fabricItemId,
                    lotId: $lotId
                );
            }
        }

        // ✅ HITUNG qty_used_fabric = planned per LOT dibagi baris valid per LOT (last row remainder)
        foreach ($bundlesIndexByLot as $lotId => $indexes) {
            $planned = (float) ($plannedMap[$lotId] ?? 0);

            if ($planned <= 0.000001) {
                return back()->withErrors(['bundles' => "Planned kain untuk LOT {$lotId} = 0. Cek CuttingJobLot / planned_fabric_qty."])->withInput();
            }

            $countInLot = count($indexes);
            $perRow = round($planned / $countInLot, 2);
            $usedSoFar = 0.0;

            foreach ($indexes as $i => $idx) {
                if ($i === $countInLot - 1) {
                    $validBundles[$idx]['qty_used_fabric'] = max($planned - $usedSoFar, 0);
                } else {
                    $validBundles[$idx]['qty_used_fabric'] = $perRow;
                    $usedSoFar += $perRow;
                }
            }
        }

        $validated['bundles'] = $validBundles;

        // Hitung total planned fabric per LOT dari bundle yang sudah dihitung (kayak di create)
        $fabricByLot = [];
        foreach ($validBundles as $b) {
            $lotId = (int) ($b['lot_id'] ?? 0);
            $fabricByLot[$lotId] = ($fabricByLot[$lotId] ?? 0.0) + (float) ($b['qty_used_fabric'] ?? 0);
        }

        // selected_lots tidak dipakai cutting service
        unset($validated['selected_lots']);

        try {
            DB::beginTransaction();

            // 1. Reverse stock lama
            $this->inventory->reverseBySource('cutting_job', $cuttingJob->id);

            // 2. Hapus referensi LOT lama
            $cuttingJob->lots()->delete();

            // 3. Update job & bundles
            $job = $this->cutting->update($validated, $cuttingJob);

            // 4. Buat pivot LOT baru
            foreach ($selectedLotIds as $lotId) {
                \App\Models\CuttingJobLot::create([
                    'cutting_job_id'     => $job->id,
                    'lot_id'             => $lotId,
                    'planned_fabric_qty' => $fabricByLot[$lotId] ?? 0.0,
                ]);
            }

            // 5. Potong ulang stok
            $this->cutting->reconsumeFabricFromLots($job);

            // 6. Sinkron used_fabric_qty per LOT (ini buat form Sisa Kain)
            $this->syncCuttingJobLotUsedFabric($job);

            DB::commit();
        } catch (\Throwable $e) {
            DB::rollBack();
            // Optionally log error here
            return back()->withErrors(['error' => 'Gagal menyimpan update: ' . $e->getMessage()])->withInput();
        }

        return redirect()
            ->route('production.cutting_jobs.show', $job)
            ->with('success', 'Cutting job berhasil diupdate.');
    }

    private function resolveTypedFinishedItems(Request $request): void
    {
        $bundles = $request->input('bundles', []);
        if (!is_array($bundles) || empty($bundles)) {
            return;
        }

        foreach ($bundles as $idx => $row) {
            if (!empty($row['finished_item_id'])) {
                continue;
            }

            $display = trim((string) ($row['finished_item_display'] ?? ''));
            if ($display === '') {
                continue;
            }

            $code = trim(str_replace('–', '—', $display));
            $code = trim(explode('—', $code)[0] ?? $code);
            $code = trim(preg_split('/\s+/', $code)[0] ?? $code);
            $code = strtoupper($code);

            if ($code === '') {
                continue;
            }

            $item = Item::query()
                ->where('type', 'finished_good')
                ->whereRaw('UPPER(code) = ?', [$code])
                ->first();

            if (!$item) {
                continue;
            }

            $bundles[$idx]['finished_item_id'] = $item->id;
            if (empty($bundles[$idx]['item_category_id'])) {
                $bundles[$idx]['item_category_id'] = $item->item_category_id;
            }
        }

        $request->merge(['bundles' => $bundles]);
    }

    private function onlyMainRawMaterialLots($lotStocks)
    {
        return $lotStocks
            ->filter(function ($row) {
                $item = $row->lot?->item;

                return $item && $item->role_code === ItemRole::RM;
            })
            ->values();
    }

    private function availableCuttingLotStocks(int $warehouseId)
    {
        return $this->onlyMainRawMaterialLots($this->inventory->getAvailableLots(
            warehouseId: $warehouseId,
            itemId: null,
            includeZeroBalance: false,
        ));
    }

    private function lotSupplierMap(array $lotIds): array
    {
        if (empty($lotIds)) {
            return [];
        }

        $firstGrnPerLot = \DB::table('inventory_mutations')
            ->select('lot_id', \DB::raw('MIN(source_id) as grn_id'))
            ->whereIn('lot_id', $lotIds)
            ->where('source_type', 'purchase_receipt')
            ->where('direction', 'in')
            ->groupBy('lot_id')
            ->get()
            ->keyBy('lot_id');

        $grnIds = $firstGrnPerLot->pluck('grn_id')->filter()->unique()->values()->all();
        if (empty($grnIds)) {
            return [];
        }

        $supplierByGrn = \DB::table('purchase_receipts')
            ->join('suppliers', 'purchase_receipts.supplier_id', '=', 'suppliers.id')
            ->whereIn('purchase_receipts.id', $grnIds)
            ->pluck('suppliers.name', 'purchase_receipts.id');

        $map = [];
        foreach ($firstGrnPerLot as $lotId => $row) {
            $supplierName = $supplierByGrn[$row->grn_id] ?? null;
            if ($supplierName) {
                $map[$lotId] = $supplierName;
            }
        }

        return $map;
    }

    private function capLotBalancesToOnHand(array $lotBalances, float $onHandQty): array
    {
        $total = array_sum($lotBalances);
        if ($total <= 0 || $onHandQty <= 0 || $total <= $onHandQty) {
            return $lotBalances;
        }

        $capped = [];
        $used = 0.0;
        $keys = array_keys($lotBalances);
        $lastKey = end($keys);

        foreach ($lotBalances as $lotId => $balance) {
            if ($lotId === $lastKey) {
                $capped[$lotId] = max(round($onHandQty - $used, 4), 0);
                continue;
            }

            $qty = round(((float) $balance / $total) * $onHandQty, 4);
            $capped[$lotId] = max($qty, 0);
            $used += $qty;
        }

        return $capped;
    }

    private function humanizeStockError(string $message): string
    {
        if (preg_match('/Stok tidak mencukupi untuk item\s+(\d+)\s+di gudang\s+(\d+)\.\s*Stok:\s*([0-9\.,]+),\s*mau keluar:\s*([0-9\.,]+)/i', $message, $m)) {
            return "Stok kain tidak cukup. Stok tersedia {$m[3]}, tetapi sistem mencoba memakai {$m[4]}. Refresh halaman lalu pilih LOT ulang.";
        }

        return 'Stok kain tidak cukup: ' . $message;
    }

    /**
     * Detail satu Cutting Job.
     */
    public function show(CuttingJob $cuttingJob)
    {
        // Self-healing: sinkron used_fabric_qty LOT dari bundles.
        // Menutup job lama yang tersimpan sebelum sync dipasang di store().
        if ($cuttingJob->status !== 'voided') {
            $this->syncCuttingJobLotUsedFabric($cuttingJob);
        }

        $cuttingJob->load([
            'warehouse',
            'lot.item',
            'lots.lot.item',
            'bundles.finishedItem',
            'bundles.operator',
            'bundles.qcResults' => function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            },
        ]);

        $hasQcCutting = $cuttingJob->bundles()
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', QcResult::STAGE_CUTTING);
            })
            ->exists();

        // Target update scrap% BOM: item jadi di job ini yang punya BOM aktif
        // dengan line bahan utama = kain job ini.
        // { finished_item_id => [item_code, bom_scrap_pct, bom_qty, quick_url] }
        $bomScrapTargets = [];
        $finishedItemIds = $cuttingJob->bundles->pluck('finished_item_id')->filter()->unique()->values();
        if ($finishedItemIds->isNotEmpty() && $cuttingJob->fabric_item_id) {
            $lines = \App\Models\ItemBomLine::query()
                ->where('material_item_id', (int) $cuttingJob->fabric_item_id)
                ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
                ->whereHas('bom', fn($q) => $q->where('active', true)->whereIn('item_id', $finishedItemIds))
                ->with(['bom.item:id,code'])
                ->get();

            foreach ($lines as $line) {
                $bomScrapTargets[(int) $line->bom->item_id] = [
                    'item_code' => $line->bom->item?->code ?? ('#' . $line->bom->item_id),
                    'scrap_pct' => (float) $line->scrap_pct,
                    'bom_qty'   => (float) $line->qty,
                    'quick_url' => route('master.item_boms.quick_line', $line->bom->id),
                ];
            }
        }

        return view('production.cutting_jobs.show', [
            'job' => $cuttingJob,
            'hasQcCutting' => $hasQcCutting,
            'bomScrapTargets' => $bomScrapTargets,
        ]);
    }

    /**
     * Void / rollback Cutting Job:
     * - Hanya owner
     * - Hanya status draft/cut (belum QC)
     * - Block jika ada sewing pickup atau WIP sudah diposting
     * - Reverse mutasi kain ke LOT semula
     * - Set status job & bundles → voided
     */
    public function void(Request $request, CuttingJob $cuttingJob)
    {
        // 1) Hanya owner
        if ((auth()->user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya Owner yang bisa melakukan void Cutting Job.');
        }

        // 2) Hanya boleh sebelum QC diinput (cut_sent_to_qc tetap ok selama belum ada QC result)
        $voidableStatuses = ['draft', 'cut', 'cut_sent_to_qc', 'sent_to_qc'];
        if (! in_array($cuttingJob->status, $voidableStatuses, true)) {
            return back()->with('error',
                'Cutting Job tidak bisa di-void. Status saat ini: ' . strtoupper($cuttingJob->status) . '. Void hanya bisa dilakukan sebelum QC diinput.'
            );
        }

        // 3) Block jika QC sudah diposting (wip_posted_at terisi)
        $cuttingJob->loadMissing('bundles');

        $hasWipPosted = $cuttingJob->bundles->contains(fn ($b) => ! empty($b->wip_posted_at));
        if ($hasWipPosted) {
            return back()->with('error',
                'Cutting Job tidak bisa di-void karena WIP sudah diposting ke gudang. Gunakan fitur Batalkan QC terlebih dahulu.'
            );
        }

        // 4) Block jika ada sewing pickup
        $hasSewingPickup = $cuttingJob->bundles->contains(fn ($b) => ((float) ($b->sewing_picked_qty ?? 0)) > 0);
        if ($hasSewingPickup) {
            return back()->with('error',
                'Cutting Job tidak bisa di-void karena sebagian bundle sudah diambil untuk jahit (Sewing Pickup).'
            );
        }

        DB::transaction(function () use ($cuttingJob) {
            // 5) Reverse mutasi kain — kembalikan saldo gudang (inventory_stocks)
            //    reverseBySource pakai affectLotCost=false, jadi lots.qty_onhand diurus terpisah di bawah.
            $outMutations = \App\Models\InventoryMutation::query()
                ->where('source_type', 'cutting_job')
                ->where('source_id', $cuttingJob->id)
                ->where('direction', 'out')
                ->whereNotNull('lot_id')
                ->get();

            $hasMutasi = $outMutations->isNotEmpty()
                || \App\Models\InventoryMutation::query()
                    ->where('source_type', 'cutting_job')
                    ->where('source_id', $cuttingJob->id)
                    ->exists();

            if ($hasMutasi) {
                $this->inventory->reverseBySource(
                    originalSourceTypes: ['cutting_job'],
                    originalSourceId:    $cuttingJob->id,
                    voidSourceType:      'cutting_job_void',
                    voidSourceId:        $cuttingJob->id,
                    notesPrefix:         "VOID {$cuttingJob->code}",
                    date:                now(),
                );
            }

            // 5b) Restore lots.qty_onhand per LOT yang terlibat
            //     (reverseBySource tidak update LOT karena affectLotCost=false)
            if ($outMutations->isNotEmpty()) {
                $lotRestore = [];
                foreach ($outMutations as $m) {
                    $lotId = (int) $m->lot_id;
                    $lotRestore[$lotId] = ($lotRestore[$lotId] ?? 0.0) + abs((float) $m->qty_change);
                }

                foreach ($lotRestore as $lotId => $restoreQty) {
                    $lot = \App\Models\Lot::lockForUpdate()->find($lotId);
                    if (! $lot || $restoreQty <= 0) {
                        continue;
                    }

                    $lot->qty_onhand = round((float) $lot->qty_onhand + $restoreQty, 4);
                    $lot->total_cost = round($lot->qty_onhand * (float) $lot->avg_cost, 4);

                    // Buka kembali LOT jika sempat tertutup
                    if ($lot->qty_onhand > 0 && $lot->status === 'closed') {
                        $lot->status = 'open';
                    }

                    $lot->save();
                }
            }

            // 6) Set status bundles → voided
            $cuttingJob->bundles()->update(['status' => 'voided']);

            // 7) Set status job → voided
            $cuttingJob->update(['status' => 'voided']);
        });

        try {
            $this->journal->voidBySource(JournalService::SRC_CUTTING_JOB, (int) $cuttingJob->id, "VOID Cutting Job {$cuttingJob->code}");
        } catch (\Throwable $e) {
            Log::warning('Gagal void jurnal cutting_job', [
                'cutting_job_id' => $cuttingJob->id,
                'message' => $e->getMessage(),
            ]);
        }

        try {
            $this->journal->voidBySource(JournalService::SRC_CUTTING_JOB_WAGE, (int) $cuttingJob->id, "VOID Upah Cutting Job {$cuttingJob->code}");
        } catch (\Throwable $e) {
            Log::warning('Gagal void jurnal upah cutting_job', [
                'cutting_job_id' => $cuttingJob->id,
                'message' => $e->getMessage(),
            ]);
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', "Cutting Job {$cuttingJob->code} berhasil di-void. Stok kain sudah dikembalikan ke LOT.");
    }

    /**
     * Kembalikan ke Bahan Baku — orkestrator satu klik.
     *
     * Menggabungkan dua langkah yang sudah ada, TANPA logika stok baru:
     *   1) Batalkan QC (QcService::cancelCuttingQc) bila QC sudah diposting
     *      → WIP-CUT dibalik + jurnal cutting_wip di-void.
     *   2) Void Cutting Job (method void() yang sudah ada)
     *      → kain balik ke RM/LOT + jurnal cutting_job & upah di-void.
     *
     * Diblok kalau bundle sudah ditarik jahit (fabric sudah di sewing).
     */
    public function revertToRaw(Request $request, CuttingJob $cuttingJob)
    {
        if ((auth()->user()->role ?? null) !== 'owner') {
            return back()->with('error', 'Hanya Owner yang bisa mengembalikan cutting ke bahan baku.');
        }

        $cuttingJob->loadMissing('bundles');

        // Fabric yang sudah ditarik ke jahit tidak bisa di-"un-cut".
        $hasSewingPickup = $cuttingJob->bundles->contains(fn ($b) => ((float) ($b->sewing_picked_qty ?? 0)) > 0);
        if ($hasSewingPickup) {
            return back()->with('error',
                'Tidak bisa dikembalikan ke bahan baku: sebagian bundle sudah ditarik untuk jahit. Batalkan pickup-nya dulu (WIP Cleanup → Batalkan).'
            );
        }

        // 1) Batalkan QC dulu jika QC sudah diposting.
        $hasQc = \App\Models\QcResult::query()
            ->where('stage', \App\Models\QcResult::STAGE_CUTTING)
            ->where('cutting_job_id', $cuttingJob->id)
            ->exists();
        $hasWipPosted = $cuttingJob->bundles->contains(fn ($b) => ! empty($b->wip_posted_at));

        if ($hasQc || $hasWipPosted) {
            try {
                app(\App\Services\Production\QcService::class)->cancelCuttingQc($cuttingJob);
            } catch (\Throwable $e) {
                return back()->with('error', 'Gagal membatalkan QC saat kembalikan ke bahan baku: ' . $e->getMessage());
            }
        }

        // 2) Void cutting job (reuse). Job sudah fresh: WIP cleared, status voidable.
        $response = $this->void($request, $cuttingJob->fresh());

        // Pesan lebih jelas untuk aksi gabungan (tanpa mengubah logika void).
        if ($response instanceof \Illuminate\Http\RedirectResponse
            && $cuttingJob->fresh()->status === 'voided') {
            $response->with('success', "Cutting Job {$cuttingJob->code} dikembalikan ke bahan baku — WIP dibatalkan, kain balik ke RM/LOT, jurnal ter-void.");
        }

        return $response;
    }

    public function sendToQc(CuttingJob $cuttingJob)
    {
        $hasQcCutting = $cuttingJob->bundles()
            ->whereHas('qcResults', function ($q) {
                $q->where('stage', 'cutting');
            })
            ->exists();

        if (!$hasQcCutting) {
            $cuttingJob->update([
                'status' => 'sent_to_qc',
            ]);
        }

        return redirect()
            ->back()
            ->with('success', 'Cutting job dikirim ke QC Cutting.');
    }


    /**
     * Sinkron actual used fabric lot dari total bundle.
     *
     * Aturan:
     * cutting_job_lots.used_fabric_qty =
     * SUM(cutting_job_bundles.qty_used_fabric)
     * per cutting_job_id + lot_id.
     */
    private function syncCuttingJobLotUsedFabric($cuttingJob): void
    {
        $cuttingJobId = is_object($cuttingJob) ? (int) $cuttingJob->id : (int) $cuttingJob;

        if ($cuttingJobId <= 0) {
            return;
        }

        $rows = DB::table('cutting_job_lots as l')
            ->leftJoin('cutting_job_bundles as b', function ($join) {
                $join->on('b.cutting_job_id', '=', 'l.cutting_job_id')
                    ->on('b.lot_id', '=', 'l.lot_id');
            })
            ->where('l.cutting_job_id', $cuttingJobId)
            ->select(
                'l.id',
                DB::raw('COALESCE(SUM(COALESCE(b.qty_used_fabric,0)),0) as bundle_used_fabric')
            )
            ->groupBy('l.id')
            ->get();

        foreach ($rows as $row) {
            DB::table('cutting_job_lots')
                ->where('id', $row->id)
                ->update([
                    'used_fabric_qty' => (float) $row->bundle_used_fabric,
                    'updated_at' => now(),
                ]);
        }
    }

    /**
     * Catat sisa kain fisik setelah cutting dan kembalikan ke RM.
     * Tiap LOT bisa punya qty_sisa_fabric masing-masing.
     */
    public function recordSisaFabric(Request $request, CuttingJob $cuttingJob)
    {
        $validated = $request->validate([
            'lots'              => ['required', 'array', 'min:1'],
            'lots.*.lot_id'     => ['required', 'integer', 'exists:lots,id'],
            'lots.*.qty_sisa'   => ['nullable', 'numeric', 'min:0'],
            'lots.*.qty_scrap'  => ['nullable', 'numeric', 'min:0'],
        ]);

        $rmWarehouseId = Warehouse::where('code', 'RM')->value('id');
        if (!$rmWarehouseId) {
            throw ValidationException::withMessages(['lots' => 'Gudang RM belum dikonfigurasi.']);
        }

        $epsilon = 0.0001;
        $processed = 0;

        // Potongan standar (kg jadi pcs) per LOT: Σ qty_pcs × BOM qty (tanpa scrap).
        // Dipakai untuk memisahkan scrap dari pemakaian vs scrap dari sisa LOT.
        $bomQtyByItem = \App\Models\ItemBomLine::query()
            ->where('material_item_id', (int) $cuttingJob->fabric_item_id)
            ->where('usage_stage', \App\Models\ItemBomLine::STAGE_MAIN_MATERIAL)
            ->whereHas('bom', fn($q) => $q->where('active', true))
            ->with('bom:id,item_id')
            ->get()
            ->keyBy(fn($l) => (int) $l->bom->item_id)
            ->map(fn($l) => (float) $l->qty);

        $goodByLot = [];
        foreach ($cuttingJob->bundles as $b) {
            $goodByLot[(int) $b->lot_id] = ($goodByLot[(int) $b->lot_id] ?? 0.0)
                + (float) $b->qty_pcs * (float) ($bomQtyByItem[(int) $b->finished_item_id] ?? 0);
        }

        DB::transaction(function () use ($validated, $cuttingJob, $rmWarehouseId, $epsilon, $goodByLot, &$processed) {

            foreach ($validated['lots'] as $row) {
                $lotId    = (int) $row['lot_id'];
                $qtySisa  = (float) ($row['qty_sisa'] ?? 0);
                $qtyScrap = (float) ($row['qty_scrap'] ?? 0);

                // Baris tanpa sisa layak & tanpa scrap → tidak ada yang dicatat
                if ($qtySisa <= $epsilon && $qtyScrap <= $epsilon) {
                    continue;
                }

                // Cari pivot cutting_job_lots
                $cjLot = CuttingJobLot::where('cutting_job_id', $cuttingJob->id)
                    ->where('lot_id', $lotId)
                    ->lockForUpdate()
                    ->first();

                if (!$cjLot) {
                    throw ValidationException::withMessages([
                        'lots' => "LOT #{$lotId} tidak terdaftar di cutting job ini.",
                    ]);
                }

                if ($cjLot->sisa_recorded_at) {
                    throw ValidationException::withMessages([
                        'lots' => "Sisa bahan LOT #{$lotId} sudah pernah dicatat. Tidak bisa dicatat ulang.",
                    ]);
                }

                $lot     = Lot::lockForUpdate()->findOrFail($lotId);
                $avgCost = (float) ($lot->avg_cost ?? 0);

                $usedQty   = (float) ($cjLot->used_fabric_qty ?? 0);
                $goodQty   = (float) ($goodByLot[$lotId] ?? 0);
                $lotOnhand = (float) ($lot->qty_onhand ?? 0);

                // Batas: sisa layak + scrap ≤ kain terpakai + sisa fisik LOT
                if (($qtySisa + $qtyScrap) > ($usedQty + $lotOnhand) + $epsilon) {
                    throw ValidationException::withMessages([
                        'lots' => "LOT #{$lotId}: sisa layak ({$qtySisa}) + scrap ({$qtyScrap}) melebihi kain terpakai ({$usedQty}) + sisa LOT ({$lotOnhand}).",
                    ]);
                }

                // ── ALOKASI SISA & SCRAP ──
                // Sumber "kelebihan kain" ada dua:
                // (1) EXCESS: kain terpakai melebihi potongan standar (sudah keluar stok)
                // (2) REMNANT: sisa fisik LOT (masih tercatat sebagai stok)
                //
                // Sisa Layak → klaim dari EXCESS dulu (stockIn balik);
                //              selebihnya = remnant dibiarkan tetap jadi stok (tanpa mutasi).
                // Scrap      → klaim dari sisa EXCESS (analitik saja, stok sudah terpotong);
                //              selebihnya = write-off dari REMNANT (stok dikurangi).
                $excess = $goodQty > 0 ? max($usedQty - $goodQty, 0) : $usedQty;

                $sisaFromUsed = min($qtySisa, $excess);
                $remnantKept  = max($qtySisa - $sisaFromUsed, 0); // sisa LOT yang dipertahankan

                if ($sisaFromUsed > $epsilon) {
                    // 1. StockIn ke RM (hanya porsi dari pemakaian)
                    $this->inventory->stockIn(
                        warehouseId: $rmWarehouseId,
                        itemId: (int) $cuttingJob->fabric_item_id,
                        qty: $sisaFromUsed,
                        date: now(),
                        sourceType: 'cutting_job_sisa',
                        sourceId: (int) $cuttingJob->id,
                        notes: "Sisa kain cutting {$cuttingJob->code} - LOT {$lot->code}",
                        lotId: $lotId,
                        unitCost: $avgCost > 0 ? $avgCost : null,
                        affectLotCost: false,
                    );

                    // 2. Tambah kembali qty_onhand LOT
                    $lot->qty_onhand  = (float) $lot->qty_onhand + $sisaFromUsed;
                    $lot->total_cost  = $lot->qty_onhand * $avgCost;
                    if ($lot->status === 'closed' && $lot->qty_onhand > 0) {
                        $lot->status = 'active';
                    }
                    $lot->save();
                }

                if ($qtyScrap > $epsilon) {
                    $excessLeft    = max($excess - $sisaFromUsed, 0);
                    $scrapFromUsed = min($qtyScrap, $excessLeft);
                    $writeOff      = min(
                        $qtyScrap - $scrapFromUsed,
                        max((float) $lot->qty_onhand - $remnantKept, 0)
                    );

                    if ($writeOff > $epsilon) {
                        $this->inventory->stockOut(
                            warehouseId: $rmWarehouseId,
                            itemId: (int) $cuttingJob->fabric_item_id,
                            qty: $writeOff,
                            date: now(),
                            sourceType: 'cutting_job_scrap',
                            sourceId: (int) $cuttingJob->id,
                            notes: "Scrap sisa LOT {$lot->code} - cutting {$cuttingJob->code}",
                            allowNegative: false,
                            lotId: $lotId,
                            unitCostOverride: $avgCost > 0 ? $avgCost : null,
                            affectLotCost: false,
                        );

                        $lot->qty_onhand = max((float) $lot->qty_onhand - $writeOff, 0);
                        $lot->total_cost = $lot->qty_onhand * $avgCost;
                        $lot->save();
                    }
                }

                // 3. Catat di pivot
                $cjLot->qty_sisa_fabric   = $qtySisa;
                if (\Illuminate\Support\Facades\Schema::hasColumn('cutting_job_lots', 'qty_scrap')) {
                    $cjLot->qty_scrap = $qtyScrap;
                }
                $cjLot->sisa_recorded_at  = now();
                $cjLot->sisa_recorded_by  = auth()->id();
                $cjLot->save();

                $processed++;
            }
        });

        if ($processed === 0) {
            // Dipanggil via AJAX (mis. modal konfirmasi selesai cutting): balas JSON
            // agar langkah berikutnya (finalize QC) bisa lanjut walau tak ada yang dicatat.
            if ($request->expectsJson()) {
                return response()->json(['ok' => true, 'processed' => 0, 'message' => 'Tidak ada sisa/scrap yang perlu dicatat.']);
            }

            return back()->with('warning', 'Tidak ada sisa/scrap yang perlu dicatat (semua qty = 0).');
        }

        if ($request->expectsJson()) {
            return response()->json(['ok' => true, 'processed' => $processed, 'message' => "Sisa kain tercatat ({$processed} LOT)."]);
        }

        return redirect()
            ->route('production.cutting_jobs.show', $cuttingJob)
            ->with('success', "Sisa kain berhasil dicatat ({$processed} LOT) — sisa layak kembali ke RM, scrap tercatat untuk evaluasi BOM.");
    }

}
