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
 *
 * get_booking_list hanya memberi booking_sn + booking_status — order_sn, kurir,
 * nomor paket & item ada di get_booking_detail. Karena itu setelah menarik daftar,
 * job ini melakukan enrichment detail secara batch (booking_sn_list, 50/panggilan).
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

            $bookingSns = [];
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
                        if (! empty($b['booking_sn'])) {
                            $bookingSns[] = $b['booking_sn'];
                        }
                    }

                    $more   = $res['response']['more'] ?? $res['response']['has_more'] ?? false;
                    $cursor = (string) ($res['response']['next_cursor'] ?? '');
                } while ($more && $cursor !== '' && ++$guard < 50);
            }

            // Lengkapi order_sn / kurir / nomor paket / item dari get_booking_detail.
            $this->enrichDetails($driver, array_values(array_unique($bookingSns)));
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

        $attrs = [
            'booking_status' => $b['booking_status'] ?? null,
            'create_time'    => $b['create_time'] ?? null,
            'update_time'    => $b['update_time'] ?? null,
            'raw_json'       => $b,
        ];
        // Jangan menimpa nilai yang sudah terisi dengan null — order_sn/kurir bisa berasal
        // dari get_booking_detail dan tidak selalu ada di get_booking_list.
        if (! empty($b['order_sn']))         $attrs['order_sn'] = $b['order_sn'];
        if (! empty($b['shipping_carrier'])) $attrs['shipping_carrier'] = $b['shipping_carrier'];

        MarketplaceBooking::updateOrCreate(
            ['store_id' => $this->store->id, 'booking_sn' => $bookingSn],
            $attrs
        );

        if (! empty($b['order_sn'])) {
            $this->linkOrder($bookingSn, $b['order_sn']);
        }
    }

    /** Ambil detail booking secara batch untuk melengkapi order_sn, kurir, paket & item. */
    protected function enrichDetails($driver, array $bookingSns): void
    {
        if (empty($bookingSns) || ! method_exists($driver, 'getBookingDetail')) {
            return;
        }

        foreach (array_chunk($bookingSns, 50) as $chunk) {
            try {
                $res  = $driver->getBookingDetail($this->store, implode(',', $chunk));
                $list = $res['response']['booking_list'] ?? $res['response']['order_list'] ?? [];
                if (empty($list)) {
                    continue;
                }

                $models = MarketplaceBooking::where('store_id', $this->store->id)
                    ->whereIn('booking_sn', $chunk)
                    ->get()
                    ->keyBy('booking_sn');

                foreach ($list as $d) {
                    $sn = $d['booking_sn'] ?? null;
                    $m  = $sn ? ($models[$sn] ?? null) : null;
                    if (! $m) {
                        continue;
                    }

                    if (! empty($d['order_sn']))         $m->order_sn = $d['order_sn'];
                    if (! empty($d['shipping_carrier'])) $m->shipping_carrier = $d['shipping_carrier'];

                    $pkg = $d['package_number'] ?? ($d['package_list'][0]['package_number'] ?? null);
                    if (! empty($pkg))                   $m->package_number = $pkg;
                    if (! empty($d['item_list']))        $m->items = $d['item_list'];

                    if ($m->isDirty()) {
                        $m->save();
                    }

                    if (! empty($d['order_sn'])) {
                        $this->linkOrder($sn, $d['order_sn']);
                    }
                }
            } catch (\Throwable $e) {
                Log::warning("SyncMarketplaceBookings enrichDetails [{$this->store->id}]: " . $e->getMessage());
            }
        }
    }

    /** Tautkan booking_sn ke order lokal (bila order-nya sudah tersimpan & belum punya booking_sn). */
    protected function linkOrder(string $bookingSn, string $orderSn): void
    {
        $order = MarketplaceOrder::where('store_id', $this->store->id)
            ->where('channel_order_id', $orderSn)
            ->first();
        if ($order && blank($order->booking_sn)) {
            $order->update(['booking_sn' => $bookingSn]);
        }
    }
}
