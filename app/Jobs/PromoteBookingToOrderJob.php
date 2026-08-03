<?php

namespace App\Jobs;

use App\Models\MarketplaceBooking;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;

/**
 * Polling promosi booking Pesanan Kilat → order bernomor pesanan.
 *
 * Shopee mencocokkan booking ke order SECARA ASINKRON setelah pengiriman diatur
 * (ship_booking) — order_sn baru muncul di get_booking_detail begitu booking
 * berstatus MATCHED, biasanya beberapa detik s/d beberapa menit kemudian.
 * Satu kali cek langsung setelah arrange (seperti sebelumnya) hampir selalu
 * terlalu cepat, sehingga nomor pesanan baru muncul di halaman Orders satu jam
 * kemudian lewat sync terjadwal.
 *
 * Job ini mengecek ulang dengan jadwal mundur (15 dtk → 5 mnt, total ±15 menit
 * seperti perilaku platform Shopee). Begitu order_sn tersedia: order ditarik via
 * syncOrdersBySn() dan ditautkan ke booking — baris pseudo-booking di halaman
 * Orders otomatis berganti menjadi order bernomor pesanan. Idempotent: aman
 * dijalankan berkali-kali / bertabrakan dengan webhook & sync per jam.
 */
class PromoteBookingToOrderJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /** Maksimal percobaan (dispatch awal + release ulang). */
    public int $tries = 8;

    public int $timeout = 120;

    public function __construct(
        public int $storeId,
        public string $bookingSn,
    ) {}

    /** Jeda (detik) sebelum percobaan berikutnya, per nomor attempt. */
    protected function nextDelay(): int
    {
        return [15, 30, 60, 120, 180, 300, 300][$this->attempts() - 1] ?? 300;
    }

    public function handle(MarketplaceSyncService $sync): void
    {
        $store = Store::find($this->storeId);
        if (! $store || ! $store->is_active) {
            return;
        }

        // Sudah tertaut (oleh webhook / sync per jam / percobaan sebelumnya)? Selesai.
        $booking = MarketplaceBooking::where('store_id', $store->id)
            ->where('booking_sn', $this->bookingSn)
            ->first();
        if (! $booking) {
            return;
        }

        try {
            $orderSn = $sync->promoteBookingToOrder($store, $this->bookingSn);
        } catch (\Throwable $e) {
            Log::warning("PromoteBookingToOrderJob [{$this->storeId}] {$this->bookingSn}: " . $e->getMessage());
            $orderSn = null;
        }

        if ($orderSn) {
            Log::info("PromoteBookingToOrderJob: booking {$this->bookingSn} → order {$orderSn} (attempt {$this->attempts()}).");
            return;
        }

        // Belum MATCHED — antre ulang dengan jeda mundur, sampai tries habis.
        if ($this->attempts() < $this->tries) {
            $this->release($this->nextDelay());
        } else {
            Log::info("PromoteBookingToOrderJob: booking {$this->bookingSn} belum MATCHED setelah {$this->tries} percobaan — diserahkan ke sync per jam / webhook.");
        }
    }
}
