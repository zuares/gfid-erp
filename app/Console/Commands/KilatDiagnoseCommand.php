<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Schema;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;

class KilatDiagnoseCommand extends Command
{
    protected $signature = 'marketplace:kilat-diagnose';
    protected $description = 'Diagnosa kenapa order kilat tidak muncul di halaman Orders';

    public function handle(): int
    {
        if (! Schema::hasTable('marketplace_bookings')) {
            $this->error('Tabel marketplace_bookings TIDAK ADA. Jalankan: php artisan migrate');
            return self::FAILURE;
        }

        $totalBooking = MarketplaceBooking::count();
        $withOrderSn  = MarketplaceBooking::whereNotNull('order_sn')->where('order_sn', '!=', '')->count();

        $orderSns = MarketplaceBooking::whereNotNull('order_sn')->where('order_sn', '!=', '')
            ->pluck('order_sn')->unique()->values();

        $matchOrders = MarketplaceOrder::whereIn('channel_order_id', $orderSns)->count();

        $latest200 = MarketplaceOrder::latest('ordered_at')->limit(200)->pluck('channel_order_id');
        $inLatest  = MarketplaceBooking::whereIn('order_sn', $latest200)->count();

        $this->info('=== Diagnosa Pesanan Kilat ↔ Orders ===');
        $this->line("1. Total booking                         : {$totalBooking}");
        $this->line("2. Booking punya order_sn                : {$withOrderSn}");
        $this->line("3. order_sn booking cocok dgn order      : {$matchOrders}");
        $this->line("4. Cocok & ada di 200 order terbaru      : {$inLatest}");
        $this->newLine();

        if ($withOrderSn === 0) {
            $this->warn('→ Booking belum punya order_sn. Jalankan: php artisan marketplace:sync-bookings --sync --full');
        } elseif ($matchOrders === 0) {
            $this->warn('→ order_sn booking tidak cocok dengan order mana pun. Order kilat kemungkinan belum tersinkron ke marketplace_orders. Sinkronkan order (marketplace:sync-orders / tarik histori).');
        } elseif ($inLatest === 0) {
            $this->warn('→ Order kilat ADA tapi lebih lama dari 200 terbaru. Perbaikan localOrders (menyertakan order kilat di luar 200) sudah dibuat — pastikan kode terbaru ter-deploy.');
        } else {
            $this->info('→ Data sudah benar. Jika Orders masih kosong dari kilat, pastikan kode is_kilat + orders.blade terbaru ter-deploy & clear cache (config/view). Cek juga tab "Sedang Dikemas" (kilat yang sudah diatur pindah ke sana).');
        }

        return self::SUCCESS;
    }
}
