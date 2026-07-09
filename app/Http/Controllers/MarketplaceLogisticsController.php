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
                if (method_exists($driver, 'getOrderDetail')) {
                    $details = $driver->getOrderDetail($store, [$orderSn]);
                    $list = $details['response']['order_list'] ?? [];
                    if (count($list) > 0) {
                        $rawJson = $list[0];
                        $awb = null;
                        if (!empty($rawJson['package_list'][0]['tracking_number'])) {
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
            
            // Step 2: Download Document (PDF Stream from Shopee API)
            // Because Shopee returns PDF stream, getShippingDocument might return invalid_response with raw body
            $downloadRes = $driver->getShippingDocument($store, [$payload]);
            
            if (isset($downloadRes['error']) && $downloadRes['error'] === 'invalid_response') {
                $content = $downloadRes['message']; // Raw body
                if (str_starts_with($content, '%PDF')) {
                    return response($content, 200, [
                        'Content-Type' => 'application/pdf',
                        'Content-Disposition' => 'inline; filename="Resi_' . $orderSn . '.pdf"'
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
