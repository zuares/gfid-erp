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
                ->where('channel_order_id', $orderSn)
                ->first();

            if (!$order) {
                return response()->json(['error' => 'Order not found'], 404);
            }

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

            if ($awb && $order->shipping_awb_no !== $awb) {
                $order->update(['shipping_awb_no' => $awb]);
                return response()->json(['success' => true, 'awb' => $awb]);
            }

            return response()->json(['success' => false, 'message' => 'AWB not found or unchanged', 'awb' => $order->shipping_awb_no]);

        } catch (\Throwable $e) {
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
            $result = $this->ensureSuccess($driver->getShippingParameter($store, $orderSn));
            
            return response()->json($result);
        } catch (\Exception $e) {
            return $this->errorResponse($e);
        }
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
            $bookings = \App\Models\MarketplaceBooking::where('store_id', $storeId)
                ->whereIn('booking_sn', $bookingSns)
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
                } catch (\Exception $e) {}
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
                } catch (\Exception $e) {}
            }

            // Sleep a bit to allow Shopee to generate documents
            usleep(500000); // 0.5 sec

            // Download INDIVIDUALLY to guarantee order sorting
            foreach ($items as $item) {
                $orderSn = $item['order_sn'];
                $pos = $item['position'];
                
                if (!isset($payloadListAll[$orderSn])) continue;
                $payload = $payloadListAll[$orderSn];
                
                try {
                    $downloadRes = [];
                    if (isset($payload['is_booking']) && method_exists($driver, 'downloadBookingShippingDocument')) {
                        $cleanedPayload = ['booking_sn' => $payload['booking_sn'] ?? '', 'tracking_number' => $payload['tracking_number'] ?? ''];
                        $bookingRes = $driver->downloadBookingShippingDocument($store, [$cleanedPayload]);
                        if (is_array($bookingRes)) {
                            $downloadRes = $bookingRes; // Contains error/message
                        } elseif (str_starts_with($bookingRes, '%PDF')) {
                            $downloadRes = ['error' => 'invalid_response', 'message' => $bookingRes];
                        }
                    } else {
                        $downloadRes = $driver->getShippingDocument($store, [$payload]);
                    }
                    
                    if (isset($downloadRes['error']) && $downloadRes['error'] === 'invalid_response') {
                        $content = $downloadRes['message']; 
                        if (str_starts_with($content, '%PDF')) {
                            $pdfContents[] = [
                                'position' => $pos,
                                'content' => $content,
                                'order_sn' => $orderSn,
                                'store_id' => $store->id,
                            ];
                            $successCount++;
                            $successfulOrderSns[$store->id][] = $orderSn;
                        } else {
                            $failedOrders[] = [
                                'store_id' => $store->id,
                                'store_name' => $store->name,
                                'channel_order_id' => $orderSn,
                                'reason' => 'Document not ready or not a PDF'
                            ];
                        }
                    } else {
                        $failedOrders[] = [
                            'store_id' => $store->id,
                            'store_name' => $store->name,
                            'channel_order_id' => $orderSn,
                            'reason' => $downloadRes['message'] ?? 'Document not ready'
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

        // Increment print_count
        foreach ($successfulOrderSns as $storeId => $sns) {
            MarketplaceOrder::where('store_id', $storeId)
                ->whereIn('channel_order_id', $sns)
                ->get()
                ->each(function($order) use ($mode) {
                    if ($mode === 'reprint') {
                        $order->increment('print_count');
                    } else {
                        $order->increment('print_count');
                        if (!$order->printed_at) $order->update(['printed_at' => now()]);
                    }
                });
                
            \App\Models\MarketplaceBooking::where('store_id', $storeId)
                ->whereIn('booking_sn', $sns)
                ->get()
                ->each(function($booking) use ($mode) {
                    if ($mode === 'reprint') {
                        $booking->increment('print_count');
                    } else {
                        $booking->increment('print_count');
                        if (!$booking->printed_at) $booking->update(['printed_at' => now()]);
                    }
                });
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

        // Merge PDFs
        $finalPdfContent = null;
        if (count($pdfContents) === 1) {
            $finalPdfContent = $overlayService->overlayPdfContent($pdfContents[0]['content'], $config);
        } else {
            $pdf = new \setasign\Fpdi\Fpdi();
            $tempFiles = [];
            $currentStoreId = null;
            
            foreach ($pdfContents as $item) {
                // Jika berpindah toko, sisipkan halaman pemisah (Kertas Thermal A6)
                if ($currentStoreId !== null && $currentStoreId !== $item['store_id']) {
                    $pdf->AddPage('P', [100, 150]);
                    $pdf->SetFont('Arial', 'B', 16);
                    $storeName = \App\Models\Store::find($item['store_id'])->name ?? 'Toko Lainnya';
                    $pdf->Cell(0, 40, '', 0, 1); // Spacer atas
                    $pdf->Cell(0, 10, '--- BATAS TOKO ---', 0, 1, 'C');
                    $pdf->SetFont('Arial', 'B', 14);
                    $pdf->Cell(0, 10, strtoupper($storeName), 0, 1, 'C');
                    $pdf->SetFont('Arial', '', 10);
                    $pdf->Cell(0, 15, 'Pisahkan tumpukan paket setelah kertas ini', 0, 1, 'C');
                }
                $currentStoreId = $item['store_id'];

                $tmpPath = storage_path('app/temp_pdf_' . uniqid() . '.pdf');
                $uncompressedContent = $overlayService->overlayPdfContent($item['content'], $config);
                file_put_contents($tmpPath, $uncompressedContent);
                $tempFiles[] = $tmpPath;
                
                try {
                    $pageCount = $pdf->setSourceFile($tmpPath);
                    for ($pageNo = 1; $pageNo <= $pageCount; $pageNo++) {
                        $templateId = $pdf->importPage($pageNo);
                        $size = $pdf->getTemplateSize($templateId);
                        $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                        $pdf->useTemplate($templateId);
                    }
                } catch (\Exception $e) {}
            }
            
            foreach ($tempFiles as $f) {
                if (file_exists($f)) unlink($f);
            }
            
            $finalPdfContent = $pdf->Output('S');
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
                    } catch (\Exception $e) {}
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
