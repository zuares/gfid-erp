<?php

namespace App\Http\Controllers;

use App\Models\WebhookLog;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;

class WebhookController extends Controller
{
    /**
     * Handle Shopee Push Mechanism (Webhooks)
     */
    public function shopee(Request $request)
    {
        // 1. Immediately return 200 OK so Shopee doesn't retry (and to pass the Verify button)
        $response = response('OK', 200);

        // Terminate the request gracefully so we can do heavy work in the background 
        // if needed. But for simple logging, we just log it.
        $payload = $request->all();
        $rawBody = $request->getContent();
        
        // Shopee sends signature in Authorization header
        $signature = $request->header('Authorization');
        
        $verified = false;
        $partnerKey = env('SHOPEE_PUSH_KEY', ''); // User should define this in .env
        
        if (!empty($partnerKey)) {
            // Shopee uses HMAC-SHA256 of the URL + Body
            $url = $request->fullUrl(); 
            
            // Because of ngrok, fullUrl() might return http:// instead of https://
            // Shopee signs using the exact URL configured in the console (https://...)
            $urlHttps = str_replace('http://', 'https://', $url);
            
            // Shopee V2 Push Mechanism requires a pipe separator!
            $expectedSignature = hash_hmac('sha256', $url . '|' . $rawBody, $partnerKey);
            $expectedSignatureHttps = hash_hmac('sha256', $urlHttps . '|' . $rawBody, $partnerKey);
            
            if ($signature === $expectedSignature || $signature === $expectedSignatureHttps) {
                $verified = true;
            }

            // Inject debug info so we can see why it mismatches
            $payload['_debug'] = [
                'received_signature' => $signature,
                'calculated_http' => $expectedSignature,
                'calculated_https' => $expectedSignatureHttps,
                'url_used' => $urlHttps,
                'partner_key_length' => strlen($partnerKey),
                'partner_key_starts_with' => substr($partnerKey, 0, 4)
            ];
        }

        try {
            $eventType = 'unknown';
            if (isset($payload['code'])) {
                // Map Shopee Push Mechanism codes to readable events
                switch ((int)$payload['code']) {
                    case 0: $eventType = 'verify'; break;
                    case 1: $eventType = 'shop_auth_update'; break;
                    case 3: $eventType = 'order_status_update'; break;
                    case 4: $eventType = 'tracking_no_update'; break;
                    case 5: $eventType = 'item_update'; break;
                    case 10: $eventType = 'brand_register_update'; break;
                    case 12: $eventType = 'auth_expiry_push'; break;
                    case 15: $eventType = 'shipping_document_status_update'; break;
                    case 23: $eventType = 'booking_status_update'; break;
                    case 24: $eventType = 'booking_trackingno_update'; break;
                    case 25: $eventType = 'booking_shipping_document_status_update'; break;
                    case 29: $eventType = 'return_updates_push'; break;
                    case 30: $eventType = 'package_fulfillment_status_update'; break;
                    case 47: $eventType = 'package_info_push'; break;
                    default: $eventType = 'code_' . $payload['code'];
                }
            }
            if (isset($payload['type'])) {
                // Fallback for some old formats if any
                $eventType = 'type_' . $payload['type'];
            }
            
            // Just for debugging, try to figure out what type of push it is
            if (isset($payload['data']['ordersn'])) {
                $eventType = 'order_status_update';
            } elseif (isset($payload['data']['tracking_no'])) {
                $eventType = 'tracking_no_update';
            }


            WebhookLog::create([
                'provider' => 'shopee',
                'event_type' => $eventType,
                'signature_verified' => $verified,
                'payload' => $payload,
                'ip_address' => $request->ip()
            ]);

            // Dispatch background job to process the webhook
            if ($verified || true) { // Optional: enforce $verified in production
                \App\Jobs\ProcessShopeeWebhookJob::dispatch($payload, $eventType);
            }

        } catch (\Exception $e) {
            Log::error('Shopee Webhook Logging Error: ' . $e->getMessage());
        }

        return $response;
    }

    /**
     * Get recent logs for UI display
     */
    public function logs(Request $request)
    {
        $provider = $request->query('provider', 'shopee');
        $logs = WebhookLog::where('provider', $provider)
            ->orderBy('created_at', 'desc')
            ->limit(20)
            ->get();
            
        return response()->json($logs);
    }

    /**
     * Simulate Webhook for testing from UI
     */
    public function simulate(Request $request)
    {
        $provider = $request->input('provider', 'shopee');
        $platformId = $request->input('platform_id');
        $eventType = $request->input('event_type', 'order_status_update');
        $orderSn = $request->input('order_sn', 'SIM' . rand(10000, 99999));
        $status = $request->input('status', 'READY_TO_SHIP');

        if ($provider === 'shopee') {
            if ($eventType === 'auth_expiry_push') {
                $payload = [
                    'code' => 12,
                    'shop_id' => $platformId,
                    'timestamp' => time(),
                ];
            } else {
                $payload = [
                    'code' => 3, // Arbitrary code for status update
                    'shop_id' => $platformId,
                    'data' => [
                        'ordersn' => $orderSn,
                        'status' => $status
                    ]
                ];
            }

            // Log it just like a real webhook so it shows in UI
            WebhookLog::create([
                'provider' => 'shopee',
                'event_type' => $eventType,
                'signature_verified' => true, // Simulate valid signature
                'payload' => $payload,
                'ip_address' => $request->ip()
            ]);

            \App\Jobs\ProcessShopeeWebhookJob::dispatch($payload, $eventType);

            return response()->json(['message' => 'Shopee Webhook simulation sent!']);
        }

        return response()->json(['error' => 'Provider not supported'], 400);
    }
}
