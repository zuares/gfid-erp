<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\Channels\ChannelManager;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class MarketplaceLogisticsController extends Controller
{
    public function __construct(
        protected ChannelManager $manager
    ) {}

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
            $result = $this->ensureSuccess($driver->shipOrder($store, $orderSn, $params));
            
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
                                'raw_json' => $rawJson
                            ]);
                        return response()->json($result);
                    }
                } elseif ($awb) {
                    MarketplaceOrder::where('store_id', $store->id)
                        ->where('channel_order_id', $orderSn)
                        ->update([
                            'order_status' => 'PROCESSED',
                            'shipping_awb_no' => $awb
                        ]);
                    return response()->json($result);
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::warning('Gagal fetch order detail setelah arrange shipment: ' . $e->getMessage());
            }
            
            // Fallback: Mark as PROCESSED locally
            MarketplaceOrder::where('store_id', $store->id)
                ->where('channel_order_id', $orderSn)
                ->update(['order_status' => 'PROCESSED']);

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
                    $overlayService = new \App\Services\ShippingLabelOverlayService();
                    $content = $overlayService->overlayPdfContent($content);

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

    public function printDocumentBulk(Request $request, Store $store): \Symfony\Component\HttpFoundation\Response
    {
        try {
            $orderSns = $request->query('orders');
            if (empty($orderSns)) {
                return response()->json(['error' => 'No orders provided'], 400);
            }
            
            $orderSnList = explode(',', $orderSns);
            $orderSnList = array_slice($orderSnList, 0, 50); // Maksimal 50
            
            $orders = MarketplaceOrder::where('store_id', $store->id)
                ->whereIn('channel_order_id', $orderSnList)
                ->get()
                ->keyBy('channel_order_id');
                
            $driver = $this->manager->driver($store);
            $payloadList = [];
            
            foreach ($orderSnList as $orderSn) {
                $order = $orders->get($orderSn);
                $payload = ['order_sn' => $orderSn];
                if ($order) {
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
                $payloadList[] = $payload;
            }
            
            $createRes = $driver->createShippingDocument($store, $payloadList);
            $downloadRes = $driver->getShippingDocument($store, $payloadList);
            
            if (isset($downloadRes['error']) && $downloadRes['error'] === 'invalid_response') {
                $content = $downloadRes['message']; 
                if (str_starts_with($content, '%PDF')) {
                    $overlayService = new \App\Services\ShippingLabelOverlayService();
                    $content = $overlayService->overlayPdfContent($content);

                    return response($content, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="Resi_Bulk.pdf"'
                    ]);
                }
            }
            
            return response()->json([
                'create_result' => $createRes,
                'download_result' => $downloadRes
            ]);
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
