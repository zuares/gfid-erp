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
     * Arrange shipment (Atur Pengiriman)
     */
    public function arrangeShipment(Request $request, Store $store, string $orderSn): JsonResponse
    {
        try {
            // $request->all() typically contains pickup/dropoff details
            // e.g. ['pickup' => ['address_id' => 123, 'pickup_time_id' => 'abc']]
            $params = $request->except(['_token', 'store', 'orderSn']);
            
            // Fix for PHP json_encode converting empty associative arrays to JSON arrays []
            // Shopee expects dropoff/pickup to be JSON objects {}
            if (isset($params['dropoff']) && is_array($params['dropoff']) && empty($params['dropoff'])) {
                $params['dropoff'] = new \stdClass();
            }
            if (isset($params['pickup']) && is_array($params['pickup']) && empty($params['pickup'])) {
                $params['pickup'] = new \stdClass();
            }
            
            $driver = $this->manager->driver($store);
            $rawResult = $driver->shipOrder($store, $orderSn, $params);
            
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
                return response(
                    "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                        <h2 style='color:#e11d48;'>Dokumen Resi Belum Tersimpan</h2>
                        <p style='color:#475569;'>Resi untuk pesanan ini <strong>belum pernah dicetak dan disimpan (ter-cache)</strong> ke dalam sistem lokal.</p>
                        <p style='color:#475569;'>Karena pesanan ini sudah berstatus <strong>Sedang Dikirim (Shipped)</strong>, Marketplace sudah tidak mengizinkan sistem untuk menarik ulang dokumen resinya.</p>
                        <button onclick='window.close()' style='margin-top:20px; padding:10px 25px; background:#f1f5f9; color:#0f172a; border:1px solid #cbd5e1; border-radius:6px; cursor:pointer; font-weight:700;'>Tutup Halaman</button>
                    </div>", 400
                );
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
                            if (str_contains($errorMsg, 'parcel has been shipped')) {
                                $errorMsg = "Gagal mencetak resi: Pesanan sudah diserahkan ke kurir (Shipped). Shopee tidak mengizinkan cetak ulang resi untuk pesanan yang sudah dalam pengiriman.";
                            }
                            break;
                        }
                    }
                }
                
                return response(
                    "<div style='font-family:sans-serif; text-align:center; padding:50px;'>
                        <h2 style='color:#e11d48;'>Gagal Mencetak Resi</h2>
                        <p>{$errorMsg}</p>
                        <button onclick='window.close()' style='padding:10px 20px; background:#e2e8f0; border:none; border-radius:5px; cursor:pointer;'>Tutup Halaman</button>
                    </div>", 400
                );
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
                $order = $orders->get($orderSn);
                if (!$order) {
                    $failedOrders[] = [
                        'store_id' => $store->id,
                        'store_name' => $store->name,
                        'channel_order_id' => $orderSn,
                        'reason' => 'Order not found in database'
                    ];
                    continue;
                }
                
                $payload = ['order_sn' => $orderSn];
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
                $payloadListAll[$orderSn] = $payload;
            }
            
            // Chunk by 50 for createShippingDocument
            $chunks = array_chunk(array_values($payloadListAll), 50);
            foreach ($chunks as $chunk) {
                try {
                    $driver->createShippingDocument($store, $chunk);
                } catch (\Exception $e) {
                    // Ignore, we will try to download anyway
                }
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
                    $downloadRes = $driver->getShippingDocument($store, [$payload]);
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
        }

        // Sort PDFs by position
        usort($pdfContents, function($a, $b) {
            return $a['position'] <=> $b['position'];
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
            
            foreach ($pdfContents as $item) {
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
