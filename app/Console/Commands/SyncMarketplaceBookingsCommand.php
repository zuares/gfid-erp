<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use App\Models\Store;
use App\Jobs\SyncMarketplaceBookings;

class SyncMarketplaceBookingsCommand extends Command
{
    protected $signature = 'marketplace:sync-bookings {--full : Tarik riwayat penuh (~6 bulan)} {--sync : Jalankan langsung (sinkron), bukan lewat antrean}';
    protected $description = 'Sinkronisasi Pesanan Kilat (booking) dari semua toko Shopee aktif';

    public function handle(): int
    {
        $full = (bool) $this->option('full');
        $sync = (bool) $this->option('sync');

        $stores = Store::with('channel')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->get()
            ->filter(fn ($s) => ! blank($s->credential('access_token')));

        if ($stores->isEmpty()) {
            $this->warn('Tidak ada toko Shopee aktif yang terkoneksi.');
            return self::SUCCESS;
        }

        $this->info('Memulai sinkronisasi booking' . ($full ? ' (riwayat penuh)' : '') . '...');

        foreach ($stores as $store) {
            $job = new SyncMarketplaceBookings($store, null, null, $full);
            if ($sync) {
                $this->line("  → Sinkron langsung: {$store->name}");
                dispatch_sync($job);
            } else {
                $this->line("  → Dispatch ke antrean: {$store->name}");
                dispatch($job);
            }
        }

        $this->info('Selesai untuk ' . $stores->count() . ' toko.');
        return self::SUCCESS;
    }
}
