<?php

namespace App\Services;

use App\Models\StorefrontEvent;
use App\Models\StorefrontProduct;
use Illuminate\Support\Collection;

/**
 * ProductRankingService
 *
 * Menghitung ranking produk berdasarkan formula:
 *
 *   final_score = (cvr_score   × 0.35)
 *               + (trending_score × 0.35)
 *               + (engagement_score × 0.15)
 *               + (new_product_boost × 0.10)
 *               + (stock_score × 0.05)
 *               + manual_boost       ← additif, tidak ternormalisasi
 *               + featured_boost     ← +0.5 jika featured_until masih aktif
 *
 * Semua komponen dinormalisasi min-max (0.0–1.0) sebelum dikalikan weight,
 * kecuali manual_boost dan featured_boost yang bersifat additive.
 *
 * Urutan akhir:
 *   1. Produk is_pinned → sorted by pin_position ASC
 *   2. Produk tidak kosong → sorted by final_score DESC
 *   3. Produk stok = 0   → sorted by final_score DESC (di bawah semua in-stock)
 */
class ProductRankingService
{
    // ── Konfigurasi ───────────────────────────────────────────────────────────

    /** Window lookback untuk CVR + engagement (hari) */
    private const LOOK_BACK_DAYS = 30;

    /** Window trending sales (hari) */
    private const TRENDING_DAYS = 7;

    /** Berapa hari produk baru mendapat new_product_boost */
    private const NEW_BOOST_DAYS = 14;

    /** Boost yang diinject otomatis saat featured_until masih aktif */
    private const FEATURED_BOOST = 0.5;

    /** Minimum view count sebelum CVR / engagement dianggap reliable */
    private const MIN_VIEWS_RELIABLE = 5;

    // ── Formula weights (harus total = 1.0) ──────────────────────────────────
    private const W_CVR        = 0.35;
    private const W_TRENDING   = 0.35;
    private const W_ENGAGEMENT = 0.15;
    private const W_NEW_BOOST  = 0.10;
    private const W_STOCK      = 0.05;

    // ─────────────────────────────────────────────────────────────────────────

    /**
     * Jalankan kalkulasi ranking untuk semua produk yang dipublish.
     *
     * @param  bool  $dryRun  Jika true, hitung saja tapi tidak simpan ke DB.
     * @return array  Array of result rows untuk ditampilkan command.
     */
    public function recalculate(bool $dryRun = false): array
    {
        // 1. Load semua produk published beserta variant aktif
        $products = StorefrontProduct::where('is_published', true)
            ->with([
                'variants' => fn($q) => $q->where('is_active', true),
            ])
            ->get();

        if ($products->isEmpty()) {
            return [];
        }

        // 2. Load events dalam window lookback
        $since         = now()->subDays(self::LOOK_BACK_DAYS);
        $trendingSince = now()->subDays(self::TRENDING_DAYS);

        $events = StorefrontEvent::where('created_at', '>=', $since)
            ->whereIn('event_type', ['product_view', 'add_to_cart', 'order_complete'])
            ->get(['event_type', 'payload', 'created_at']);

        // 3. Build per-slug raw counters
        [$views, $cartAdds, $orders30d, $orders7d] = $this->buildCounters($events, $trendingSince);

        // 4. Compute raw (un-normalized) scores per product
        $rawScores = $this->computeRawScores($products, $views, $cartAdds, $orders30d, $orders7d);

        // 5. Min-max normalize CVR, engagement, trending
        $this->normalize($rawScores, 'cvr_raw',       'cvr_score');
        $this->normalize($rawScores, 'engagement_raw', 'engagement_score');
        $this->normalize($rawScores, 'trending_raw',   'trending_score');

        // 6. Compute final_score per product
        $scored = $this->computeFinalScores($rawScores);

        // 7. Sort: pinned → in-stock by score → out-of-stock by score
        $sorted = $this->sortProducts($scored);

        // 8. Persist (unless dry-run)
        $results = [];
        $position = 1;

        foreach ($sorted as $data) {
            $product = $data['product'];

            $results[] = [
                'id'        => $product->id,
                'name'      => $product->name,
                'position'  => $position,
                'score'     => round($data['final_score'], 4),
                'is_pinned' => $data['is_pinned'],
                'stock'     => $data['available_stock'],
                'debug'     => $data['debug'],
            ];

            if (! $dryRun) {
                $product->update([
                    'rank_score'      => $data['final_score'],
                    'rank_position'   => $position,
                    'rank_updated_at' => now(),
                    'rank_debug'      => $data['debug'],
                ]);
            }

            $position++;
        }

        return $results;
    }

    // ── Private helpers ───────────────────────────────────────────────────────

    /**
     * Parse events ke 4 array counter:
     *   [views, cartAdds, orders30d, orders7d]  keyed by product slug
     */
    private function buildCounters(Collection $events, \Carbon\Carbon $trendingSince): array
    {
        $views    = [];
        $cartAdds = [];
        $orders30d = [];
        $orders7d  = [];

        foreach ($events as $event) {
            $payload = $event->payload ?? [];

            switch ($event->event_type) {
                case 'product_view':
                    $slug = $payload['slug'] ?? null;
                    if ($slug) {
                        $views[$slug] = ($views[$slug] ?? 0) + 1;
                    }
                    break;

                case 'add_to_cart':
                    $slug = $payload['slug'] ?? null;
                    if ($slug) {
                        $cartAdds[$slug] = ($cartAdds[$slug] ?? 0) + 1;
                    }
                    break;

                case 'order_complete':
                    // items = array dari cart item, masing-masing punya 'slug'
                    $items = $payload['items'] ?? [];
                    foreach ($items as $item) {
                        $slug = $item['slug'] ?? null;
                        if (! $slug) continue;

                        $orders30d[$slug] = ($orders30d[$slug] ?? 0) + 1;

                        if ($event->created_at >= $trendingSince) {
                            $orders7d[$slug] = ($orders7d[$slug] ?? 0) + 1;
                        }
                    }
                    break;
            }
        }

        return [$views, $cartAdds, $orders30d, $orders7d];
    }

    /**
     * Hitung raw (un-normalized) value tiap komponen untuk setiap produk.
     */
    private function computeRawScores(
        Collection $products,
        array $views,
        array $cartAdds,
        array $orders30d,
        array $orders7d
    ): array {
        $raw = [];
        $now = now();

        foreach ($products as $product) {
            $slug = $product->slug;
            $v = $views[$slug]    ?? 0;
            $c = $cartAdds[$slug] ?? 0;
            $o = $orders30d[$slug] ?? 0;
            $t = $orders7d[$slug]  ?? 0;

            // CVR = orders_30d / views  (reliable hanya jika views cukup)
            $cvrRaw = ($v >= self::MIN_VIEWS_RELIABLE) ? $o / $v : 0.0;

            // Engagement = add_to_cart / views
            $engRaw = ($v >= self::MIN_VIEWS_RELIABLE) ? $c / $v : 0.0;

            // Trending = raw count orders 7 hari (dinormalisasi nanti)
            $trendingRaw = (float) $t;

            // New product boost: linear decay dari 1.0 → 0.0 selama NEW_BOOST_DAYS
            $ageInDays = $now->diffInDays($product->created_at);
            $newBoost = $ageInDays < self::NEW_BOOST_DAYS
                ? 1.0 - ($ageInDays / self::NEW_BOOST_DAYS)
                : 0.0;

            // Stock score (graduated)
            $availableStock = $this->resolveStock($product);
            $stockScore = match (true) {
                $availableStock === 0       => 0.0,
                $availableStock <= 4        => 0.3,
                $availableStock <= 20       => 0.7,
                default                     => 1.0,
            };

            // Featured boost: auto-inject jika featured_until masih aktif
            $featuredBoost = ($product->featured_until && $now->lt($product->featured_until))
                ? self::FEATURED_BOOST
                : 0.0;

            $raw[$product->id] = [
                'product'         => $product,
                'available_stock' => $availableStock,
                'cvr_raw'         => $cvrRaw,
                'engagement_raw'  => $engRaw,
                'trending_raw'    => $trendingRaw,
                'new_boost'       => $newBoost,
                'stock_score'     => $stockScore,
                'featured_boost'  => $featuredBoost,
                'manual_boost'    => (float) ($product->manual_boost ?? 0),
                // counters for debug
                'view_count'      => $v,
                'cart_count'      => $c,
                'order_count_30d' => $o,
                'order_count_7d'  => $t,
            ];
        }

        return $raw;
    }

    /**
     * Min-max normalize nilai $rawKey → $scoreKey (0.0–1.0) in place.
     */
    private function normalize(array &$data, string $rawKey, string $scoreKey): void
    {
        $values = array_column($data, $rawKey);
        $min    = min($values);
        $max    = max($values);
        $range  = $max - $min;

        foreach ($data as &$row) {
            $row[$scoreKey] = $range > 0
                ? ($row[$rawKey] - $min) / $range
                : 0.0;
        }
        unset($row);
    }

    /**
     * Gabungkan komponen yang sudah dinormalisasi menjadi final_score.
     */
    private function computeFinalScores(array $rawScores): array
    {
        $result = [];

        foreach ($rawScores as $productId => $data) {
            $cvr        = $data['cvr_score']        ?? 0.0;
            $trending   = $data['trending_score']   ?? 0.0;
            $engagement = $data['engagement_score'] ?? 0.0;
            $newBoost   = $data['new_boost'];
            $stock      = $data['stock_score'];
            $featured   = $data['featured_boost'];
            $manual     = $data['manual_boost'];

            $finalScore = ($cvr        * self::W_CVR)
                        + ($trending   * self::W_TRENDING)
                        + ($engagement * self::W_ENGAGEMENT)
                        + ($newBoost   * self::W_NEW_BOOST)
                        + ($stock      * self::W_STOCK)
                        + $manual
                        + $featured;

            $debug = [
                // Raw values (untuk traceback)
                'views'          => $data['view_count'],
                'carts'          => $data['cart_count'],
                'orders_30d'     => $data['order_count_30d'],
                'orders_7d'      => $data['order_count_7d'],
                // Raw rates
                'cvr_raw'        => round($data['cvr_raw'], 5),
                'eng_raw'        => round($data['engagement_raw'], 5),
                'trend_raw'      => $data['trending_raw'],
                // Normalized scores
                'cvr_score'      => round($cvr, 4),
                'eng_score'      => round($engagement, 4),
                'trend_score'    => round($trending, 4),
                // Additive components
                'new_boost'      => round($newBoost, 4),
                'stock_score'    => round($stock, 4),
                'featured_boost' => round($featured, 4),
                'manual_boost'   => round($manual, 4),
                // Final
                'final_score'    => round($finalScore, 5),
            ];

            $result[$productId] = [
                'product'         => $data['product'],
                'available_stock' => $data['available_stock'],
                'final_score'     => $finalScore,
                'is_pinned'       => (bool) $data['product']->is_pinned,
                'pin_position'    => $data['product']->pin_position,
                'stock_score'     => $stock,
                'debug'           => $debug,
            ];
        }

        return $result;
    }

    /**
     * Urutkan produk:
     *   1. Pinned → by pin_position ASC (stok 0 tetap ikut)
     *   2. In-stock non-pinned → by final_score DESC
     *   3. Out-of-stock non-pinned → by final_score DESC
     */
    private function sortProducts(array $scored): array
    {
        $pinned    = [];
        $inStock   = [];
        $outOfStock = [];

        foreach ($scored as $data) {
            if ($data['is_pinned']) {
                $pinned[] = $data;
            } elseif ($data['stock_score'] > 0 || $data['available_stock'] > 0) {
                $inStock[] = $data;
            } else {
                $outOfStock[] = $data;
            }
        }

        // Sort pinned by pin_position (null → last)
        usort($pinned, function ($a, $b) {
            $pa = $a['pin_position'] ?? PHP_INT_MAX;
            $pb = $b['pin_position'] ?? PHP_INT_MAX;
            return $pa <=> $pb;
        });

        // Sort in-stock by final_score DESC
        usort($inStock, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        // Sort out-of-stock by final_score DESC
        usort($outOfStock, fn($a, $b) => $b['final_score'] <=> $a['final_score']);

        return array_merge($pinned, $inStock, $outOfStock);
    }

    /**
     * Stock resolver:
     *   - Jika ada active variants → sum(variant.stock)
     *   - Fallback → product.stock
     */
    private function resolveStock(StorefrontProduct $product): int
    {
        if ($product->variants->isNotEmpty()) {
            return (int) $product->variants->sum('stock');
        }

        return (int) ($product->stock ?? 0);
    }
}
