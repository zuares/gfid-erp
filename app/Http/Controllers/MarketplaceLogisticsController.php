<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;

class MarketplaceLogisticsController extends Controller
{
    public function __construct(
        protected ChannelManager $manager
    ) {}

    public function syncAwb(Store $store, string $orderSn): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            $order = MarketplaceOrder::where('store_id', $store->id)
                ->where(function ($q) use ($orderSn) {
                    $q->where('channel_order_id', $orderSn)
                      ->orWhere('external_order_id', $orderSn)
                      ->orWhere('booking_sn', $orderSn);
                })
                ->first();

            $booking = null;
            if (!$order) {
                // Coba cari di tabel booking jika tidak ada di tabel orders (kasus pesanan kilat murni yang belum ada order_sn-nya)
                $booking = \App\Models\MarketplaceBooking::where('store_id', $store->id)
                    ->where('booking_sn', $orderSn)
                    ->first();
                
                if (!$booking) {
                    return response()->json(['error' => 'Order or Booking not found'], 404);
                }
            }

            $awb = null;
            if ($order && !empty($order->booking_sn) && method_exists($driver, 'getBookingTrackingNumber')) {
                $trackingResp = $driver->getBookingTrackingNumber($store, $order->booking_sn);
                $awb = $trackingResp['response']['tracking_number'] ?? null;
            } elseif ($booking && method_exists($driver, 'getBookingTrackingNumber')) {
                $trackingResp = $driver->getBookingTrackingNumber($store, $booking->booking_sn);
                $awb = $trackingResp['response']['tracking_number'] ?? null;
            } elseif ($order && method_exists($driver, 'getTrackingNumber')) {
                $trackingResp = $driver->getTrackingNumber($store, $order->channel_order_id);
                $awb = $trackingResp['response']['tracking_number'] ?? null;
            }
            if (!$awb && method_exists($driver, 'getOrderDetail')) {
                // Ensure we use the actual order_sn for getOrderDetail, as booking_sn will cause an error
                $actualOrderSn = $order ? $order->channel_order_id : ($booking ? $booking->order_sn : null);
                if (!empty($actualOrderSn) && (!$order || $actualOrderSn !== $order->booking_sn)) {
                    $details = $driver->getOrderDetail($store, [$actualOrderSn]);
                    $list = $details['response']['order_list'] ?? [];
                    if (count($list) > 0) {
                        $remoteRaw = $list[0];
                        if (!empty($remoteRaw['package_list'][0]['tracking_number'])) {
                            $awb = $remoteRaw['package_list'][0]['tracking_number'];
                        }
                    }
                }
            }

            if ($awb) {
                if ($order && $order->shipping_awb_no !== $awb) {
                    $order->update(['shipping_awb_no' => $awb]);
                }
                if ($booking && $booking->tracking_number !== $awb) {
                    $booking->update(['tracking_number' => $awb]);
                }
                return response()->json(['success' => true, 'awb' => $awb]);
            }

            $currentAwb = $order ? $order->shipping_awb_no : ($booking ? $booking->tracking_number : null);
            return response()->json(['success' => false, 'message' => 'AWB not found or unchanged', 'awb' => $currentAwb]);

        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error('syncAwb Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString(), 'orderSn' => $orderSn]);
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    /**
     * Get shipping parameters (Pickup/Dropoff info)
     */
    public function getShippingParameter(Store $store, string $orderSn): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            $raw = $driver->getShippingParameter($store, $orderSn);

            // Jaring pengaman Pesanan Kilat: bila $orderSn ternyata booking_sn
            // (baris kilat belum MATCHED), Shopee menjawab "order_sn ... not exist".
            // Alihkan otomatis ke get_booking_shipping_parameter supaya modal
            // Atur Pengiriman tetap bekerja dari halaman/alur mana pun.
            if ($this->looksLikeMissingOrderSn($raw)
                && method_exists($driver, 'getBookingShippingParameter')) {
                // Tanpa filter store_id: record booking bisa tersimpan di store lokal
                // berbeda (booking_sn unik global), dan bisa juga belum tersinkron sama
                // sekali — coba saja endpoint booking; kalau sn memang tidak valid,
                // jawaban error Shopee yang baru yang dikembalikan.
                $raw = $driver->getBookingShippingParameter($store, $orderSn);
            }

            $result = $this->ensureSuccess($raw);
            
            return response()->json($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Deteksi jawaban Shopee "The order_sn {order_sn} is not exist." (dan varian
     * error_param/error_not_exist) — penanda sn yang dikirim bukan order_sn valid,
     * kemungkinan besar booking_sn Pesanan Kilat.
     */
    private function looksLikeMissingOrderSn(array $raw): bool
    {
        if (empty($raw['error'])) {
            return false;
        }
        $msg = strtolower((string) ($raw['message'] ?? ''));
        return str_contains($msg, 'not exist') || str_contains($msg, 'not_exist')
            || str_contains((string) $raw['error'], 'not_exist');
    }

    /**
     * Get booking details (usually for Instant/Sameday delivery to check driver)
     */
    public function getBookingDetail(Store $store, string $orderSn): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            if (method_exists($driver, 'getBookingDetail')) {
                $result = $this->ensureSuccess($driver->getBookingDetail($store, $orderSn));
                return response()->json($result);
            }
            return response()->json(['error' => 'Not supported on this channel'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get raw order detail directly from channel API (for debugging/exploring)
     */
    public function getOrderDetailRaw(Store $store, string $orderSn): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            if (method_exists($driver, 'getOrderDetail')) {
                $result = $this->ensureSuccess($driver->getOrderDetail($store, [$orderSn]));
                return response()->json($result);
            }
            return response()->json(['error' => 'Not supported on this channel'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get raw package detail directly from channel API (for debugging/exploring)
     */
    public function getPackageDetailRaw(Store $store, string $packageNumber): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            if (method_exists($driver, 'getPackageDetail')) {
                $result = $this->ensureSuccess($driver->getPackageDetail($store, $packageNumber));
                return response()->json($result);
            }
            return response()->json(['error' => 'Not supported on this channel'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get raw return list directly from channel API (for debugging/exploring)
     */
    public function getReturnListRaw(Store $store, Request $request): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            if (method_exists($driver, 'getReturnList')) {
                $pageNo = (int) $request->input('page_no', 0);
                $pageSize = (int) $request->input('page_size', 20);
                $result = $this->ensureSuccess($driver->getReturnList($store, $pageNo, $pageSize));
                return response()->json($result);
            }
            return response()->json(['error' => 'Not supported on this channel'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get booking list from channel API (for debugging/exploring)
     */
    public function getBookingList(Store $store, Request $request): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            if (method_exists($driver, 'getBookingList')) {
                $timeFrom = $request->input('time_from', time() - (86400 * 3)); // default last 3 days
                $timeTo = $request->input('time_to', time());
                $status = $request->input('booking_status', '');
                
                $result = $this->ensureSuccess($driver->getBookingList($store, (int)$timeFrom, (int)$timeTo, 20, '', $status));
                return response()->json($result);
            }
            return response()->json(['error' => 'Not supported on this channel'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Get order list from channel API (for debugging/exploring)
     */
    public function getOrderList(Store $store, Request $request): JsonResponse
    {
        try {
            $driver = $this->manager->driver($store);
            if (method_exists($driver, 'getOrders')) {
                $timeFrom = $request->input('time_from', time() - (86400 * 3)); // default last 3 days
                $timeTo = $request->input('time_to', time());
                $status = $request->input('order_status', '');
                
                $result = $this->ensureSuccess($driver->getOrders($store, (int)$timeFrom, (int)$timeTo, 20, '', $status));
                return response()->json($result);
            }
            return response()->json(['error' => 'Not supported on this channel'], 400);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Sync bookings from marketplace and update local database
     */
    public function syncBookings(Store $store, Request $request): JsonResponse
    {
        try {
            $timeFrom = $request->input('time_from') ? (int) $request->input('time_from') : (time() - 86400 * 14);
            $timeTo   = $request->input('time_to')   ? (int) $request->input('time_to')   : time();

            // Gunakan SyncMarketplaceBookings yang sudah memiliki logika propagasi
            // booking_status → order_status secara otomatis.
            dispatch_sync(new \App\Jobs\SyncMarketplaceBookings($store, $timeFrom, $timeTo, false));

            return response()->json([
                'success' => true,
                'message' => 'Sinkronisasi Pesanan Kilat selesai.',
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Arrange shipment (Atur Pengiriman)
     */
    public function arrangeShipment(Request $request, Store $store, string $orderSn): JsonResponse
    {
        try {
            // $request->all() typically contains pickup/dropoff details
            // e.g. ['pickup' => ['address_id' => 123, 'pickup_time_id' => 'abc']]
            $params = $request->except(['_token', 'store', 'orderSn']);
            
            // Shopee hanya mengizinkan SATU metode pengiriman (pickup ATAU dropoff).
            // Jika frontend tidak mengirim apapun (misal 'auto'), kirim salah satu sebagai pancingan.
            $hasPickup = isset($params['pickup']);
            $hasDropoff = isset($params['dropoff']);
            $hasNonIntegrated = isset($params['non_integrated']);

            if (!$hasPickup && !$hasDropoff && !$hasNonIntegrated) {
                $params['pickup'] = new \stdClass();
            } else {
                // Jika dikirim tapi berupa array kosong, ubah jadi object {}
                if ($hasDropoff && is_array($params['dropoff']) && empty($params['dropoff'])) {
                    $params['dropoff'] = new \stdClass();
                }
                if ($hasPickup && is_array($params['pickup']) && empty($params['pickup'])) {
                    $params['pickup'] = new \stdClass();
                }
            }
            // Cek apakah frontend memaksa auto_sync_only
            $isAutoSync = $request->input('auto_sync_only');
            
            $driver = $this->manager->driver($store);
            
            if ($isAutoSync) {
                // Bypass pemanggilan API Shopee shipOrder (yang pasti akan gagal jika sudah dikirim).
                // Kita langsung simulasikan seolah API mengembalikan error 'already_arranged'
                // agar sistem otomatis masuk ke blok penarikan resi.
                $rawResult = ['error' => 'logistics.already_arranged'];
            } else {
                $rawResult = $driver->shipOrder($store, $orderSn, $params);

                // Jaring pengaman Pesanan Kilat: sn ini ternyata booking_sn (belum
                // MATCHED ke order). ship_order menjawab "order_sn is not exist" —
                // alihkan otomatis ke ship_booking, lalu polling promosi order_sn
                // (PromoteBookingToOrderJob) supaya nomor pesanan menyusul.
                // Ini juga menutup kasus proses massal yang memanggil endpoint order
                // untuk semua baris.
                if ($this->looksLikeMissingOrderSn($rawResult)
                    && method_exists($driver, 'shipBooking')) {

                    // Tanpa cek/filter store_id: record booking bisa tersimpan di store
                    // lokal berbeda atau belum tersinkron — booking_sn unik global.
                    $rawResult = $driver->shipBooking($store, $orderSn, $params);

                    $bErr = (string) ($rawResult['error'] ?? '');
                    if ($bErr === '' || str_contains($bErr, 'already') || str_contains($bErr, 'unsupported') || str_contains($bErr, 'status_invalid')) {
                        \App\Models\MarketplaceBooking::where('booking_sn', $orderSn)
                            ->update(['booking_status' => 'PROCESSED']);
                        \App\Jobs\PromoteBookingToOrderJob::dispatch($store->id, $orderSn)
                            ->delay(now()->addSeconds(15));
                    }
                }
            }
            
            // Allow if it's already arranged by marketplace (e.g. Instant/Sameday orders)
            // or has invalid status due to being already processed
            if (isset($rawResult['error']) && $rawResult['error']) {
                $err = $rawResult['error'];
                $allowedErrors = [
                    'logistics.already_arranged', 
                    'logistics.ship_order_already_shipped',
                    'logistics.pickup_address_unsupported',
                    'logistics.status_invalid'
                ];
                
                if (in_array($err, $allowedErrors) || str_contains($err, 'already') || str_contains($err, 'unsupported')) {
                    $rawResult['error'] = ''; 
                    \Illuminate\Support\Facades\Log::info("shipOrder returned $err for $orderSn, treating as success for local status update.");
                }
            }
            
            $result = $this->ensureSuccess($rawResult);
            
            // Ambil data terbaru langsung dari Shopee untuk mendapatkan package_number atau tracking_number
            try {
                // Tunggu 3 detik karena proses AWB Shopee dilakukan secara asynchronous
                sleep(3);
                
                $awb = null;
                if (method_exists($driver, 'getTrackingNumber')) {
                    $trackingResp = $driver->getTrackingNumber($store, $orderSn);
                    $awb = $trackingResp['response']['tracking_number'] ?? null;
                }
                
                if (method_exists($driver, 'getOrderDetail')) {
                    $details = $driver->getOrderDetail($store, [$orderSn]);
                    $list = $details['response']['order_list'] ?? [];
                    if (count($list) > 0) {
                        $rawJson = $list[0];
                        if (!$awb && !empty($rawJson['package_list'][0]['tracking_number'])) {
                            $awb = $rawJson['package_list'][0]['tracking_number'];
                        }
                        
                        MarketplaceOrder::where('store_id', $store->id)
                            ->where('channel_order_id', $orderSn)
                            ->update([
                                'order_status' => 'PROCESSED',
                                'shipping_awb_no' => $awb,
                                'shipping_arranged_at' => now(),
                                'raw_json' => $rawJson
                            ]);
                        return response()->json($result);
                    }
                } elseif ($awb) {
                    MarketplaceOrder::where('store_id', $store->id)
                        ->where('channel_order_id', $orderSn)
                        ->update([
                            'order_status' => 'PROCESSED',
                            'shipping_awb_no' => $awb,
                            'shipping_arranged_at' => now()
                        ]);
                    return response()->json($result);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal fetch order detail setelah arrange shipment: ' . $e->getMessage());
            }
            
            // Fallback: Mark as PROCESSED locally
            MarketplaceOrder::where('store_id', $store->id)
                ->where('channel_order_id', $orderSn)
                ->update([
                    'order_status' => 'PROCESSED',
                    'shipping_arranged_at' => now()
                ]);

            return response()->json($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    /**
     * Download shipping document
     */
    public function printDocument(Store $store, string $orderSn)
    {
        try {
            $driver = $this->manager->driver($store);
            
            $order = MarketplaceOrder::where('store_id', $store->id)
                ->where('channel_order_id', $orderSn)
                ->first();

            // Jika order masih menggunakan booking_sn sebagai ID utamanya, usahakan tukar ke order_sn asli
            if ($order && method_exists($driver, 'getBookingDetail') && $order->booking_sn && $order->channel_order_id === $order->booking_sn) {
                try {
                    $detailRes = $driver->getBookingDetail($store, $order->booking_sn);
                    if (empty($detailRes['error'])) {
                        $bookings = $detailRes['response']['booking_list'] ?? [];
                        foreach ($bookings as $b) {
                            if ($b['booking_sn'] === $order->booking_sn && !empty($b['order_list'][0]['order_sn'])) {
                                $realOrderSn = $b['order_list'][0]['order_sn'];
                                if ($realOrderSn !== $order->channel_order_id) {
                                    $order->update([
                                        'channel_order_id' => $realOrderSn,
                                        'external_order_id' => $realOrderSn
                                    ]);
                                    $orderSn = $realOrderSn; // Gunakan order_sn untuk mencetak
                                    \Illuminate\Support\Facades\Log::info("Swapped {$order->booking_sn} to {$realOrderSn} before printing");
                                }
                                break;
                            }
                        }
                    }
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::warning("Failed to check booking detail before print: " . $e->getMessage());
                }
            }

            $payload = ['order_sn' => $orderSn];
            if ($order) {
                if ($order->shipping_awb_no) {
                    $payload['tracking_number'] = $order->shipping_awb_no;
                }
                
                // Fallback / Priority check for package_number from Shopee V2 API
                $rawJson = $order->raw_json ?? [];
                if (isset($rawJson['package_list']) && is_array($rawJson['package_list'])) {
                    $packageList = $rawJson['package_list'];
                    if (count($packageList) > 0 && isset($packageList[0]['package_number'])) {
                        $payload['package_number'] = $packageList[0]['package_number'];
                    }
                }
                
                // Jika masih kosong, coba ambil langsung dari API sebelum mencetak
                if (!isset($payload['tracking_number']) && !isset($payload['package_number'])) {
                    try {
                        $awb = null;
                        if (method_exists($driver, 'getTrackingNumber')) {
                            $trackingResp = $driver->getTrackingNumber($store, $orderSn);
                            $awb = $trackingResp['response']['tracking_number'] ?? null;
                        }
                        if (!$awb && method_exists($driver, 'getOrderDetail')) {
                            $details = $driver->getOrderDetail($store, [$orderSn]);
                            $list = $details['response']['order_list'] ?? [];
                            if (count($list) > 0) {
                                $remoteRaw = $list[0];
                                if (!empty($remoteRaw['package_list'][0]['tracking_number'])) {
                                    $awb = $remoteRaw['package_list'][0]['tracking_number'];
                                }
                            }
                        }
                        if ($awb && str_starts_with(strtoupper($awb), 'OFG')) {
                            $awb = null;
                        }
                        if ($awb) {
                            $payload['tracking_number'] = $awb;
                            $order->update(['shipping_awb_no' => $awb]);
                        }
                    } catch (\Throwable $e) {}
                }
            }
            
            // Parameter untuk greeting card
            $cardParam = request()->query('card');
            $cacheSuffix = $cardParam === '0' ? '_nocard' : '';
            
            // Cek Cache Lokal
            $disk = \Illuminate\Support\Facades\Storage::disk('local');
            $cachePath = "shipping_labels/{$store->id}/{$orderSn}{$cacheSuffix}.pdf.gz";
            
            if ($disk->exists($cachePath)) {
                if ($order) {
                    $order->increment('print_count');
                    if (!$order->printed_at) $order->update(['printed_at' => now()]);
                }
                $content = gzdecode($disk->get($cachePath));
                return response($content, 200, [
                    'Content-Type' => 'application/pdf',
                    'Content-Disposition' => 'inline; filename="Resi_' . $orderSn . '.pdf"'
                ]);
            }

            // Jika belum ada di cache, dan statusnya sudah SHIPPED, berikan keterangan langsung
            if ($order && $order->order_status === 'SHIPPED') {
                return response()->view('marketplace.documents.fallback_awb', [
                    'order' => $order,
                    'store' => $store,
                    'awb'   => $payload['tracking_number'] ?? $order->shipping_awb_no ?? 'N/A',
                ], 200);
            }
            
            // Step 1: Create Document
            $createRes = $driver->createShippingDocument($store, [$payload]);
            
            if (isset($createRes['error']) && $createRes['error']) {
                $errorMsg = $createRes['message'] ?? 'Gagal membuat dokumen resi di Marketplace.';
                if (isset($createRes['response']['result_list']) && is_array($createRes['response']['result_list'])) {
                    foreach ($createRes['response']['result_list'] as $resItem) {
                        if (isset($resItem['fail_message'])) {
                            $errorMsg = $resItem['fail_message'];
                            
                            // Translate common shopee error
                            if (str_contains(strtolower($errorMsg), 'tracking number is invalid')) {
                                try {
                                    $awb = null;
                                    $pkgNo = null;
                                    
                                    // Panggil getShippingParameter sesuai request user
                                    if (method_exists($driver, 'getShippingParameter')) {
                                        $driver->getShippingParameter($store, $orderSn);
                                    }
                                    
                                    if (method_exists($driver, 'getTrackingNumber')) {
                                        $trackResp = $driver->getTrackingNumber($store, $orderSn);
                                        $awb = $trackResp['response']['tracking_number'] ?? null;
                                    }
                                    
                                    if (!$awb && method_exists($driver, 'getOrderDetail')) {
                                        $details = $driver->getOrderDetail($store, [$orderSn]);
                                        $list = $details['response']['order_list'] ?? [];
                                        if (count($list) > 0 && !empty($list[0]['package_list'][0])) {
                                            $pkg = $list[0]['package_list'][0];
                                            $awb = $pkg['tracking_number'] ?? $awb;
                                            $pkgNo = $pkg['package_number'] ?? null;
                                        }
                                    }
                                    
                                    if ($awb || $pkgNo) {
                                        if ($awb && str_starts_with(strtoupper($awb), 'OFG')) {
                                            $awb = null;
                                        }
                                        if ($awb) {
                                            $payload['tracking_number'] = $awb;
                                            if ($order) {
                                                $order->update(['shipping_awb_no' => $awb]);
                                            }
                                        }
                                        if ($pkgNo) {
                                            $payload['package_number'] = $pkgNo;
                                        }
                                        
                                        // Retry create document with updated payload
                                        $createRes = $driver->createShippingDocument($store, [$payload]);
                                        if (empty($createRes['error']) && (!isset($createRes['response']['result_list'][0]['fail_message']))) {
                                            $errorMsg = null;
                                            break;
                                        }
                                    }
                                } catch (\Throwable $e) {}
                            }

                            if (str_contains($errorMsg, 'parcel has been shipped')) {
                                return response()->view('marketplace.documents.fallback_awb', [
                                    'order' => $order,
                                    'store' => $store,
                                    'awb'   => $payload['tracking_number'] ?? $order->shipping_awb_no ?? 'N/A',
                                ], 200);
                            }
                            break;
                        }
                    }
                }
                
                if ($errorMsg !== null) {
                    return response(
                        "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                            <h2 style='color:#e11d48;'>Gagal Mencetak Resi</h2>
                            <p>{$errorMsg}</p>
                            <button onclick='window.close()' style='padding:10px 20px; background:#e2e8f0; border:none; border-radius:5px; cursor:pointer;'>Tutup Halaman</button>
                        </div>", 400
                    );
                }
            }
            
            // Step 2: Download Document (PDF Stream from Shopee API)
            // Because Shopee returns PDF stream, getShippingDocument might return invalid_response with raw body
            $downloadRes = $driver->getShippingDocument($store, [$payload]);
            
            if (isset($downloadRes['error']) && $downloadRes['error'] === 'invalid_response') {
                $content = $downloadRes['message']; // Raw body
                if (str_starts_with($content, '%PDF')) {
                    $config = [];
                    if ($cardParam !== null) {
                        $config['marketplace_print_greeting_card'] = $cardParam === '1' ? '1' : '0';
                    }
                    $overlayService = new \App\Services\ShippingLabelOverlayService();
                    $content = $overlayService->overlayPdfContent($content, $config);

                    // Simpan ke Cache Lokal dengan kompresi GZIP Level 9
                    $disk->put($cachePath, gzencode($content, 9));

                    if ($order) {
                        $order->increment('print_count');
                        if (!$order->printed_at) $order->update(['printed_at' => now()]);
                    }

                    return response($content, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="Resi_' . $orderSn . '.pdf"'
                    ]);
                }
            }
            if (isset($downloadRes['error'])) {
                $errorMsg = $downloadRes['message'] ?? 'Gagal mengunduh dokumen resi.';
                return response(
                    "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                        <h2 style='color:#f59e0b;'>Resi Belum Siap</h2>
                        <p>{$errorMsg}</p>
                        <button onclick='window.close()' style='padding:10px 20px; background:#e2e8f0; border:none; border-radius:5px; cursor:pointer;'>Tutup Halaman</button>
                    </div>", 400
                );
            }
            
            return response()->json([
                'create_result' => $createRes,
                'download_result' => $downloadRes
            ]);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    public function createBulkPrintJob(Request $request)
    {
        $payloadOrders = $request->input('orders', []);
        $mode = $request->input('mode', 'unprinted_only');
        $withGreeting = $request->input('with_greeting', 0);
        
        if (empty($payloadOrders)) {
            return response()->json(['error' => 'No orders provided'], 400);
        }

        // Batas waktu dinamis: unduhan per-item + retry butuh waktu; default 30-60
        // detik PHP bisa memutus batch besar di tengah jalan.
        set_time_limit(min(600, 60 + 15 * count($payloadOrders)));

        $results = [];
        $pdfContents = [];
        $successfulOrderSns = [];
        $failedOrders = [];
        $successCount = 0;
        
        // Group payload by store_id
        $groupedByStore = [];
        foreach ($payloadOrders as $reqOrder) {
            $storeId = $reqOrder['store_id'] ?? null;
            $orderSn = $reqOrder['channel_order_id'] ?? null;
            $pos = $reqOrder['position'] ?? 0;
            
            if (!$storeId || !$orderSn) continue;
            
            $groupedByStore[$storeId][] = [
                'order_sn' => $orderSn,
                'booking_sn' => $reqOrder['booking_sn'] ?? null,
                'position' => $pos
            ];
        }

        foreach ($groupedByStore as $storeId => $items) {
            $store = Store::find($storeId);
            if (!$store) {
                foreach ($items as $item) {
                    $failedOrders[] = [
                        'store_id' => $storeId,
                        'store_name' => 'Unknown Store',
                        'channel_order_id' => $item['order_sn'],
                        'reason' => 'Store not found'
                    ];
                }
                continue;
            }

            $orderSns = array_column($items, 'order_sn');
            $orders = MarketplaceOrder::where('store_id', $storeId)
                ->whereIn('channel_order_id', $orderSns)
                ->get()
                ->keyBy('channel_order_id');
                
            $bookingSns = array_filter(array_column($items, 'booking_sn'));
            // Tanpa filter store_id: record booking bisa tersimpan di store lokal
            // berbeda dari order-nya (booking_sn unik global) — dengan filter,
            // item kilat bisa salah dinyatakan "Order not found in database".
            $bookings = empty($bookingSns)
                ? collect()
                : \App\Models\MarketplaceBooking::whereIn('booking_sn', $bookingSns)
                    ->get()
                    ->keyBy('booking_sn');
                
            try {
                $driver = app(\App\Services\Channels\ChannelManager::class)->driver($store);
            } catch (\Exception $e) {
                foreach ($items as $item) {
                    $failedOrders[] = [
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'channel_order_id' => $item['order_sn'],
                        'reason' => 'Failed to initialize marketplace driver'
                    ];
                }
                continue;
            }

            // Create shipping document in bulk first to trigger generation on Shopee side
            $payloadListAll = [];
            foreach ($items as $item) {
                $orderSn = $item['order_sn'];
                $bookingSn = $item['booking_sn'] ?? null;
                $order = $orders->get($orderSn);
                $booking = $bookingSn ? $bookings->get($bookingSn) : null;
                
                if (!$order && !$booking) {
                    $failedOrders[] = [
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'channel_order_id' => $orderSn,
                        'reason' => 'Order not found in database'
                    ];
                    continue;
                }
                
                $payload = ['order_sn' => $orderSn];
                if (!empty($bookingSn)) {
                    $payload['is_booking'] = true;
                    $payload['booking_sn'] = $bookingSn;
                }
                
                if ($booking) {
                    // Resi booking sering menyusul asinkron — bila belum ada, coba tarik
                    // dulu supaya create/download dokumen tidak gagal karena resi kosong.
                    if (! $booking->tracking_number && method_exists($driver, 'getBookingTrackingNumber')) {
                        try {
                            $trk = $driver->getBookingTrackingNumber($store, $bookingSn);
                            $trkNum = $trk['response']['tracking_number'] ?? null;
                            if ($trkNum) {
                                $booking->update(['tracking_number' => $trkNum]);
                            }
                        } catch (\Throwable $e) { /* lanjut tanpa resi */ }
                    }
                    if ($booking->tracking_number) {
                        $payload['tracking_number'] = $booking->tracking_number;
                    }
                } elseif ($order) {
                    if ($order->shipping_awb_no) {
                        $payload['tracking_number'] = $order->shipping_awb_no;
                    }
                    $rawJson = $order->raw_json ?? [];
                    if (isset($rawJson['package_list']) && is_array($rawJson['package_list'])) {
                        $packageList = $rawJson['package_list'];
                        if (count($packageList) > 0 && isset($packageList[0]['package_number'])) {
                            $payload['package_number'] = $packageList[0]['package_number'];
                        }
                    }
                }
                $payloadListAll[$orderSn] = $payload;
            }
            
            // Chunk by 50 for createShippingDocument
            $regularChunks = array_chunk(array_values(array_filter($payloadListAll, fn($p) => !isset($p['is_booking']))), 50);
            foreach ($regularChunks as $chunk) {
                try {
                    $driver->createShippingDocument($store, $chunk);
                } catch (\Exception $e) { \Illuminate\Support\Facades\Log::error("FPDI Error: " . $e->getMessage()); }
            }
            
            // Create booking shipping document in bulk
            $bookingChunks = array_chunk(array_values(array_filter($payloadListAll, fn($p) => isset($p['is_booking']))), 50);
            foreach ($bookingChunks as $chunk) {
                try {
                    if (method_exists($driver, 'createBookingShippingDocument')) {
                        // For booking payload, Shopee expects only booking_sn and tracking_number
                        $cleanedChunk = array_map(fn($c) => ['booking_sn' => $c['booking_sn'] ?? '', 'tracking_number' => $c['tracking_number'] ?? ''], $chunk);
                        $driver->createBookingShippingDocument($store, $cleanedChunk);
                    }
                } catch (\Exception $e) { \Illuminate\Support\Facades\Log::error("FPDI Error: " . $e->getMessage()); }
            }

            // Beri waktu Shopee membuat dokumen (asinkron) sebelum unduhan pertama.
            sleep(1);

            // Download INDIVIDUALLY to guarantee order sorting
            foreach ($items as $item) {
                $orderSn = $item['order_sn'];
                $pos = $item['position'];

                if (!isset($payloadListAll[$orderSn])) continue;
                $payload = $payloadListAll[$orderSn];

                try {
                    // Dokumen Shopee dibuat ASINKRON — sekali coba sering kena
                    // "document not ready". Retry ringan per item (maks 3x, jeda naik);
                    // error permanen ("not exist" dsb.) tidak diulang.
                    $content = null;
                    $lastReason = 'Document not ready';

                    for ($attempt = 1; $attempt <= 3; $attempt++) {
                        $downloadRes = [];
                        if (isset($payload['is_booking']) && method_exists($driver, 'downloadBookingShippingDocument')) {
                            $cleanedPayload = ['booking_sn' => $payload['booking_sn'] ?? '', 'tracking_number' => $payload['tracking_number'] ?? ''];
                            $bookingRes = $driver->downloadBookingShippingDocument($store, [$cleanedPayload]);
                            if (is_array($bookingRes)) {
                                $downloadRes = $bookingRes; // Contains error/message
                            } elseif (is_string($bookingRes) && str_starts_with($bookingRes, '%PDF')) {
                                $downloadRes = ['error' => 'invalid_response', 'message' => $bookingRes];
                            }
                        } else {
                            $downloadRes = $driver->getShippingDocument($store, [$payload]);
                        }

                        if (($downloadRes['error'] ?? null) === 'invalid_response'
                            && str_starts_with((string) ($downloadRes['message'] ?? ''), '%PDF')) {
                            $content = $downloadRes['message'];
                            break;
                        }

                        $lastReason = (string) ($downloadRes['message'] ?? ($downloadRes['error'] ?? 'Document not ready'));
                        if (str_contains(strtolower($lastReason), 'not exist')) {
                            break; // permanen — retry hanya buang waktu
                        }
                        if ($attempt < 3) {
                            sleep($attempt === 1 ? 2 : 4);
                        }
                    }

                    if ($content !== null) {
                        $pdfContents[] = [
                            'position' => $pos,
                            'content' => $content,
                            'order_sn' => $orderSn,
                            'store_id' => $store->id,
                            'booking_sn' => $payload['booking_sn'] ?? null,
                        ];
                        $successCount++;
                    } else {
                        $failedOrders[] = [
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'channel_order_id' => $orderSn,
                            'reason' => $lastReason,
                        ];
                    }
                } catch (\Exception $e) {
                    $failedOrders[] = [
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'channel_order_id' => $orderSn,
                        'reason' => 'API Error: ' . $e->getMessage()
                    ];
                }
            }
        }

        if ($successCount === 0) {
            return response()->json([
                'error' => 'Gagal mendapatkan semua dokumen PDF.',
                'failed_orders' => $failedOrders,
                'success_count' => 0,
                'failed_count' => count($failedOrders)
            ], 400);
        }

        // Sort PDFs by store_id first, then by position to group by store
        usort($pdfContents, function($a, $b) {
            if ($a['store_id'] === $b['store_id']) {
                return $a['position'] <=> $b['position'];
            }
            return $a['store_id'] <=> $b['store_id'];
        });

        $config = [];
        $config['marketplace_print_greeting_card'] = $withGreeting == 1 ? '1' : '0';
        $overlayService = new \App\Services\ShippingLabelOverlayService();

        // ── Merge PDFs ────────────────────────────────────────────────────────
        // Prinsip tahan banting: (1) overlay kartu ucapan gagal → pakai PDF asli
        // (lebih baik tercetak tanpa kartu daripada gagal); (2) satu PDF korup
        // gagal digabung → order itu MASUK daftar gagal (dulu: dilewati diam-diam
        // tapi tetap dihitung sukses, resinya hilang dari hasil cetak tanpa jejak).
        $mergedOk = []; // entri pdfContents yang benar-benar masuk hasil akhir
        $finalPdfContent = null;

        $overlaySafe = function (string $content) use ($overlayService, $config): string {
            try {
                return $overlayService->overlayPdfContent($content, $config);
            } catch (\Throwable $e) {
                \Illuminate\Support\Facades\Log::warning('Bulk print: overlay gagal, pakai PDF asli — ' . $e->getMessage());
                return $content;
            }
        };

        if (count($pdfContents) === 1) {
            $finalPdfContent = $overlaySafe($pdfContents[0]['content']);
            $mergedOk[] = $pdfContents[0];
        } else {
            $pdf = new \setasign\Fpdi\Fpdi();
            $tempFiles = [];

            foreach ($pdfContents as $item) {
                $tmpPath = storage_path('app/temp_pdf_' . uniqid() . '.pdf');
                file_put_contents($tmpPath, $overlaySafe($item['content']));
                $tempFiles[] = $tmpPath;

                try {
                    $pageCount = $pdf->setSourceFile($tmpPath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                    $mergedOk[] = $item;
                } catch (\Exception $e) {
                    \Illuminate\Support\Facades\Log::error("FPDI Error ({$item['order_sn']}): " . $e->getMessage());
                    $failedOrders[] = [
                        'store_id' => $item['store_id'],
                        'store_name' => optional(Store::find($item['store_id']))->name ?? '-',
                        'channel_order_id' => $item['order_sn'],
                        'reason' => 'PDF tidak bisa digabung: ' . $e->getMessage(),
                    ];
                    $successCount--;
                }
            }

            foreach ($tempFiles as $f) {
                if (file_exists($f)) unlink($f);
            }

            if (empty($mergedOk)) {
                return response()->json([
                    'error' => 'Semua dokumen PDF gagal digabung.',
                    'failed_orders' => $failedOrders,
                    'success_count' => 0,
                    'failed_count' => count($failedOrders)
                ], 400);
            }

            $finalPdfContent = $pdf->Output('S');
        }

        // ── Statistik cetak: hanya untuk yang BENAR-BENAR masuk PDF akhir ─────
        // Dibungkus try/catch: statistik tidak boleh menggagalkan batch yang
        // PDF-nya sudah di tangan. Booking dicocokkan via booking_sn tanpa filter
        // store_id (record bisa tersimpan di store lokal berbeda).
        try {
            $orderSnsByStore = [];
            $bookingSns = [];
            foreach ($mergedOk as $item) {
                $orderSnsByStore[$item['store_id']][] = $item['order_sn'];
                if (! empty($item['booking_sn'])) {
                    $bookingSns[] = $item['booking_sn'];
                }
            }

            foreach ($orderSnsByStore as $sid => $sns) {
                MarketplaceOrder::where('store_id', $sid)
                    ->whereIn('channel_order_id', array_unique($sns))
                    ->get()
                    ->each(function ($order) {
                        $order->increment('print_count');
                        if (! $order->printed_at) $order->update(['printed_at' => now()]);
                    });
            }

            if (! empty($bookingSns)) {
                \App\Models\MarketplaceBooking::whereIn('booking_sn', array_unique($bookingSns))
                    ->get()
                    ->each(function ($booking) {
                        $booking->increment('print_count');
                        if (! $booking->printed_at) $booking->update(['printed_at' => now()]);
                    });
            }
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::warning('Bulk print: gagal mencatat statistik cetak — ' . $e->getMessage());
        }

        $uuid = (string) Str::uuid();
        $tmpPdfPath = 'public/tmp/bulk_print_' . $uuid . '.pdf';
        \Illuminate\Support\Facades\Storage::put($tmpPdfPath, $finalPdfContent);

        return response()->json([
            'uuid' => $uuid,
            'success_count' => $successCount,
            'failed_count' => count($failedOrders),
            'failed_orders' => $failedOrders,
            'download_url' => url('/api/marketplace/documents/bulk-print/' . $uuid)
        ]);
    }

    public function downloadBulkPrintJob(string $uuid)
    {
        $path = 'public/tmp/bulk_print_' . $uuid . '.pdf';
        if (!\Illuminate\Support\Facades\Storage::exists($path)) {
            abort(404, 'Dokumen PDF tidak ditemukan atau sudah kedaluwarsa.');
        }

        return response()->make(\Illuminate\Support\Facades\Storage::get($path), 200, [
            'Content-Type' => 'application/pdf',
            'Content-Disposition' => 'inline; filename="bulk_print_' . $uuid . '.pdf"'
        ]);
    }

    public function printBulkGreetings(Request $request, Store $store): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $orderSns = $request->query('orders');
            if (empty($orderSns)) {
                return response()->json(['error' => 'No orders provided'], 400);
            }
            
            $orderSnList = explode(',', $orderSns);
            $orderSnList = array_slice($orderSnList, 0, 50); // Maksimal 50
            
            // Buat blank PDF
            $pdf = new \setasign\Fpdi\Fpdi();
            
            $width = 100;
            $height = 150;
            
            $greetingImageFull = null;
            $customGreetingImg = \App\Models\SystemSetting::get('marketplace_greeting_card_image', '');
            
            if (!empty($customGreetingImg) && file_exists(storage_path('app/public/' . $customGreetingImg))) {
                $greetingImageFull = storage_path('app/public/' . $customGreetingImg);
            } else {
                $gTpl = \App\Models\SystemSetting::get('marketplace_greeting_card_template', 'template_1.png');
                if ($gTpl !== 'none') {
                    if (in_array($gTpl, ['1', '2', '3'])) {
                        $gTpl = 'template_' . $gTpl . '.png';
                    }
                    $tplPath = storage_path('app/public/templates/greetings/' . $gTpl);
                    if (file_exists($tplPath)) {
                        $greetingImageFull = $tplPath;
                    }
                }
            }

            // Cetak HANYA 1 Halaman sesuai request user
            $pdf->AddPage('P', [$width, $height]);
            
            if ($greetingImageFull) {
                $ext = strtolower(pathinfo($greetingImageFull, PATHINFO_EXTENSION));
                if ($ext === 'pdf') {
                    try {
                        $pdf->setSourceFile($greetingImageFull);
                        $gTplId = $pdf->importPage(1);
                        $pdf->useTemplate($gTplId, 0, 0, $width, $height);
                    } catch (\Exception $e) { \Illuminate\Support\Facades\Log::error("FPDI Error: " . $e->getMessage()); }
                } else {
                    // Add 4mm safe margin
                    $m = 4;
                    $pdf->Image($greetingImageFull, $m, $m, $width - ($m * 2), $height - ($m * 2));
                }
            } else {
                $pdf->SetFont('Helvetica', 'B', 14);
                $pdf->SetXY(0, ($height / 2) - 5);
                $pdf->Cell($width, 10, 'Thank you for your order!', 0, 1, 'C');
            }
            
            $content = $pdf->Output('S');

            return response($content, 200, [
                'Content-Type' => 'application/pdf',
                'Content-Disposition' => 'inline; filename="Kartu_Ucapan_Bulk.pdf"'
            ]);
            
        } catch (\Throwable $e) {
            \Illuminate\Support\Facades\Log::error("Failed bulk greetings: " . $e->getMessage());
            return response()->json(['error' => $e->getMessage()], 500);
        }
    }

    public function getTrackingInfo(Store $store, $orderSn, Request $request)
    {
        try {
            $driver = $this->manager->driver($store);
            if (!method_exists($driver, 'getTrackingInfo')) {
                return response()->json(['error' => true, 'message' => 'Driver tidak mendukung pelacakan'], 400);
            }

            $result = $driver->getTrackingInfo($store, $orderSn);
            
            if (isset($result['error']) && !empty($result['error'])) {
                return response()->json([
                    'tracking_info' => [],
                    'message' => 'Belum ada data pelacakan atau pesanan tidak menggunakan resi otomatis.',
                    'raw_error' => $result['message'] ?? $result['error']
                ]);
            }

            return response()->json($result['response'] ?? $result);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
    }

    protected function ensureSuccess(array $result): array
    {
        if (!empty($result['error']) || (isset($result['code']) && $result['code'] !== 0 && $result['code'] !== '')) {
            throw new \RuntimeException($result['message'] ?? $result['error'] ?? 'Terjadi kesalahan pada API Marketplace.');
        }
        return $result;
    }

    protected function errorResponse(\Exception $e): JsonResponse
    {
        Log::error('Marketplace Logistics Error: ' . $e->getMessage(), ['trace' => $e->getTraceAsString()]);
        return response()->json([
            'error' => true,
            'message' => $e->getMessage(),
        ], 422);
    }
}
