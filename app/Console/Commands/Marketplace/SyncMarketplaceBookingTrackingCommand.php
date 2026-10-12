<?php

namespace App\Console\Commands\Marketplace;

use App\Models\MarketplaceBooking;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class SyncMarketplaceBookingTrackingCommand extends Command
{
    protected $signature = 'marketplace:sync-booking-tracking
        {--store= : Hanya toko tertentu (stores.id)}
        {--limit=30 : Maksimum booking yang diperiksa per eksekusi}
        {--stale-minutes=60 : Periksa ulang setelah jumlah menit ini}';

    protected $description = 'Perbarui status perjalanan booking Shopee ke gudang.';

    public function handle(ChannelManager $manager): int
    {
        $limit = max(1, min(100, (int) $this->option('limit')));
        $staleMinutes = max(10, (int) $this->option('stale-minutes'));
        $stores = Store::query()
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->when($this->option('store'), fn ($query, $storeId) => $query->whereKey((int) $storeId))
            ->orderBy('id')->get();

        $checked = 0;
        $errors = 0;
        $attempted = 0;

        foreach ($stores as $store) {
            $remaining = $limit - $attempted;
            if ($remaining <= 0) {
                break;
            }

            $bookings = MarketplaceBooking::query()
                ->where('store_id', $store->id)
                ->where('booking_status', 'SHIPPED')
                ->where(fn ($query) => $query->whereNull('tracking_checked_at')
                    ->orWhere('tracking_checked_at', '<=', now()->subMinutes($staleMinutes)))
                ->orderByRaw('CASE WHEN tracking_checked_at IS NULL THEN 0 ELSE 1 END')
                ->orderBy('tracking_checked_at')->orderBy('id')
                ->limit($remaining)->get();

            if ($bookings->isEmpty()) {
                continue;
            }

            try {
                $driver = $manager->driver($store);
            } catch (\Throwable $exception) {
                $errors += $bookings->count();
                Log::warning('Booking tracking sync: gagal membuat driver Shopee.', [
                    'store_id' => $store->id,
                    'message' => $exception->getMessage(),
                ]);
                continue;
            }

            if (! method_exists($driver, 'getBookingTrackingInfo')) {
                continue;
            }

            foreach ($bookings as $booking) {
                $attempted++;
                try {
                    $response = $driver->getBookingTrackingInfo($store, $booking->booking_sn);
                    if (! empty($response['error'])) {
                        throw new \RuntimeException((string) ($response['message'] ?? $response['error']));
                    }

                    $logisticsStatus = strtoupper(trim((string) data_get($response, 'response.logistics_status', '')));
                    $booking->forceFill([
                        'tracking_logistics_status' => $logisticsStatus !== '' ? $logisticsStatus : null,
                        'tracking_checked_at' => now(),
                    ])->save();
                    $checked++;
                } catch (\Throwable $exception) {
                    $booking->forceFill(['tracking_checked_at' => now()])->save();
                    $errors++;
                    Log::warning('Booking tracking sync: gagal mengambil status booking.', [
                        'store_id' => $store->id,
                        'booking_sn' => $booking->booking_sn,
                        'message' => $exception->getMessage(),
                    ]);
                }
            }
        }

        $this->info("Booking tracking diperiksa: {$checked}; gagal: {$errors}.");

        return self::SUCCESS;
    }
}
