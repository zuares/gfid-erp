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

        $data = $bookings->map(fn ($b) => [
            'store_id'                 => $b->store_id,
            'store_name'               => optional($b->store)->name,
            'booking_sn'               => $b->booking_sn,
            'order_sn'                 => $b->order_sn ?: optional($b->order_data)->channel_order_id,
            'booking_status'           => $b->booking_status,
            'shipping_carrier'         => $b->shipping_carrier,
            'tracking_number'          => $b->tracking_number ?: optional($b->order_data)->booking_sn, // MarketplaceOrder stores tracking no in booking_sn
            'package_number'           => $b->package_number,
            'shipping_document_status' => $b->shipping_document_status,
            'needs_shipping'           => $b->needsShipping(),
            'create_time'              => $b->create_time,
            'update_time'              => $b->update_time,
            'items'                    => (!empty($b->items) && is_array($b->items)) ? $b->items : (optional($b->order_data)->items ?? []),
        ])->toArray();

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

    /** Detail booking langsung dari API Shopee. */
    public function detail(Store $store, string $bookingSn)
    {
        try {
            $driver  = $this->manager->driver($store);
            $booking = MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)->first();

            // get_booking_detail Shopee memakai order_sn; pakai order_sn bila ada.
            $ref = $booking && $booking->order_sn ? $booking->order_sn : $bookingSn;

            if (! method_exists($driver, 'getBookingDetail')) {
                return response()->json(['error' => 'Not supported'], 400);
            }
            $res = $driver->getBookingDetail($store, $ref);
            if (! empty($res['error'])) {
                return response()->json(['error' => $res['message'] ?? $res['error']], 400);
            }
            return response()->json($res['response'] ?? $res);
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

            // Terima pilihan pickup/dropoff dari UI (opsional).
            $params = [];
            if ($request->filled('pickup')) {
                $params['pickup'] = $request->input('pickup');
            }
            if ($request->filled('dropoff')) {
                $params['dropoff'] = $request->input('dropoff');
            }

            $res = $driver->shipBooking($store, $bookingSn, $params);
            if (! empty($res['error'])) {
                return response()->json(['error' => $res['message'] ?? $res['error']], 400);
            }

            // Coba tarik nomor resi terbaru & simpan ke DB.
            $tracking = null;
            if (method_exists($driver, 'getBookingTrackingNumber')) {
                try {
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

            return response()->json([
                'success'         => true,
                'message'         => 'Pengiriman kilat berhasil diatur.',
                'tracking_number' => $tracking,
            ]);
        } catch (\Exception $e) {
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }
}
