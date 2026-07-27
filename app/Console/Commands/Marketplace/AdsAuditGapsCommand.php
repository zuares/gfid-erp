<?php

namespace App\Console\Commands\Marketplace;

use App\Jobs\ShopeeAdsSyncJob;
use App\Models\MarketplaceAdCampaignDaily;
use App\Models\MarketplaceAdsDaily;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\Cache;

/**
 * Penjaga kelengkapan data ads.
 *
 * Mendeteksi "tanggal bolong" — hari tanpa baris shop-daily, atau hari yang
 * punya spend tapi tidak punya baris campaign-daily — lalu (dengan --fix)
 * mengantrekan job perbaikan HANYA untuk jendela tanggal yang hilang,
 * bukan mengulang seluruh rentang. Hemat kuota, data tetap utuh.
 */
class AdsAuditGapsCommand extends Command
{
    protected $signature = 'marketplace:ads-audit-gaps
                            {--days=90 : Berapa hari ke belakang yang diaudit (maks 180)}
                            {--store= : ID toko tertentu (kosong = semua toko aktif)}
                            {--fix : Antrekan job perbaikan untuk tanggal yang bolong}';

    protected $description = 'Audit kelengkapan data ads harian; deteksi tanggal bolong dan (opsional) perbaiki lewat queue';

    public function handle(): int
    {
        $days = min(180, max(7, (int) $this->option('days')));

        $query = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->where('status', 'active')
            ->where('is_active', true); // toko nonaktif dilewati

        if ($this->option('store')) {
            $query->where('id', $this->option('store'));
        }

        $stores = $query->get();
        if ($stores->isEmpty()) {
            $this->warn('Tidak ada toko Shopee aktif.');
            return self::SUCCESS;
        }

        $totalMissing = 0;

        foreach ($stores as $store) {
            // Mulai audit dari data tertua yang pernah ada (jangan menandai
            // masa sebelum toko punya data sebagai "bolong").
            $firstData = MarketplaceAdsDaily::where('store_id', $store->id)->min('date');
            if (! $firstData) {
                if ($this->option('fix')) {
                    // Toko baru/aktif kembali tanpa data ads: antre backfill awal
                    // 90 hari (dedupe + progress ditangani command sync-ads).
                    $this->call('marketplace:sync-ads', [
                        '--store'    => $store->id,
                        '--backfill' => true,
                        '--from'     => now()->subDays(90)->toDateString(),
                        '--to'       => now()->toDateString(),
                    ]);
                    $this->info("[{$store->name}] belum ada data — backfill awal 90 hari diantrekan.");
                } else {
                    $this->line("[{$store->name}] belum ada data sama sekali — jalankan dengan --fix atau backfill manual.");
                }
                continue;
            }

            $start = Carbon::parse($firstData)->startOfDay();
            $floor = now()->subDays($days)->startOfDay();
            if ($start->lt($floor)) {
                $start = $floor;
            }
            $end = now()->subDay()->startOfDay(); // hari ini belum final, jangan diaudit

            if ($start->gt($end)) {
                $this->line("[{$store->name}] rentang audit kosong.");
                continue;
            }

            // 1. Tanggal shop-daily yang hilang sama sekali
            $have = MarketplaceAdsDaily::where('store_id', $store->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->pluck('date')
                ->map(fn ($d) => substr((string) $d, 0, 10))
                ->flip();

            // 2. Hari ber-spend tapi tanpa baris campaign-daily (kasus bug GMS-5 dulu)
            $spendDays = MarketplaceAdsDaily::where('store_id', $store->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->where('spend', '>', 0)
                ->pluck('date')
                ->map(fn ($d) => substr((string) $d, 0, 10));

            $campDays = MarketplaceAdCampaignDaily::where('store_id', $store->id)
                ->whereBetween('date', [$start->toDateString(), $end->toDateString()])
                ->distinct()
                ->pluck('date')
                ->map(fn ($d) => substr((string) $d, 0, 10))
                ->flip();

            $missing = [];
            for ($d = $start->copy(); $d->lte($end); $d->addDay()) {
                $key = $d->toDateString();
                if (! isset($have[$key])) {
                    $missing[$key] = 'shop-daily hilang';
                }
            }
            foreach ($spendDays as $key) {
                if (! isset($campDays[$key]) && ! isset($missing[$key])) {
                    $missing[$key] = 'ada spend, campaign-daily kosong';
                }
            }

            if (empty($missing)) {
                $this->info("[{$store->name}] LENGKAP — {$start->toDateString()} s/d {$end->toDateString()}, tidak ada tanggal bolong.");
                continue;
            }

            ksort($missing);
            $totalMissing += count($missing);
            $this->warn("[{$store->name}] " . count($missing) . ' tanggal bolong:');
            foreach ($missing as $date => $reason) {
                $this->line("  - {$date} ({$reason})");
            }

            if (! $this->option('fix')) {
                continue;
            }

            // Dedupe: jangan antre perbaikan ganda untuk toko yang sama.
            if (! Cache::add('shopee-ads-gapfix:' . $store->id, 1, 3600)) {
                $this->warn("[{$store->name}] perbaikan sudah ada di antrean — dilewati.");
                continue;
            }

            // Kelompokkan tanggal berurutan jadi jendela (maks 30 hari per job).
            $windows = [];
            $dates = array_keys($missing);
            $winStart = $winEnd = Carbon::parse(array_shift($dates));
            foreach ($dates as $dStr) {
                $d = Carbon::parse($dStr);
                if ($d->diffInDays($winEnd) === 1 && $winEnd->diffInDays($winStart) < 29) {
                    $winEnd = $d;
                } else {
                    $windows[] = [$winStart, $winEnd];
                    $winStart = $winEnd = $d;
                }
            }
            $windows[] = [$winStart, $winEnd];

            $jobs = [];
            foreach ($windows as [$from, $to]) {
                // Chunk pertama sync lengkap (campaigns); berikutnya skipMeta.
                $jobs[] = new ShopeeAdsSyncJob($store, $from->copy(), $to->copy(), false, count($jobs) > 0);
                $this->info("  → antre perbaikan {$from->toDateString()} s/d {$to->toDateString()}");
            }

            Bus::chain($jobs)->dispatch();

            // Progress bar tab Sync ikut menampilkan perbaikan ini.
            Cache::put(
                'shopee-ads-backfill-progress:' . $store->id,
                ['total' => count($jobs), 'started' => time()],
                7200
            );
        }

        $this->info("Selesai. Total tanggal bolong terdeteksi: {$totalMissing}.");

        return self::SUCCESS;
    }
}
