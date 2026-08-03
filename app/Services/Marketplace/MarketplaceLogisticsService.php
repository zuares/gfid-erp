<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceBooking;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketplaceLogisticsService
{
    public function __construct(
        protected MarketplaceApiGateway $gateway
    ) {}

    /**
     * Fallback lookup chain to get the AWB tracking number.
     * Uses cooldown to prevent multiple queries for the same order in a short time.
     */
    public function syncAwb(Store $store, string $orderSn): ?string
    {
        $order = MarketplaceOrder::where('store_id', $store->id)
            ->where(function ($q) use ($orderSn) {
                $q->where('channel_order_id', $orderSn)
                  ->orWhere('external_order_id', $orderSn)
                  ->orWhere('booking_sn', $orderSn);
            })
            ->first();

        // Cooldown: hindari request berulang untuk order yang sama dalam 30 detik
        $cooldownKey = "syncAwb:{$store->id}:{$orderSn}";
        if (Cache::has($cooldownKey)) {
            return $order?->shipping_awb_no;
        }
        Cache::put($cooldownKey, true, 30);

        $booking = null;
        if (!$order) {
            $booking = MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $orderSn)
                ->first();
        }

        $awb = null;
        try {
            if ($order && !empty($order->booking_sn)) {
                $trackingResp = $this->gateway->getBookingTrackingNumber($store, $order->booking_sn);
                $awb = $trackingResp['response']['tracking_number'] ?? null;
            } elseif ($booking) {
                $trackingResp = $this->gateway->getBookingTrackingNumber($store, $booking->booking_sn);
                $awb = $trackingResp['response']['tracking_number'] ?? null;
            } elseif ($order) {
                $trackingResp = $this->gateway->getTrackingNumber($store, $order->channel_order_id);
                $awb = $trackingResp['response']['tracking_number'] ?? null;
            }

            if (!$awb) {
                $actualOrderSn = $order ? $order->channel_order_id : ($booking ? $booking->order_sn : null);
                if (!empty($actualOrderSn) && (!$order || $actualOrderSn !== $order->booking_sn)) {
                    $details = $this->gateway->getOrderDetail($store, [$actualOrderSn]);
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
            }
        } catch (\Throwable $e) {
            Log::warning("[LogisticsService] Failed to sync AWB for {$orderSn}: " . $e->getMessage());
        }

        return $awb;
    }

    /**
     * Arrange shipment for an order.
     */
    public function arrangeShipment(Store $store, string $orderSn, array $params, bool $isAutoSync = false): array
    {
        $lockKey = "arrangeShipment:{$store->id}:{$orderSn}";
        $lock = Cache::lock($lockKey, 30);
        
        if (! $lock->get()) {
            throw new \RuntimeException('Proses pengiriman sedang berjalan, tunggu sebentar.');
        }

        try {
            $existingOrder = MarketplaceOrder::where('store_id', $store->id)
                ->where('channel_order_id', $orderSn)
                ->first();
                
            if ($existingOrder && $existingOrder->order_status === 'PROCESSED' && $existingOrder->shipping_arranged_at) {
                return ['success' => true, 'message' => 'Pengiriman sudah diatur sebelumnya.', 'awb' => $existingOrder->shipping_awb_no];
            }

            if ($isAutoSync) {
                $rawResult = ['error' => 'logistics.already_arranged'];
            } else {
                try {
                    $rawResult = $this->gateway->shipOrder($store, $orderSn, $params);
                } catch (\BadMethodCallException $e) {
                    $rawResult = ['error' => 'not_supported'];
                }

                if ($this->looksLikeMissingOrderSn($rawResult)) {
                    try {
                        $rawResult = $this->gateway->shipBooking($store, $orderSn, $params);
                        $bErr = (string) ($rawResult['error'] ?? '');
                        if ($bErr === '' || str_contains($bErr, 'already') || str_contains($bErr, 'unsupported') || str_contains($bErr, 'status_invalid')) {
                            MarketplaceBooking::where('booking_sn', $orderSn)
                                ->update(['booking_status' => 'PROCESSED']);
                            \App\Jobs\PromoteBookingToOrderJob::dispatch($store->id, $orderSn)
                                ->delay(now()->addSeconds(15));
                        }
                    } catch (\BadMethodCallException $e) {
                        // Ignore if shipBooking not supported
                    }
                }
            }

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
                    Log::info("shipOrder returned $err for $orderSn, treating as success for local status update.");
                }
            }

            $result = $this->ensureSuccess($rawResult);

            try {
                // Background async AWB update
                sleep(3);
                $awb = null;
                try {
                    $trackingResp = $this->gateway->getTrackingNumber($store, $orderSn);
                    $awb = $trackingResp['response']['tracking_number'] ?? null;
                } catch (\Throwable $e) {}
                
                try {
                    $details = $this->gateway->getOrderDetail($store, [$orderSn]);
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
                        return $result;
                    }
                } catch (\Throwable $e) {}

                if ($awb) {
                    MarketplaceOrder::where('store_id', $store->id)
                        ->where('channel_order_id', $orderSn)
                        ->update([
                            'order_status' => 'PROCESSED',
                            'shipping_awb_no' => $awb,
                            'shipping_arranged_at' => now()
                        ]);
                    return $result;
                }
            } catch (\Exception $e) {
                Log::warning('Gagal fetch order detail setelah arrange shipment: ' . $e->getMessage());
            }

            // Fallback
            MarketplaceOrder::where('store_id', $store->id)
                ->where('channel_order_id', $orderSn)
                ->update([
                    'order_status' => 'PROCESSED',
                    'shipping_arranged_at' => now()
                ]);

            return $result;
        } finally {
            $lock->release();
        }
    }

    /**
     * Arrange shipment for a booking.
     */
    public function shipBooking(Store $store, string $bookingSn, array $params): array
    {
        $lockKey = "shipBooking:{$store->id}:{$bookingSn}";
        $lock = Cache::lock($lockKey, 30);
        if (! $lock->get()) {
            throw new \RuntimeException('Proses pengiriman sedang berjalan, tunggu sebentar.');
        }

        try {
            $existingBooking = MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)
                ->first();
                
            if ($existingBooking && $existingBooking->booking_status === 'PROCESSED') {
                return [
                    'success'         => true,
                    'message'         => 'Pengiriman kilat sudah diatur sebelumnya.',
                    'tracking_number' => $existingBooking->tracking_number,
                    'order_sn'        => $existingBooking->order_sn,
                ];
            }

            $res = $this->gateway->shipBooking($store, $bookingSn, $params);

            if (! empty($res['error'])) {
                $err = (string) $res['error'];
                $tolerable = str_contains($err, 'already')
                    || str_contains($err, 'unsupported')
                    || str_contains($err, 'status_invalid');
                if (! $tolerable) {
                    throw new \RuntimeException($res['message'] ?? $err);
                }
            }

            $tracking = null;
            try {
                sleep(2);
                $trk = $this->gateway->getBookingTrackingNumber($store, $bookingSn);
                $tracking = $trk['response']['tracking_number'] ?? null;
            } catch (\Throwable $e) {}

            MarketplaceBooking::where('store_id', $store->id)
                ->where('booking_sn', $bookingSn)
                ->update(array_filter([
                    'booking_status'  => 'PROCESSED',
                    'tracking_number' => $tracking,
                ]));

            $orderSn = $this->promoteBookingToOrder($store, $bookingSn);

            if (! $orderSn) {
                \App\Jobs\PromoteBookingToOrderJob::dispatch($store->id, $bookingSn)
                    ->delay(now()->addSeconds(15));
            }

            return [
                'success'         => true,
                'message'         => $orderSn
                    ? "Pengiriman kilat berhasil diatur. Nomor pesanan: {$orderSn}."
                    : 'Pengiriman kilat berhasil diatur. Nomor pesanan menyusul otomatis begitu Shopee selesai mencocokkan (±beberapa menit).',
                'tracking_number' => $tracking,
                'order_sn'        => $orderSn,
            ];
        } finally {
            $lock->release();
        }
    }

    protected function promoteBookingToOrder(Store $store, string $bookingSn): ?string
    {
        try {
            return app(MarketplaceSyncService::class)
                ->promoteBookingToOrder($store, $bookingSn);
        } catch (\Throwable $e) {
            Log::warning("promoteBookingToOrder [{$store->id}] {$bookingSn}: " . $e->getMessage());
            return null;
        }
    }

    protected function looksLikeMissingOrderSn(array $result): bool
    {
        $err = (string) ($result['error'] ?? '');
        $msg = (string) ($result['message'] ?? '');

        return str_contains($err, 'order_sn is not exist')
            || str_contains($msg, 'order_sn is not exist')
            || str_contains($err, 'not_found')
            || str_contains($msg, 'not_found')
            || str_contains($err, 'invalid')
            || str_contains($msg, 'invalid');
    }

    protected function ensureSuccess(array $result): array
    {
        if (isset($result['error']) && $result['error']) {
            $msg = $result['message'] ?? $result['error'];
            throw new \RuntimeException($msg);
        }
        $result['success'] = true;
        return $result;
    }
}
