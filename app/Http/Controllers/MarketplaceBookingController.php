<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Store;
use App\Models\MarketplaceBooking;
use App\Services\Channels\ChannelManager;

/**
 * Modul "Pesanan Kilat" (booking / fulfillment gudang Shopee).
 */
class MarketplaceBookingController extends Controller
{
    protected ChannelManager $manager;

    public function __construct(ChannelManager $manager)
    {
        $this->manager = $manager;
    }

    /** Toko Shopee aktif yang punya token. */
    protected function shopeeStores()
    {
        return Store::with('channel')
            ->where('is_active', true)
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->get()
            ->filter(fn ($s) => ! blank($s->credential('access_token')))
            ->values();
    }

    public function index(Request $request)
    {
        $stores = $this->shopeeStores();
        return view('marketplace.kilat', compact('stores'));
    }

    /** Baca daftar booking dari DATABASE (agregat lintas toko, tanpa batas tanggal). */
    public function stored(Request $request)
    {
        $status = trim((string) $request->query('status', ''));
        $search = trim((string) $request->query('search', ''));

        $storeIds = Store::whereHas('channel', fn ($q) => $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']))
            ->pluck('id');

        $q = MarketplaceBooking::with('store')->whereIn('store_id', $storeIds);

        if ($status !== '') {
            $q->where('booking_status', $status);
        }
        if ($search !== '') {
            $q->where(fn ($w) => $w->where('booking_sn', 'like', "%{$search}%")
                ->orWhere('order_sn', 'like', "%{$search}%")
                ->orWhere('tracking_number', 'like', "%{$search}%"));
        }

        $bookings = $q->orderByDesc('create_time')->limit(1000)->get();

        $data = $bookings->map(function ($b) {
            // Ambil order tertaut SEKALI saja (accessor ini query DB tiap akses).
            $od = $b->order_data;
            return [
                'store_id'                 => $b->store_id,
                'store_name'               => optional($b->store)->name,
                'booking_sn'               => $b->booking_sn,
                'order_sn'                 => $b->order_sn ?: optional($od)->channel_order_id,
                'booking_status'           => $b->booking_status,
                // Kurir & resi: pakai milik booking, jika kosong ambil dari order tertaut.
                // (MarketplaceOrder menyimpan AWB di shipping_awb_no, sebagian juga di booking_sn.)
                'shipping_carrier'         => $b->shipping_carrier ?: optional($od)->shipping_carrier,
                'tracking_number'          => $b->tracking_number
                    ?: optional($od)->shipping_awb_no
                    ?: optional($od)->booking_sn,
                'package_number'           => $b->package_number,
                'shipping_document_status' => $b->shipping_document_status,
                'needs_shipping'           => $b->needsShipping(),
                'create_time'              => $b->create_time,
                'update_time'              => $b->update_time,
                'meta'                     => $b->meta,
                'items'                    => (!empty($b->items) && is_array($b->items)) ? $b->items : (optional($od)->items ?? []),
                'fulfillment_status'       => optional($od)->fulfillment?->status,
            ];
        })->toArray();

        return response()->json([
            'data'           => $data,
            'count'          => count($data),
            'source'         => 'db',
            'stores_queried' => $storeIds->count(),
        ]);
    }

    /** Tarik & simpan booking dari Shopee untuk SEMUA toko aktif. */
    public function syncAll(Request $request)
    {
        @set_time_limit(300);
        $full = $request->boolean('full', false);

        $synced = 0;
        $errors = [];
        foreach ($this->shopeeStores() as $store) {
            try {
                dispatch_sync(new \App\Jobs\SyncMarketplaceBookings($store, null, null, $full));
                $synced++;
            } catch (\Throwable $e) {
                $errors[] = "[{$store->name}] " . $e->getMessage();
            }
        }

        return response()->json(['success' => true, 'stores_synced' => $synced, 'errors' => $errors]);
    }

    /**
     * Detail booking dari API Shopee.
     * - Booking yang sudah tertaut order → get_order_detail (paling lengkap: alamat, item, resi).
     * - Booking murni (belum ada order_sn) → get_booking_detail (booking_sn_list).
     * Selalu dinormalisasi ke bentuk { order_list: [...] } agar UI seragam.
     */
    public function detail(Store $store, string $bookingSn)
    {
        try {
            $driver  = $this->manager->driver($store);
            $booking = MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)->first();

            $orderSn = $booking?->order_sn;

            if ($orderSn && method_exists($driver, 'getOrderDetail')) {
                $res = $driver->getOrderDetail($store, [$orderSn]);
                if (! empty($res['error'])) {
                    return response()->json(['error' => $res['message'] ?? $res['error']], 400);
                }
                $list = $res['response']['order_list'] ?? [];
                // Resi kurir ASLI (SPXID…) tidak ikut di get_order_detail — package_list hanya
                // berisi package_number (OFG…). Ambil resi sebenarnya via get_tracking_number.
                if (! empty($list) && method_exists($driver, 'getTrackingNumber')) {
                    try {
                        $trk    = $driver->getTrackingNumber($store, $orderSn);
                        $realTn = $trk['response']['tracking_number'] ?? null;
                        if ($realTn) {
                            $list[0]['tracking_number'] = $realTn;
                        }
                    } catch (\Throwable $e) {
                        // biarkan; resi bisa belum di-assign kurir
                    }
                }
                return response()->json(['order_list' => $list]);
            }

            if (! method_exists($driver, 'getBookingDetail')) {
                return response()->json(['error' => 'Not supported'], 400);
            }
            $res = $driver->getBookingDetail($store, $bookingSn);
            if (! empty($res['error'])) {
                return response()->json(['error' => $res['message'] ?? $res['error']], 400);
            }
            $list = $res['response']['booking_list'] ?? $res['response']['order_list'] ?? [];
            if (! empty($list) && method_exists($driver, 'getBookingTrackingNumber')) {
                try {
                    $trk    = $driver->getBookingTrackingNumber($store, $bookingSn);
                    $realTn = $trk['response']['tracking_number'] ?? null;
                    if ($realTn) {
                        $list[0]['tracking_number'] = $realTn;
                    }
                } catch (\Throwable $e) {
                    // biarkan
                }
            }
            return response()->json(['order_list' => $list]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Timeline pelacakan pengiriman booking (via order tertaut).
     * Mengembalikan nomor resi asli + daftar riwayat status logistik.
     */
    public function tracking(Store $store, string $bookingSn)
    {
        try {
            $driver  = $this->manager->driver($store);
            $booking = MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)->first();
            $orderSn = ($booking && $booking->order_sn) ? $booking->order_sn : null;

            $tracking        = null;
            $trackingInfo    = [];
            $logisticsStatus = null;
            $message         = null;

            // 1) Timeline BOOKING — API paling tepat untuk Pesanan Kilat: pakai booking_sn
            //    langsung, tidak butuh order_sn (get_booking_tracking_info).
            if (method_exists($driver, 'getBookingTrackingInfo')) {
                $res = $driver->getBookingTrackingInfo($store, $bookingSn);
                if (empty($res['error'])) {
                    $resp            = $res['response'] ?? [];
                    $trackingInfo    = $resp['tracking_info'] ?? [];
                    $logisticsStatus = $resp['logistics_status'] ?? null;
                } else {
                    $message = $res['message'] ?? $res['error'];
                }
            }

            // 2) Nomor resi: coba resi booking dulu, lalu resi order (bila ada).
            if (method_exists($driver, 'getBookingTrackingNumber')) {
                try {
                    $tracking = $driver->getBookingTrackingNumber($store, $bookingSn)['response']['tracking_number'] ?? null;
                } catch (\Throwable $e) {
                    // biarkan
                }
            }
            if (! $tracking && $orderSn && method_exists($driver, 'getTrackingNumber')) {
                try {
                    $tracking = $driver->getTrackingNumber($store, $orderSn)['response']['tracking_number'] ?? null;
                } catch (\Throwable $e) {
                    // biarkan
                }
            }

            // 3) Kalau timeline booking kosong tapi order tersedia → fallback timeline order.
            if (empty($trackingInfo) && $orderSn && method_exists($driver, 'getTrackingInfo')) {
                $res = $driver->getTrackingInfo($store, $orderSn);
                if (empty($res['error'])) {
                    $trackingInfo = $res['response']['tracking_info'] ?? [];
                } elseif (! $message) {
                    $message = $res['message'] ?? null;
                }
            }

            if (empty($trackingInfo) && ! $message) {
                $message = 'Belum ada riwayat pelacakan.';
            }

            return response()->json([
                'tracking_number'  => $tracking,
                'logistics_status' => $logisticsStatus,
                'tracking_info'    => $trackingInfo,
                'message'          => empty($trackingInfo) ? $message : null,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Parameter pengiriman (pickup/dropoff) untuk sebuah booking. */
    public function shippingParameter(Store $store, string $bookingSn)
    {
        try {
            $driver = $this->manager->driver($store);
            if (! method_exists($driver, 'getBookingShippingParameter')) {
                return response()->json(['error' => 'Not supported'], 400);
            }
            $res = $driver->getBookingShippingParameter($store, $bookingSn);
            if (! empty($res['error'])) {
                return response()->json(['error' => $res['message'] ?? $res['error']], 400);
            }
            return response()->json($res['response'] ?? $res);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /** Atur pengiriman booking (ship_booking) lalu tarik nomor resi. */
    public function ship(Store $store, string $bookingSn, Request $request)
    {
        try {
            $driver = $this->manager->driver($store);
            if (! method_exists($driver, 'shipBooking')) {
                return response()->json(['error' => 'Not supported on this channel'], 400);
            }

            // Terima pilihan pickup/dropoff dari UI. Kirim sebagai objek {} bukan array []
            // (json_encode PHP mengubah array kosong jadi [] yang ditolak Shopee) — sama
            // seperti arrangeShipment pada order.
            $params = [];
            foreach (['pickup', 'dropoff'] as $method) {
                if ($request->has($method)) {
                    $val = $request->input($method);
                    if (is_array($val) && empty($val)) {
                        $val = new \stdClass();
                    }
                    $params[$method] = $val;
                }
            }

            $res = $driver->shipBooking($store, $bookingSn, $params);

            // Toleransi: sebagian kuril instan/otomatis mengembalikan "already/unsupported"
            // padahal sudah berhasil diatur — perlakukan sebagai sukses.
            if (! empty($res['error'])) {
                $err = (string) $res['error'];
                $tolerable = str_contains($err, 'already')
                    || str_contains($err, 'unsupported')
                    || str_contains($err, 'status_invalid');
                if (! $tolerable) {
                    return response()->json(['error' => $res['message'] ?? $err], 400);
                }
            }

            // Coba tarik nomor resi terbaru & simpan ke DB (AWB Shopee asinkron → beri jeda).
            $tracking = null;
            if (method_exists($driver, 'getBookingTrackingNumber')) {
                try {
                    sleep(2);
                    $trk = $driver->getBookingTrackingNumber($store, $bookingSn);
                    $tracking = $trk['response']['tracking_number'] ?? null;
                } catch (\Throwable $e) {
                    // abaikan; resi bisa menyusul via webhook booking_trackingno (code 24)
                }
            }

            MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)
                ->update(array_filter([
                    'booking_status'  => 'PROCESSED',
                    'tracking_number' => $tracking,
                ]));

            // Setelah diatur: lengkapi order_sn (booking → MATCHED) dan tarik order-nya
            // ke marketplace_orders, supaya halaman Orders menampilkan NOMOR PESANAN
            // (bukan nomor booking) di tab Sedang Dikemas dan seterusnya.
            $this->promoteBookingToOrder($store, $bookingSn);

            return response()->json([
                'success'         => true,
                'message'         => 'Pengiriman kilat berhasil diatur.',
                'tracking_number' => $tracking,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Lengkapi order_sn booking (dari get_booking_detail) lalu tarik order-nya ke
     * marketplace_orders bila belum ada. Dipanggil setelah arrange sukses — dengan
     * begitu baris pseudo-booking di halaman Orders otomatis digantikan baris order
     * asli bernomor pesanan. Kegagalan di sini tidak menggagalkan proses arrange.
     */
    protected function promoteBookingToOrder(Store $store, string $bookingSn): void
    {
        try {
            $booking = MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)->first();
            if (! $booking) {
                return;
            }

            $driver = $this->manager->driver($store);

            // order_sn belum tercatat → ambil dari get_booking_detail (muncul saat MATCHED).
            if (blank($booking->order_sn) && method_exists($driver, 'getBookingDetail')) {
                $det  = $driver->getBookingDetail($store, $bookingSn);
                $list = $det['response']['booking_list'] ?? $det['response']['order_list'] ?? [];
                $d    = collect($list)->firstWhere('booking_sn', $bookingSn) ?? ($list[0] ?? null);
                if (! empty($d['order_sn'])) {
                    $booking->order_sn = $d['order_sn'];
                    $booking->save();
                }
            }

            if (blank($booking->order_sn)) {
                return; // belum MATCHED — nanti dilengkapi webhook/sync per jam.
            }

            // Order lokal belum ada → backfill via detail order.
            $exists = \App\Models\MarketplaceOrder::where('channel_order_id', $booking->order_sn)
                ->orWhere('external_order_id', $booking->order_sn)
                ->exists();
            if (! $exists) {
                app(\App\Services\MarketplaceSyncService::class)->syncOrdersBySn($store, [$booking->order_sn]);
            }

            // Tautkan booking_sn ke order lokal.
            \App\Models\MarketplaceOrder::where('store_id', $store->id)
                ->where('channel_order_id', $booking->order_sn)
                ->whereNull('booking_sn')
                ->update(['booking_sn' => $bookingSn]);
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning(
                "promoteBookingToOrder [{$store->id}] {$bookingSn}: " . $e->getMessage()
            );
        }
    }
}
