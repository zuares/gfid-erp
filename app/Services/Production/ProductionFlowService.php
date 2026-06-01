<?php

namespace App\Services\Production;

use App\Models\ProductionMovement;
use App\Models\Warehouse;
use App\Services\Inventory\InventoryService;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

/**
 * ProductionFlowService
 *
 * Sumber kebenaran stok produksi = saldo inventory_mutations (sudah di-maintain
 * ke inventory_stocks oleh InventoryService). Service ini HANYA membaca/agregasi
 * untuk dashboard — tidak menulis stok. Penulisan stok (tombol aksi) memakai
 * InventoryService::transfer() dan dicatat di production_movements (stage berikutnya).
 *
 * Pemetaan "status produksi" -> gudang:
 *   siap-jahit   = WIP-CUT  (stok cut bundle siap dijahit)
 *   sedang-jahit = WIP-SEW  (sedang dijahit)
 *   wh-prd       = WH-PRD   (gudang produksi)
 *   qc           = WH-PRD   (gate QC; belum jadi gudang terpisah)
 *   ready        = WH-RTS   (ready stock / siap jual)
 *
 * Pemetaan produk/varian (items flat):
 *   Produk = item_category, Varian/SKU = item.code
 */
class ProductionFlowService
{
    /** status => kode warehouse (hanya status yang punya stok). */
    public const STATUS_WAREHOUSE = [
        'siap-jahit' => 'WIP-CUT',
        'sedang-jahit' => 'WIP-SEW',
        'wh-prd' => 'WH-PRD',
        'ready' => 'WH-RTS',
    ];

    /**
     * Pipeline status produksi (urut). 'warehouse' null = stage logis
     * (belum jadi lokasi stok) → tombol aksi hanya melayani stage ber-gudang.
     */
    public const STATUSES = [
        'rencana' => ['label' => 'Rencana Produksi', 'warehouse' => null],
        'siap-jahit' => ['label' => 'Siap Jahit', 'warehouse' => 'WIP-CUT'],
        'sedang-jahit' => ['label' => 'Sedang Jahit', 'warehouse' => 'WIP-SEW'],
        'wh-prd' => ['label' => 'WH-PRD', 'warehouse' => 'WH-PRD'],
        'qc' => ['label' => 'QC Produksi', 'warehouse' => null],
        'ready' => ['label' => 'Ready Stock', 'warehouse' => 'WH-RTS'],
    ];

    public function __construct(private InventoryService $inventory)
    {
    }

    /** Daftar status untuk UI. */
    public function statuses(): array
    {
        return self::STATUSES;
    }

    /** Status berikutnya yang ber-gudang (untuk tombol "lanjut tahap"). */
    public function nextStockStatus(string $slug): ?string
    {
        $slugs = array_keys(self::STATUSES);
        $idx = array_search($slug, $slugs, true);
        if ($idx === false) {
            return null;
        }
        for ($i = $idx + 1; $i < count($slugs); $i++) {
            if (self::STATUSES[$slugs[$i]]['warehouse'] !== null) {
                return $slugs[$i];
            }
        }
        return null;
    }

    /** Ambang (hari) untuk proxy KPI. */
    private const OVERDUE_AGE_DAYS = 14;   // WIP jahit menua = "telat"
    private const COVER_LOW_DAYS = 7;      // ready stock < 7 hari penjualan = prioritas

    /**
     * 5 KPI dashboard: Siap Jahit, Sedang Jahit, WH-PRD, Overdue, High Priority.
     * Snapshot stok "saat ini" (tidak tergantung rentang tanggal); hanya filter
     * produk (kategori) & varian (item) yang relevan.
     */
    public function dashboardKpis(array $f): array
    {
        $stockByStatus = $this->stockTotalsByStatus($f);

        return [
            'siap_jahit' => $stockByStatus['siap-jahit'] ?? 0.0,
            'sedang_jahit' => $stockByStatus['sedang-jahit'] ?? 0.0,
            'wh_prd' => $stockByStatus['wh-prd'] ?? 0.0,
            'overdue' => $this->overdueWipSewing($f),
            'high_priority' => $this->highPriorityCount($f),
        ];
    }

    /**
     * Total qty stok per status (snapshot inventory_stocks).
     * @return array<string,float> keyed by status slug
     */
    public function stockTotalsByStatus(array $f): array
    {
        $codes = array_values(self::STATUS_WAREHOUSE);
        $byCode = $this->baseStockQuery($f)
            ->whereIn('w.code', $codes)
            ->groupBy('w.code')
            ->selectRaw('w.code, COALESCE(SUM(s.qty),0) as qty')
            ->pluck('qty', 'code');

        $out = [];
        foreach (self::STATUS_WAREHOUSE as $status => $code) {
            $out[$status] = (float) ($byCode[$code] ?? 0);
        }
        return $out;
    }

    /**
     * Tabel ringkasan SKU produksi: stok per status + penjualan 7 hari,
     * dalam SATU query (pivot CASE), ringan & ter-index.
     */
    public function skuSummary(array $f, int $limit = 50): Collection
    {
        $q = $this->baseStockQuery($f)
            ->join('items as i', 'i.id', '=', 's.item_id')
            ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id')
            ->whereIn('w.code', array_values(self::STATUS_WAREHOUSE))
            ->groupBy('i.id', 'i.code', 'i.name', 'c.name')
            ->selectRaw("
                i.id as item_id,
                i.code as sku,
                i.name as product,
                COALESCE(c.name, '-') as category,
                COALESCE(SUM(CASE WHEN w.code='WIP-CUT' THEN s.qty END),0) as siap_jahit,
                COALESCE(SUM(CASE WHEN w.code='WIP-SEW' THEN s.qty END),0) as sedang_jahit,
                COALESCE(SUM(CASE WHEN w.code='WH-PRD'  THEN s.qty END),0) as wh_prd,
                COALESCE(SUM(CASE WHEN w.code='WH-RTS'  THEN s.qty END),0) as ready
            ")
            ->havingRaw("
                COALESCE(SUM(CASE WHEN w.code IN ('WIP-CUT','WIP-SEW','WH-PRD') THEN s.qty END),0) > 0.0001
            ")
            ->orderByRaw("
                COALESCE(SUM(CASE WHEN w.code IN ('WIP-CUT','WIP-SEW','WH-PRD') THEN s.qty END),0) DESC
            ")
            ->limit($limit);

        $rows = $q->get();

        // Penjualan 7 hari (sekali query untuk semua SKU di hasil)
        $sales7 = $this->sales7d($rows->pluck('item_id')->all());

        return $rows->map(function ($r) use ($sales7) {
            $wip = (float) $r->siap_jahit + (float) $r->sedang_jahit + (float) $r->wh_prd;
            $s7 = (float) ($sales7[$r->item_id] ?? 0);
            $ready = (float) $r->ready;
            $r->sales_7d = $s7;
            $r->wip_total = $wip;
            // days-of-cover ready stock terhadap laju jual 7 hari
            $rate = $s7 / 7;
            $r->cover_days = $rate > 0 ? round($ready / $rate, 1) : null;
            $r->is_priority = $rate > 0 && $r->cover_days !== null && $r->cover_days < self::COVER_LOW_DAYS;
            return $r;
        })->values();
    }

    // ==========================================================
    // MUTASI / TOMBOL AKSI
    // ==========================================================

    /**
     * Pindahkan qty dari satu status ke status lain.
     * Stok berpindah via InventoryService::transfer() (menulis inventory_mutations
     * + update inventory_stocks), lalu dicatat sebagai ProductionMovement.
     *
     * @throws ValidationException
     */
    public function move(array $p, ?int $userId = null): ProductionMovement
    {
        $fromStatus = $p['from_status'] ?? null;
        $toStatus = $p['to_status'] ?? null;
        $itemId = (int) ($p['item_id'] ?? 0);
        $qty = $this->num($p['qty'] ?? 0);

        $fromWhId = $this->warehouseIdForStatus($fromStatus);
        $toWhId = $this->warehouseIdForStatus($toStatus);

        $errors = [];
        if (!$fromWhId) {
            $errors['from_status'] = ["Status asal '$fromStatus' belum punya gudang stok."];
        }
        if (!$toWhId) {
            $errors['to_status'] = ["Status tujuan '$toStatus' belum punya gudang stok."];
        }
        if ($fromWhId && $toWhId && $fromWhId === $toWhId) {
            $errors['to_status'] = ['Status asal dan tujuan sama.'];
        }
        if ($itemId <= 0) {
            $errors['item_id'] = ['SKU wajib dipilih.'];
        }
        if ($qty <= 0) {
            $errors['qty'] = ['Qty harus lebih dari 0.'];
        }
        if (!$errors && $fromWhId) {
            $balance = $this->inventory->getBalance($fromWhId, $itemId);
            if ($qty > $balance + 1e-6) {
                $errors['qty'] = ["Qty melebihi stok di status asal (tersedia {$balance})."];
            }
        }
        if ($errors) {
            throw ValidationException::withMessages($errors);
        }

        $date = !empty($p['date']) ? Carbon::parse($p['date'])->toDateString() : Carbon::today()->toDateString();
        $bundleId = !empty($p['cutting_job_bundle_id']) ? (int) $p['cutting_job_bundle_id'] : null;

        return DB::transaction(function () use ($p, $itemId, $qty, $fromWhId, $toWhId, $fromStatus, $toStatus, $bundleId, $date, $userId) {
            $movement = ProductionMovement::create([
                'code' => $this->generateCode($date),
                'date' => $date,
                'cutting_job_bundle_id' => $bundleId,
                'item_id' => $itemId,
                'from_warehouse_id' => $fromWhId,
                'to_warehouse_id' => $toWhId,
                'from_status' => $fromStatus,
                'to_status' => $toStatus,
                'qty' => $qty,
                'operator_id' => !empty($p['operator_id']) ? (int) $p['operator_id'] : null,
                'deadline' => !empty($p['deadline']) ? Carbon::parse($p['deadline'])->toDateString() : null,
                'notes' => $p['notes'] ?? null,
                'created_by' => $userId,
            ]);

            $mutations = $this->inventory->transfer(
                fromWarehouseId: $fromWhId,
                toWarehouseId: $toWhId,
                itemId: $itemId,
                qty: $qty,
                date: $date,
                sourceType: 'production_movement',
                sourceId: $movement->id,
                notes: $p['notes'] ?? null,
                allowNegative: false,
                lotId: null,
                cuttingJobBundleId: $bundleId,
            );

            if (!empty($mutations['in']?->id)) {
                $movement->inventory_mutation_id = $mutations['in']->id;
                $movement->save();
            }

            return $movement;
        });
    }

    /** Nomor dokumen: MUT-YYYYMMDD-NNN (urut per hari). */
    public function generateCode(string $date): string
    {
        $ymd = Carbon::parse($date)->format('Ymd');
        $count = ProductionMovement::whereDate('date', Carbon::parse($date)->toDateString())->count();
        return 'MUT-' . $ymd . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }

    /** Query daftar mutasi untuk tabel (eager relasi, terfilter). */
    public function movementsQuery(array $f)
    {
        $q = ProductionMovement::query()
            ->with(['item.category', 'bundle', 'operator', 'creator', 'fromWarehouse', 'toWarehouse'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        // Range tanggal sargable (kolom date bisa menyimpan komponen waktu 00:00:00,
        // jadi pakai >= from dan < to+1hari agar hari batas-atas tetap ikut).
        if (!empty($f['date_from'])) {
            $q->where('date', '>=', Carbon::parse($f['date_from'])->toDateString());
        }
        if (!empty($f['date_to'])) {
            $q->where('date', '<', Carbon::parse($f['date_to'])->addDay()->toDateString());
        }
        if (!empty($f['item_id'])) {
            $q->where('item_id', $f['item_id']);
        }
        if (!empty($f['operator_id'])) {
            $q->where('operator_id', $f['operator_id']);
        }
        if (!empty($f['to_status'])) {
            $q->where('to_status', $f['to_status']);
        }
        if (!empty($f['category_id'])) {
            $q->whereHas('item', fn($iq) => $iq->where('item_category_id', $f['category_id']));
        }

        return $q;
    }

    /**
     * Rekap throughput produksi per SKU dari production_movements (untuk Laporan).
     * Pivot qty per status tujuan dalam periode. Read-only, satu query ter-index.
     */
    public function productionRecap(array $f, int $limit = 300): Collection
    {
        $q = DB::table('production_movements as pm')
            ->join('items as i', 'i.id', '=', 'pm.item_id')
            ->leftJoin('item_categories as c', 'c.id', '=', 'i.item_category_id');

        if (!empty($f['date_from'])) {
            $q->where('pm.date', '>=', Carbon::parse($f['date_from'])->toDateString());
        }
        if (!empty($f['date_to'])) {
            $q->where('pm.date', '<', Carbon::parse($f['date_to'])->addDay()->toDateString());
        }
        if (!empty($f['item_id'])) {
            $q->where('pm.item_id', $f['item_id']);
        }
        if (!empty($f['operator_id'])) {
            $q->where('pm.operator_id', $f['operator_id']);
        }
        if (!empty($f['category_id'])) {
            $q->where('i.item_category_id', $f['category_id']);
        }
        if (!empty($f['to_status'])) {
            $q->where('pm.to_status', $f['to_status']);
        }

        return $q->groupBy('i.id', 'i.code', 'i.name', 'c.name')
            ->selectRaw("
                i.id as item_id,
                i.code as sku,
                i.name as product,
                COALESCE(c.name, '-') as category,
                COALESCE(SUM(CASE WHEN pm.to_status='siap-jahit'   THEN pm.qty END),0) as to_siap_jahit,
                COALESCE(SUM(CASE WHEN pm.to_status='sedang-jahit' THEN pm.qty END),0) as to_sedang_jahit,
                COALESCE(SUM(CASE WHEN pm.to_status='wh-prd'       THEN pm.qty END),0) as to_wh_prd,
                COALESCE(SUM(CASE WHEN pm.to_status='ready'        THEN pm.qty END),0) as to_ready,
                COALESCE(SUM(pm.qty),0) as total_qty,
                COUNT(*) as moves
            ")
            ->orderByRaw('COALESCE(SUM(pm.qty),0) DESC')
            ->limit($limit)
            ->get();
    }

    /** Warehouse id untuk slug status (null bila stage logis). */
    public function warehouseIdForStatus(?string $slug): ?int
    {
        if (!$slug || empty(self::STATUSES[$slug]['warehouse'])) {
            return null;
        }
        return Warehouse::where('code', self::STATUSES[$slug]['warehouse'])->value('id');
    }

    // ==========================================================
    // INTERNAL
    // ==========================================================

    private function num($v): float
    {
        return (float) (is_string($v) ? str_replace(',', '.', $v) : $v);
    }

    /** Query dasar inventory_stocks + warehouses, terfilter produk/varian. */
    private function baseStockQuery(array $f)
    {
        $q = DB::table('inventory_stocks as s')
            ->join('warehouses as w', 'w.id', '=', 's.warehouse_id');

        // Varian/SKU
        if (!empty($f['item_id'])) {
            $q->where('s.item_id', $f['item_id']);
        }
        // Produk = kategori
        if (!empty($f['category_id'])) {
            $q->join('items as fi', 'fi.id', '=', 's.item_id')
                ->where('fi.item_category_id', $f['category_id']);
        }

        return $q;
    }

    /** Penjualan 7 hari per item_id. @return array<int,float> */
    private function sales7d(array $itemIds): array
    {
        if (empty($itemIds)) {
            return [];
        }
        $from = Carbon::today()->subDays(6)->toDateString();
        $to = Carbon::today()->toDateString();

        return DB::table('daily_item_sales')
            ->whereIn('item_id', $itemIds)
            ->whereBetween('date', [$from, $to])
            ->groupBy('item_id')
            ->selectRaw('item_id, COALESCE(SUM(qty_sold),0) as qty')
            ->pluck('qty', 'item_id')
            ->map(fn($v) => (float) $v)
            ->all();
    }

    /**
     * Proxy "Overdue": total qty WIP sewing outstanding yang menua
     * lebih dari OVERDUE_AGE_DAYS hari (belum dikembalikan).
     */
    private function overdueWipSewing(array $f): float
    {
        $threshold = Carbon::today()->subDays(self::OVERDUE_AGE_DAYS)->toDateString();

        $q = DB::table('sewing_pickup_lines as pl')
            ->join('sewing_pickups as p', 'p.id', '=', 'pl.sewing_pickup_id')
            ->whereNull('p.voided_at')
            ->where('p.date', '<=', $threshold)
            ->where('pl.status', 'in_progress');

        if (!empty($f['item_id'])) {
            $q->where('pl.finished_item_id', $f['item_id']);
        }
        if (!empty($f['category_id'])) {
            $q->join('items as it', 'it.id', '=', 'pl.finished_item_id')
                ->where('it.item_category_id', $f['category_id']);
        }

        return (float) $q->sum(DB::raw(
            'pl.qty_bundle - COALESCE(pl.qty_returned_ok,0) - COALESCE(pl.qty_returned_reject,0)'
        ));
    }

    /**
     * Proxy "High Priority": jumlah SKU finished_good yang ready stock-nya
     * di bawah COVER_LOW_DAYS hari penjualan (cover rendah, perlu diproduksi).
     */
    private function highPriorityCount(array $f): int
    {
        // ready stock per item (WH-RTS)
        $ready = $this->baseStockQuery($f)
            ->where('w.code', 'WH-RTS')
            ->groupBy('s.item_id')
            ->selectRaw('s.item_id, COALESCE(SUM(s.qty),0) as qty')
            ->pluck('qty', 'item_id');

        if ($ready->isEmpty()) {
            return 0;
        }

        $sales7 = $this->sales7d($ready->keys()->all());

        $count = 0;
        foreach ($ready as $itemId => $qty) {
            $rate = (float) ($sales7[$itemId] ?? 0) / 7;
            if ($rate <= 0) {
                continue;
            }
            $cover = (float) $qty / $rate;
            if ($cover < self::COVER_LOW_DAYS) {
                $count++;
            }
        }
        return $count;
    }
}
