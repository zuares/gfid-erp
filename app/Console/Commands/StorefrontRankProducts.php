<?php

namespace App\Console\Commands;

use App\Services\ProductRankingService;
use Illuminate\Console\Command;

class StorefrontRankProducts extends Command
{
    protected $signature = 'storefront:rank-products
                            {--dry-run : Hitung ranking tanpa menyimpan ke database}';

    protected $description = 'Hitung ulang ranking produk storefront dan simpan ke database';

    public function handle(ProductRankingService $service): int
    {
        $dryRun = $this->option('dry-run');

        if ($dryRun) {
            $this->warn('[DRY RUN] Ranking tidak akan disimpan ke database.');
        }

        $this->info('Menghitung ranking produk…');

        $startTime = microtime(true);

        try {
            $results = $service->recalculate(dryRun: $dryRun);
        } catch (\Throwable $e) {
            $this->error('Gagal menghitung ranking: ' . $e->getMessage());
            return self::FAILURE;
        }

        $elapsed = round(microtime(true) - $startTime, 2);

        if (empty($results)) {
            $this->warn('Tidak ada produk published yang ditemukan.');
            return self::SUCCESS;
        }

        // ── Tabel hasil ────────────────────────────────────────────────────────
        $rows = array_map(function ($r) {
            $pinMark = $r['is_pinned'] ? '📌' : '';
            $stockMark = $r['stock'] === 0 ? '❌' : ($r['stock'] <= 4 ? '⚠️' : '');
            $debug = $r['debug'];

            return [
                $r['position'],
                $pinMark . $r['name'],
                $r['score'],
                $debug['cvr_score'],
                $debug['trend_score'],
                $debug['eng_score'],
                $debug['new_boost'],
                $r['stock'] . $stockMark,
            ];
        }, $results);

        $this->table(
            ['#', 'Produk', 'Final Score', 'CVR', 'Trending', 'Engagement', 'New Boost', 'Stok'],
            $rows
        );

        $label = $dryRun ? '[DRY RUN] ' : '';
        $this->line('');
        $this->info("{$label}Selesai: " . count($results) . " produk diproses dalam {$elapsed}s.");

        return self::SUCCESS;
    }
}
