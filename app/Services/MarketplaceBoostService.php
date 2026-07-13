<?php

namespace App\Services;

use App\Models\MarketplaceBoostLog;
use App\Models\MarketplaceBoostPool;
use App\Models\MarketplaceBoostSchedule;
use App\Models\MarketplaceProduct;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;

/**
 * Mesin "Naikkan Produk" (Shopee boost).
 *
 * Batasan Shopee (API v2 product.boost_item):
 *   - maksimal 5 produk ter-boost bersamaan,
 *   - tiap boost bertahan 4 jam,
 *   - produk baru bisa di-boost lagi setelah 4 jam.
 *
 * Strategi: jadwal jam-tetap per produk diprioritaskan; sisa slot kosong
 * diisi otomatis dari antrian rotasi (pool) — produk yang paling lama belum
 * di-boost dapat giliran duluan.
 */
class MarketplaceBoostService
{
    public const MAX_SLOTS      = 5;
    public const COOLDOWN_HOURS = 4;

    public function __construct(protected ChannelManager $manager) {}

    // ─── Manual ───────────────────────────────────────────────────────────────

    /**
     * Naikkan sekarang sekumpulan produk (maks 5). Dipakai tombol manual.
     *
     * @param  Collection<int,MarketplaceProduct>  $products
     */
    public function boostNow(Store $store, Collection $products, string $source = 'manual'): array
    {
        $products = $products->filter(fn ($p) => filled($p->item_id))->take(self::MAX_SLOTS)->values();

        if ($products->isEmpty()) {
            return ['success' => false, 'boosted' => 0, 'message' => 'Tidak ada produk valid untuk di-boost.'];
        }

        $driver  = $this->manager->driver($store);
        $itemIds = $products->pluck('item_id')->all();

        try {
            $res = $driver->boostItems($store, $itemIds);
        } catch (\Throwable $e) {
            $res = ['error' => 'exception', 'message' => $e->getMessage()];
        }

        $success = empty($res['error']);
        $message = $res['message'] ?? ($res['error'] ?? null);

        $this->logBatch($store, $products, $source, $success, $message);

        return [
            'success' => $success,
            'boosted' => $success ? $products->count() : 0,
            'message' => $success
                ? "Berhasil menaikkan {$products->count()} produk."
                : ('Gagal boost: ' . ($message ?: 'error tidak diketahui')),
            'raw'     => $res,
        ];
    }

    // ─── Status ────────────────────────────────────────────────────────────────

    /**
     * Produk yang sedang di-boost di Shopee + sisa waktunya.
     */
    public function currentlyBoosted(Store $store): array
    {
        $driver = $this->manager->driver($store);

        try {
            $res = $driver->getBoostedList($store);
        } catch (\Throwable $e) {
            return ['error' => $e->getMessage(), 'items' => []];
        }

        if (! empty($res['error'])) {
            return ['error' => $res['message'] ?? $res['error'], 'items' => []];
        }

        $itemIds = $this->extractBoostedIds($res);

        if (empty($itemIds)) {
            return ['error' => null, 'items' => [], 'used' => 0, 'max' => self::MAX_SLOTS];
        }

        $products = MarketplaceProduct::where('store_id', $store->id)
            ->whereIn('item_id', $itemIds)
            ->get()->keyBy('item_id');

        // Sisa waktu diperkirakan dari log terakhir tiap produk.
        $latestLogs = MarketplaceBoostLog::where('store_id', $store->id)
            ->whereIn('item_id', $itemIds)
            ->where('success', true)
            ->orderByDesc('boosted_at')
            ->get()->unique('item_id')->keyBy('item_id');

        $items = collect($itemIds)->map(function ($id) use ($products, $latestLogs) {
            $p   = $products[$id] ?? null;
            $log = $latestLogs[$id] ?? null;
            $remaining = null;
            if ($log && $log->expires_at && $log->expires_at->isFuture()) {
                $remaining = (int) ceil(now()->diffInMinutes($log->expires_at, false));
            }
            return [
                'item_id'           => (string) $id,
                'product_id'        => $p?->id,
                'name'              => $p?->item_name ?? ('item ' . $id),
                'sku'               => $p?->item_sku,
                'image_url'         => $p?->image_url,
                'boosted_at'        => $log?->boosted_at?->toIso8601String(),
                'expires_at'        => $log?->expires_at?->toIso8601String(),
                'remaining_minutes' => $remaining,
            ];
        })->values()->all();

        return [
            'error' => null,
            'items' => $items,
            'used'  => count($itemIds),
            'max'   => self::MAX_SLOTS,
        ];
    }

    // ─── Engine terjadwal ───────────────────────────────────────────────────────

    /**
     * Jalankan mesin untuk semua toko Shopee aktif. Dipanggil command tiap 5 menit.
     */
    public function run(): array
    {
        $stores = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')->get()
            // Lewati toko yang belum terhubung (tanpa access_token) — kalau tidak,
            // tiap run akan gagal "invalid token" dan mengotori log.
            ->filter(fn ($s) => filled($s->credential('access_token')));

        $summary = [];
        foreach ($stores as $store) {
            try {
                $summary[] = ['store' => $store->name] + $this->runForStore($store);
            } catch (\Throwable $e) {
                Log::warning("[boost] {$store->name}: " . $e->getMessage());
                $summary[] = ['store' => $store->name, 'error' => $e->getMessage(), 'boosted' => 0];
            }
        }
        return $summary;
    }

    /**
     * Satu toko: kumpulkan jadwal yang jatuh tempo + rotasi pool, isi slot ≤5.
     */
    public function runForStore(Store $store): array
    {
        $now      = now();
        $today    = $now->toDateString();
        $nowTime  = $now->format('H:i:s');

        // 1. Jadwal jatuh tempo (jam tetap harian, catch-up bila terlewat).
        $schedulesDue = MarketplaceBoostSchedule::with('product')
            ->where('store_id', $store->id)
            ->where('is_active', true)
            ->where('boost_time', '<=', $nowTime)
            ->where(fn ($q) => $q->whereNull('last_fired_on')->orWhereDate('last_fired_on', '<', $today))
            ->orderBy('priority')
            ->orderBy('boost_time')
            ->get();

        // 2. Apakah rotasi pool jatuh tempo? (tiap 4 jam per toko)
        $lastPool = MarketplaceBoostLog::where('store_id', $store->id)
            ->where('source', 'pool')->where('success', true)
            ->orderByDesc('boosted_at')->first();
        $poolDue = ! $lastPool || $lastPool->boosted_at->lte($now->copy()->subHours(self::COOLDOWN_HOURS));

        // Tidak ada yang perlu dikerjakan → jangan panggil API (hemat rate limit).
        if ($schedulesDue->isEmpty() && ! $poolDue) {
            return ['boosted' => 0, 'skipped' => true];
        }

        // 3. Cek slot terpakai di Shopee.
        $driver    = $this->manager->driver($store);
        $boostedIds = [];
        try {
            $boostedIds = $this->extractBoostedIds($driver->getBoostedList($store));
        } catch (\Throwable $e) {
            Log::warning("[boost] get_boosted_list {$store->name}: " . $e->getMessage());
        }
        $boostedSet = array_flip(array_map('strval', $boostedIds));
        $free       = self::MAX_SLOTS - count($boostedSet);

        /** @var array<int,array{product:MarketplaceProduct,source:string,schedule?:MarketplaceBoostSchedule,pool?:MarketplaceBoostPool}> $picks */
        $picks     = [];
        $pickedIds = [];

        // 4. Jadwal diprioritaskan.
        foreach ($schedulesDue as $sch) {
            $p = $sch->product;
            if (! $p || blank($p->item_id)) continue;
            $iid = (string) $p->item_id;

            if (isset($boostedSet[$iid])) {           // sudah di atas → tandai selesai
                $sch->update(['last_fired_on' => $today, 'last_boosted_at' => $now]);
                continue;
            }
            if (isset($pickedIds[$iid])) {            // produk sama dijadwal 2x di menit sama
                $sch->update(['last_fired_on' => $today, 'last_boosted_at' => $now]);
                continue;
            }
            if ($free <= 0) break;                    // slot penuh → biarkan retry run berikut

            $picks[] = ['product' => $p, 'source' => 'schedule', 'schedule' => $sch];
            $pickedIds[$iid] = true;
            $free--;
        }

        // 5. Isi sisa slot dari pool rotasi (yang paling lama belum di-boost duluan).
        if ($poolDue && $free > 0) {
            $poolRows = MarketplaceBoostPool::with('product')
                ->where('store_id', $store->id)
                ->where('is_active', true)
                ->orderByRaw('last_boosted_at IS NULL DESC')  // belum pernah → giliran pertama
                ->orderBy('last_boosted_at')
                ->orderBy('sort_order')
                ->get();

            foreach ($poolRows as $row) {
                if ($free <= 0) break;
                $p = $row->product;
                if (! $p || blank($p->item_id)) continue;
                $iid = (string) $p->item_id;
                if (isset($boostedSet[$iid]) || isset($pickedIds[$iid])) continue;

                $picks[] = ['product' => $p, 'source' => 'pool', 'pool' => $row];
                $pickedIds[$iid] = true;
                $free--;
            }
        }

        if (empty($picks)) {
            return ['boosted' => 0, 'note' => 'tidak ada kandidat / slot penuh'];
        }

        // 6. Eksekusi satu panggilan boost untuk seluruh batch.
        $itemIds = array_map(fn ($x) => $x['product']->item_id, $picks);
        try {
            $res = $driver->boostItems($store, $itemIds);
        } catch (\Throwable $e) {
            $res = ['error' => 'exception', 'message' => $e->getMessage()];
        }
        $success = empty($res['error']);
        $message = $res['message'] ?? ($res['error'] ?? null);

        foreach ($picks as $pick) {
            $this->logOne($store, $pick['product'], $pick['source'], $success, $message);
            if (! $success) continue;

            if (($pick['schedule'] ?? null) instanceof MarketplaceBoostSchedule) {
                $pick['schedule']->update(['last_fired_on' => $today, 'last_boosted_at' => $now]);
            }
            if (($pick['pool'] ?? null) instanceof MarketplaceBoostPool) {
                $pick['pool']->update(['last_boosted_at' => $now]);
            }
        }

        return [
            'boosted' => $success ? count($picks) : 0,
            'success' => $success,
            'message' => $message,
            'items'   => $itemIds,
        ];
    }

    // ─── Helpers ─────────────────────────────────────────────────────────────────

    /** Ambil daftar item_id dari respons get_boosted_list (dukung int / objek). */
    protected function extractBoostedIds(array $res): array
    {
        $list = data_get($res, 'response.item_list', data_get($res, 'response', []));
        if (! is_array($list)) return [];

        return collect($list)
            ->map(fn ($x) => is_array($x) ? ($x['item_id'] ?? null) : $x)
            ->filter(fn ($x) => $x !== null && $x !== '')
            ->map(fn ($x) => (string) $x)
            ->values()->all();
    }

    protected function logBatch(Store $store, Collection $products, string $source, bool $success, ?string $message): void
    {
        foreach ($products as $p) {
            $this->logOne($store, $p, $source, $success, $message);
        }
    }

    protected function logOne(Store $store, MarketplaceProduct $product, string $source, bool $success, ?string $message): void
    {
        $now = now();
        MarketplaceBoostLog::create([
            'store_id'               => $store->id,
            'marketplace_product_id' => $product->id,
            'item_id'                => $product->item_id,
            'source'                 => $source,
            'success'                => $success,
            'message'                => $message ? mb_substr($message, 0, 500) : null,
            'boosted_at'             => $now,
            'expires_at'             => $success ? $now->copy()->addHours(self::COOLDOWN_HOURS) : null,
        ]);
    }
}
