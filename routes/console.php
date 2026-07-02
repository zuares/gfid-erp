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
Schedule::command('storefront:rank-products')
    ->hourly()
    ->withoutOverlapping()
    ->runInBackground();

// Update harian setelah ganti hari
Schedule::command('sales:rebuild-daily-item-sales --days=90')
    ->dailyAt('00:05');

Schedule::command('inventory:recalc-ads-from-daily --days=30')
    ->dailyAt('00:10');
