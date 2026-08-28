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

// ─────────────────────────────────────────────────────────────────────────
// CATATAN: name() harus statis (jangan uniqid) — withoutOverlapping() memakai
// nama event sebagai kunci mutex; nama yang berubah tiap proses membuat
// pencegahan overlap tidak pernah bekerja.
//
// Jadwal sales:rebuild-daily-item-sales & inventory:recalc-ads-from-daily
// didefinisikan SEKALI di bootstrap/app.php (01:00 / 01:10) — jangan
// diduplikasi di sini.
//
// Jadwal ads (marketplace:sync-ads / --hourly) juga ada di bootstrap/app.php.
// ─────────────────────────────────────────────────────────────────────────

// ── Storefront product ranking (setiap jam) ───────────────────────────────
Schedule::call(fn () => Artisan::call('storefront:rank-products'))
    ->hourly()
    ->name('storefront-rank')
    ->withoutOverlapping();

// Order: tarik data order terbaru tiap 5 menit
Schedule::call(fn () => Artisan::call('marketplace:sync-orders'))
    ->everyFiveMinutes()
    ->name('sync-orders')
    ->withoutOverlapping();

// Verifikasi status order yang masih aktif ke API setelah sync order. Sweep ini
// juga mencakup SHIPPED/TO_CONFIRM_RECEIVE supaya COMPLETED tidak menunggu
// histori sync besar, serta memakai guard agar response API yang stale tidak
// menurunkan status lokal.
Schedule::command('marketplace:verify-processed-orders', [
        '--apply' => true,
        '--limit' => 50,
    ])
    ->cron('2-59/5 * * * *')
    ->name('verify-processed-orders')
    ->withoutOverlapping();

// Repair data lokal yang rusak tanpa memajukan status order berdasarkan
// fulfillment lokal saja. Pemindahan status ditangani verifier API di atas.
Schedule::command('marketplace:repair-stuck-orders', [
        '--apply' => true,
        '--limit' => 500,
        '--only-missing-fulfillment' => true,
    ])
    ->cron('3-59/5 * * * *')
    ->name('repair-stuck-orders')
    ->withoutOverlapping();

// Pesanan Kilat (booking) + enrichment order_sn tiap jam
// (dipindahkan dari app/Console/Kernel.php yang tidak dipakai Laravel 12)
Schedule::call(fn () => Artisan::call('marketplace:sync-bookings'))
    ->hourlyAt(17) // digeser dari :00 agar tidak tabrakan dengan sync lain
    ->name('sync-bookings')
    ->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:sync-returns'))
    ->hourlyAt(37) // digeser dari :00 agar tidak tabrakan dengan sync lain
    ->name('sync-returns')
    ->withoutOverlapping();

// Jalur pengaman order BARU: tidak memakai cursor backlog, sehingga order hari
// ini tidak menunggu sync settlement histori yang dapat berhenti di batas runtime.
Schedule::call(fn () => Artisan::call('marketplace:sync-settlements', [
        '--from' => now()->subDay()->toDateString(),
        '--to' => now()->toDateString(),
        '--limit' => 100,
        '--newest-first' => true,
    ]))
    ->everyFiveMinutes()
    ->name('sync-recent-settlements')
    ->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:sync-settlements', [
        '--all' => true,
    ]))
    ->cron('7 */4 * * *') // tiap 4 jam di menit :07 (sebelumnya :00, tabrakan dengan sync-finance)
    ->name('sync-settlements')
    ->withoutOverlapping();

// Ambil estimated_escrow_amount untuk order pending setelah sync settlement nominal.
Schedule::call(fn () => Artisan::call('marketplace:sync-income-details'))
    ->cron('15 */4 * * *')
    ->name('sync-income-details')
    ->withoutOverlapping();

// Isi tanggal dana cair lewat GetEscrowList setelah settlement nominal
// selesai. Jalur terpisah supaya endpoint release yang bermasalah tidak menggagalkan
// sync settlement utama; lock per toko tetap sama agar tidak overlap.
Schedule::call(fn () => Artisan::call('marketplace:sync-released-settlements'))
    ->cron('22 */4 * * *')
    ->name('sync-released-settlements')
    ->withoutOverlapping();

// Finance: sync order → settlement → COGS setiap 4 jam (profit per order)
Schedule::call(fn () => Artisan::call('marketplace:sync-finance', [
        '--months' => 1,
        '--mode' => 'missing',
    ]))
    ->cron('37 */4 * * *') // tiap 4 jam di menit :37 (sebelumnya :00, tabrakan dengan sync-settlements)
    ->name('sync-finance')
    ->withoutOverlapping();

// Biaya iklan wallet Shopee adalah sumber subledger terpisah dari order,
// settlement, dan HPP. Ambil rolling 15 hari agar koreksi transaksi terbaru
// ikut tersinkron; service memecah rentang yang lebih panjang saat backfill.
Schedule::call(fn () => Artisan::call('marketplace:sync-shopee-ad-wallet', [
        '--from' => now()->subDays(14)->toDateString(),
        '--to' => now()->toDateString(),
    ]))
    ->cron('52 */4 * * *')
    ->name('sync-shopee-ad-wallet')
    ->withoutOverlapping();

Schedule::call(fn () => Artisan::call('marketplace:sync-chats'))
    ->everyMinute()
    ->name('sync-chats')
    ->withoutOverlapping();

// Produk: sync dari Shopee + snapshot harian metrik (stok/harga/terjual)
Schedule::call(fn () => Artisan::call('marketplace:snapshot-products', ['--sync' => true]))
    ->dailyAt('23:45')
    ->name('snapshot-products')
    ->withoutOverlapping();

// Naikkan Produk: mesin boost terjadwal (jam-tetap + rotasi otomatis, maks 5 / 4 jam)
Schedule::call(fn () => Artisan::call('marketplace:run-boosts'))
    ->everyFiveMinutes()
    ->name('run-boosts')
    ->withoutOverlapping();

// Claim jadwal pause/resume Ads dan kirim eksekusinya ke queue ads.
Schedule::command('marketplace:run-ads-campaign-schedules')
    ->everyMinute()
    ->name('run-ads-campaign-schedules')
    ->withoutOverlapping();

// Proses antrean job CEPAT (order webhook, download resi) lewat cron —
// tanpa perlu queue worker daemon terpisah. Drain tiap menit sampai antrean kosong.
// --queue=default,labels: queue default (webhook, promosi kilat) SELALU didahulukan;
// unduhan resi (queue 'labels') hanya diproses saat default kosong — resi tidak
// pernah lagi menyumbat pekerjaan real-time.
Schedule::call(fn () => Artisan::call('queue:work', ['--queue' => 'default,labels', '--stop-when-empty' => true, '--max-time' => 55, '--tries' => 3, '--sleep' => 1]))
    ->everyMinute()
    ->name('queue-work')
    ->withoutOverlapping();

// Antrean 'ads' khusus sinkronisasi Shopee Ads. Job ini sengaja dipisah dari
// default/labels karena satu rentang tanggal dapat melakukan banyak panggilan
// API dan memiliki timeout job sampai 30 menit.
Schedule::call(fn () => Artisan::call('queue:work', [
    '--queue' => 'ads',
    '--stop-when-empty' => true,
    '--max-time' => 280,
    '--timeout' => 1800,
    '--tries' => 10,
    '--sleep' => 2,
]))
    ->everyMinute()
    ->name('queue-work-ads')
    ->withoutOverlapping();

// Antrean 'heavy' KHUSUS pekerjaan lama (backfill histori, sync rentang >7 hari).
// Dipisah supaya job berat berjam-jam TIDAK menyumbat webhook/resi di queue default.
// withoutOverlapping: hanya satu worker heavy pada satu waktu.
Schedule::call(fn () => Artisan::call('queue:work', ['--queue' => 'heavy', '--stop-when-empty' => true, '--max-time' => 280, '--tries' => 1, '--sleep' => 2]))
    ->everyFiveMinutes()
    ->name('queue-work-heavy')
    ->withoutOverlapping();

// Audit alokasi inventory harian
// (dipindahkan dari app/Console/Kernel.php yang tidak dipakai Laravel 12)
Schedule::call(fn () => Artisan::call('inventory:audit-allocated'))
    ->dailyAt('00:45')
    ->name('audit-inventory-allocated')
    ->withoutOverlapping();

// Auto-cleanup file resi (.pdf.gz) setiap malam pukul 01:00
Schedule::call(fn () => Artisan::call('marketplace:cleanup-labels'))
    ->dailyAt('01:00')
    ->name('cleanup-labels')
    ->withoutOverlapping();

// Pertahankan log API Shopee hanya 14 hari agar payload metadata tidak
// tumbuh tanpa batas. Command akan membatalkan cleanup jika SQLite korup.
Schedule::command('shopee:cleanup-api-logs', [
        '--days' => 14,
        '--redact-days' => 1,
        '--force' => true,
    ])
    ->dailyAt('01:20')
    ->name('cleanup-shopee-api-logs')
    ->withoutOverlapping();
