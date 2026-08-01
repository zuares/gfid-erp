<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

/**
 * CATATAN (Laravel 12): class ini TIDAK dipakai.
 *
 * Laravel 12 memakai bootstrap/app.php (withSchedule + withRouting commands),
 * sehingga schedule() dan commands() di sini tidak pernah dipanggil.
 *
 * Semua jadwal aktif ada di:
 *   - bootstrap/app.php  → crm:weekly-summary, sales:rebuild-daily-item-sales,
 *     inventory:recalc-ads-from-daily, marketplace:sync-ads (daily + hourly)
 *   - routes/console.php → sync-orders, sync-bookings, sync-returns,
 *     sync-settlements, sync-finance, sync-chats, snapshot-products,
 *     run-boosts, queue-work, queue-work-ads, audit-inventory-allocated, cleanup-labels, dll.
 *
 * Jangan tambahkan jadwal baru di sini. File ini boleh dihapus sepenuhnya
 * (git rm app/Console/Kernel.php) — dipertahankan hanya sebagai penanda.
 */
class Kernel extends ConsoleKernel
{
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        // Sengaja kosong — lihat catatan di atas.
    }

    protected function commands(): void
    {
        // Sengaja kosong — command di app/Console/Commands ter-load otomatis
        // oleh Laravel 12.
    }
}
