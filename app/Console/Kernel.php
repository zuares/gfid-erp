<?php

namespace App\Console;

use Illuminate\Foundation\Console\Kernel as ConsoleKernel;

class Kernel extends ConsoleKernel
{
    protected function schedule(\Illuminate\Console\Scheduling\Schedule $schedule): void
    {
        $schedule->command('inventory:audit-allocated')->dailyAt('01:00');
        
        // Cron Job Penjaga Masa Kini: Tarik data retur 15 hari terakhir setiap jam
        $schedule->call(function () {
            $stores = \App\Models\Store::where('is_active', true) // toko nonaktif dilewati
                ->whereHas('channel', function($q) {
                    $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
                })->get();
            
            foreach ($stores as $store) {
                // Biarkan parameter tanggal kosong agar otomatis menggunakan 15 hari terakhir
                dispatch(new \App\Jobs\SyncMarketplaceReturns($store));
            }
        })->hourly()->name('sync-marketplace-returns')->withoutOverlapping();

        // Cron Job Penjaga Masa Kini (Orders): Tarik data order 3 hari terakhir (default command) setiap jam
        $schedule->command('marketplace:sync-orders')
                 ->hourly()
                 ->withoutOverlapping();

        // Cron Job: Tarik data Pesanan Kilat (booking) + enrichment order_sn setiap jam.
        // withoutOverlapping mencegah dua sync menulis bersamaan (kurangi "database is locked").
        $schedule->command('marketplace:sync-bookings')
                 ->hourly()
                 ->withoutOverlapping();

        // Cron Job: Sinkronisasi data keuangan (Order, Settlement, HPP, Ads) setiap 4 jam
        $schedule->command('marketplace:sync-finance')
                 ->everyFourHours()
                 ->withoutOverlapping();
    }

    protected function commands(): void
    {
        $this->load(__DIR__ . '/Commands');
        // require base_path('routes/console.php'); // opsional
    }
}
