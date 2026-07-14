<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use App\Models\Store;
use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Services\Channels\ChannelManager;

/**
 * Tarik & simpan "Pesanan Kilat" (booking) dari Shopee ke DB.
 * Anti-duplikat via booking_sn unik + updateOrCreate.
 */
class SyncMarketplaceBookings implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $store;
    public $timeFrom;
    public $timeTo;
    public $fullSync;

    public function __construct(Store $store, ?int $timeFrom = null, ?int $timeTo = null, bool $fullSync = false)
    {
        $this->store    = $store;
        $this->timeFrom = $timeFrom;
        $this->timeTo   = $timeTo;
        $this->fullSync = $fullSync;
    }

    public function handle(ChannelManager $manager): void
    {
        try {
            $driver = $manager->driver($this->store);
            if (! method_exists($driver, 'getBookingList')) {
                return;
            }

            // Shopee membatasi rentang create_time maksimal 15 hari per panggilan.
            $span = 15 * 86400;
            if ($this->fullSync) {
                $end   = time();
                $start = strtotime('-6 months', $end);
            } else {
                $end   = $this->timeTo ?? time();
                $start = $this->timeFrom ?? ($end - (14 * 86400));
            }

            $windows = [];
            if ($end <= $start) {
                $windows[] = [$start, max($start, $end)];
            } else {
                for ($s = $start; $s < $end; $s += $span) {
                    $windows[] = [$s, min($s + $span - 1, $end)];
                }
            }

            foreach ($windows as [$wFrom, $wTo]) {
                $cursor = '';
                $guard  = 0;
                do {
                    $res = $driver->getBookingList($this->store, $wFrom, $wTo, 50, $cursor);
                    if (! empty($res['error'])) {
                        Log::error("SyncMarketplaceBookings [{$this->store->id}]: " . ($res['message'] ?? $res['error']));
                        break;
                    }

                    $list = $res['response']['booking_list'] ?? [];
                    foreach ($list as $b) {
                        $this->processBooking($b);
                    }

                    $more   = $res['response']['more'] ?? $res['response']['has_more'] ?? false;
                    $cursor = (string) ($res['response']['next_cursor'] ?? '');
                } while ($more && $cursor !== '' && ++$guard < 50);
            }
        } catch (\Throwable $e) {
            Log::error("Exception in SyncMarketplaceBookings [{$this->store->id}]: " . $e->getMessage());
        }
    }

    protected function processBooking(array $b): void
    {
        $bookingSn = $b['booking_sn'] ?? null;
        if (! $bookingSn) {
            return;
        }

        $orderSn = $b['order_sn'] ?? null;

        MarketplaceBooking::updateOrCreate(
            [
                'store_id'   => $this->store->id,
                'booking_sn' => $bookingSn,
            ],
            [
                'order_sn'         => $orderSn ?: null,
                'booking_status'   => $b['booking_status'] ?? null,
                'shipping_carrier' => $b['shipping_carrier'] ?? null,
                'create_time'      => $b['create_time'] ?? null,
                'update_time'      => $b['update_time'] ?? null,
                'raw_json'         => $b,
            ]
        );

        // Kalau booking ini terkait order yang sudah tersimpan, tautkan booking_sn-nya.
        if ($orderSn) {
            $order = MarketplaceOrder::where('store_id', $this->store->id)
                ->where('channel_order_id', $orderSn)
                ->first();
            if ($order && blank($order->booking_sn)) {
                $order->update(['booking_sn' => $bookingSn]);
            }
        }
    }
}
