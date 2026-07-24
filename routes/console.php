<?php

use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('production:seed-boms {--force : Jalankan tanpa konfirmasi saat environment production}', function () {
    $this->info('Generate BOM produksi sendiri...');

    $code = $this->call('db:seed', [
        '--class' => Database\Seeders\ItemBomSeeder::class,
        '--force' => (bool) $this->option('force'),
    ]);

    if ($code !== 0) {
        $this->error('Seeder BOM gagal dijalankan.');
        return 1;
    }

    $rows = DB::table('items as i')
        ->join('item_categories as c', 'c.id', '=', 'i.item_category_id')
        ->leftJoin('item_boms as b', 'b.item_id', '=', 'i.id')
        ->where('i.type', 'finished_good')
        ->where('c.code', '!=', 'SHT')
        ->groupBy('c.code')
        ->orderBy('c.code')
        ->selectRaw("
            c.code as kategori,
            COUNT(i.id) as total_fg,
            SUM(CASE WHEN b.id IS NOT NULL AND b.active = 1 THEN 1 ELSE 0 END) as total_bom
        ")
        ->get();

    if ($rows->isNotEmpty()) {
        $this->newLine();
        $this->table(
            ['Kategori', 'FG', 'BOM Aktif'],
            $rows->map(fn ($r) => [
                $r->kategori,
                (int) $r->total_fg,
                (int) $r->total_bom,
            ])->all()
        );
    }

    $this->info('Selesai.');

    return 0;
})->purpose('Generate BOM finished goods produksi sendiri kecuali kategori SHT.');

// ── Storefront product ranking (setiap jam) ───────────────────────────────
Schedule::call(fn () => Artisan::call('storefront:rank-products'))
    ->hourly()
    ->name('storefront-rank')
    ->name("task_" . uniqid())->withoutOverlapping();

// Update harian setelah ganti hari
Schedule::call(fn () => Artisan::call('sales:rebuild-daily-item-sales', ['--days' => 90]))
    ->dailyAt('00:05');

Schedule::call(fn () => Artisan::call('inventory:recalc-ads-from-daily', ['--days' => 30]))
    ->dailyAt('00:10');

// Auto-cleanup file resi (.pdf.gz) setiap malam pukul 01:00
Schedule::call(fn () => Artisan::call('marketplace:sync-orders'))
    ->everyFiveMinutes()
    ->name('sync-orders')
    ->name("task_" . uniqid())->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:sync-returns'))
    ->hourly()
    ->name('sync-returns')
    ->name("task_" . uniqid())->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:sync-settlements'))
    ->everyFourHours()
    ->name('sync-settlements')
    ->name("task_" . uniqid())->withoutOverlapping();

// Finance: sync order → settlement → COGS setiap 4 jam (profit per order)
Schedule::call(fn () => Artisan::call('marketplace:sync-finance', ['--months' => 1]))
    ->everyFourHours()
    ->name('sync-finance')
    ->name("task_" . uniqid())->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:sync-chats'))
    ->everyMinute()
    ->name('sync-chats')
    ->name("task_" . uniqid())->withoutOverlapping();

// Ads: simpan performa harian + snapshot saldo ke DB (untuk analisa historis)
Schedule::call(fn () => Artisan::call('marketplace:sync-ads-daily', ['--days' => 3]))
    ->dailyAt('23:30')
    ->name('sync-ads-daily')
    ->name("task_" . uniqid())->withoutOverlapping();

// Produk: sync dari Shopee + snapshot harian metrik (stok/harga/terjual)
Schedule::call(fn () => Artisan::call('marketplace:snapshot-products', ['--sync' => true]))
    ->dailyAt('23:45')
    ->name('snapshot-products')
    ->name("task_" . uniqid())->withoutOverlapping();

// Naikkan Produk: mesin boost terjadwal (jam-tetap + rotasi otomatis, maks 5 / 4 jam)
Schedule::call(fn () => Artisan::call('marketplace:run-boosts'))
    ->everyFiveMinutes()
    ->name('run-boosts')
    ->name("task_" . uniqid())->withoutOverlapping();

// Proses antrean job (order webhook, download resi, sync historis) lewat cron —
// tanpa perlu queue worker daemon terpisah. Drain tiap menit sampai antrean kosong.
Schedule::call(fn () => Artisan::call('queue:work', ['--stop-when-empty' => true, '--max-time' => 55, '--tries' => 3, '--sleep' => 1]))
    ->everyMinute()
    ->name('queue-work')
    ->name("task_" . uniqid())->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:cleanup-labels'))
    ->dailyAt('01:00');
