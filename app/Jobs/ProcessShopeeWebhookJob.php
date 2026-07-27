<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Cache;
use App\Models\MarketplaceOrder;
use App\Models\Store;

class ProcessShopeeWebhookJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    protected $payload;
    protected $eventType;

    /**
     * Create a new job instance.
     */
    public function __construct(array $payload, string $eventType)
    {
        $this->payload = $payload;
        $this->eventType = $eventType;
    }

    /**
     * Execute the job.
     */
    public function handle(): void
    {
        $shopId = $this->payload['shop_id'] ?? null;

        // Idempotensi: Shopee kerap mengirim push yang sama beberapa kali dalam
        // hitungan detik. Tanpa guard, tiap salinan jadi job terpisah dan memicu
        // sync API berulang (bahkan race saat insert order yang sama). Hanya proses
        // salinan pertama untuk kombinasi event+shop+data yang identik.
        $dedupKey = 'shopee:webhook:' . md5(
            $this->eventType . '|' . ($shopId ?? '') . '|' . json_encode($this->payload['data'] ?? [])
        );
        if (! Cache::add($dedupKey, true, now()->addSeconds(60))) {
            Log::info("Shopee Webhook duplikat dilewati: {$this->eventType}" . ($shopId ? " (shop {$shopId})" : ''));
            return;
        }

        Log::info("Processing Shopee Webhook Event: {$this->eventType}");

        if ($shopId) {
            $store = \App\Models\Store::where('external_shop_id', (string)$shopId)
                ->whereHas('channel', function($q) {
                    $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
                })->first();
                
            if ($store) {
                $meta = $store->meta ?? [];
                $meta['last_webhook_at'] = now()->toISOString();
                $store->update(['meta' => $meta]);
            }
        }

        if ($this->eventType === 'order_status_update') {
            $this->handleOrderStatusUpdate();
        } elseif ($this->eventType === 'shop_auth_update') {
            $this->handleShopAuthUpdate();
        } elseif ($this->eventType === 'tracking_no_update') {
            $this->handleTrackingNoUpdate();
        } elseif ($this->eventType === 'auth_expiry_push') {
            $this->handleAuthExpiryPush();
        } elseif ($this->eventType === 'shipping_document_status_update') {
            $this->handleShippingDocumentStatusUpdate();
        } elseif ($this->eventType === 'booking_status_update') {
            $this->handleBookingStatusUpdate();
        } elseif ($this->eventType === 'booking_trackingno_update') {
            $this->handleBookingTrackingNoUpdate();
        } elseif ($this->eventType === 'booking_shipping_document_status_update') {
            $this->handleBookingShippingDocumentStatusUpdate();
        } elseif ($this->eventType === 'package_fulfillment_status_update') {
            $this->handlePackageFulfillmentStatusUpdate();
        } elseif ($this->eventType === 'return_updates_push') {
            $this->handleReturnUpdatesPush();
        } elseif ($this->eventType === 'package_info_push') {
            $this->handlePackageInfoPush();
        } elseif ($this->eventType === 'courier_delivery_binding_status_update') {
            $this->handleCourierDeliveryBindingStatusUpdate();
        } elseif ($this->eventType === 'item_update') {
            $this->handleItemUpdate();
        } else {
            Log::info("No specific handler for event: {$this->eventType}");
        }
    }

    /**
     * Push code 5: item di-update di Shopee (harga/stok/status berubah)
     * → re-sync item tersebut + broadcast agar halaman Produk refresh.
     */
    protected function handleItemUpdate()
    {
        $shopId = $this->payload['shop_id'] ?? null;
        if (!$shopId) return;

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })->first();
        if (!$store) return;

        // Payload bisa berisi satu item_id atau daftar item
        $itemIds = [];
        $data = $this->payload['data'] ?? [];
        if (!empty($data['item_id'])) {
            $itemIds[] = $data['item_id'];
        }
        foreach (($data['items'] ?? []) as $it) {
            if (!empty($it['item_id'])) $itemIds[] = $it['item_id'];
        }

        $service = app(\App\Services\MarketplaceProductService::class);
        foreach (array_unique($itemIds) as $itemId) {
            try {
                $service->syncSingleItem($store, $itemId);
                event(new \App\Events\ProductUpdated($store->id, (string)$itemId));
            } catch (\Throwable $e) {
                Log::warning("item_update: gagal sync item {$itemId}: " . $e->getMessage());
            }
        }
    }

    protected function handleOrderStatusUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $orderSn = $data['ordersn'] ?? null;
        $status = $data['status'] ?? null;
        $completedScenario = $data['completed_scenario'] ?? null;
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$orderSn || !$shopId) {
            return;
        }

        // Find the store that owns this shop_id
        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();
        
        if (!$store) {
            Log::warning("Shopee Webhook received for unknown shop_id: {$shopId}");
            return;
        }

        // Example logic: update the local MarketplaceOrder status if it exists.
        // Or fetch full details using ShopeeChannel if you want to sync it fresh.
        $localOrder = MarketplaceOrder::where('channel_order_id', $orderSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            if ($status) {
                $meta = $localOrder->meta ?? [];
                if ($completedScenario) {
                    $meta['completed_scenario'] = $completedScenario;
                }

                // Prevent reverting a locally 'PROCESSED' (sedang dikemas) order back to 'READY_TO_SHIP'
                // tapi JANGAN blok status yang maju ke depan (SHIPPED, TO_CONFIRM_RECEIVE, COMPLETED).
                $isRollback = $localOrder->order_status === 'PROCESSED' && $status === 'READY_TO_SHIP';
                if ($isRollback) {
                    Log::info("Order {$orderSn} is already PROCESSED locally. Ignoring READY_TO_SHIP webhook.");
                    // Still update meta just in case
                    $localOrder->update(['meta' => $meta]);
                } else {
                    $localOrder->update(['order_status' => $status, 'meta' => $meta]);
                    Log::info("Updated local order {$orderSn} to status: {$status}");
                    
                    if (in_array($status, ['READY_TO_SHIP', 'PROCESSED'])) {
                        \App\Jobs\DownloadMarketplaceShippingDocumentJob::dispatch($store->id, $orderSn);
                    }
                    
                    event(new \App\Events\OrderUpdated($store->id, $orderSn, $status));
                }
            }

        } else {
            Log::info("Order {$orderSn} not found locally. Syncing specific order via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
                
                // Broadcast an event to trigger UI refresh since we just synced a new order
                event(new \App\Events\OrderUpdated($store->id, $orderSn, $status));
            } catch (\Exception $e) {
                Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
            }
        }
    }

    protected function handleTrackingNoUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $orderSn = $data['ordersn'] ?? null;
        $trackingNo = $data['tracking_no'] ?? null;
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$orderSn || !$trackingNo || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();
        if (!$store) return;

        $localOrder = MarketplaceOrder::where('channel_order_id', $orderSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $localOrder->update(['shipping_awb_no' => $trackingNo]);
            Log::info("Updated local order {$orderSn} with tracking_no (shipping_awb_no): {$trackingNo}");
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->order_status));
        } else {
            Log::info("Order {$orderSn} not found locally during tracking_no_update. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
            }
        }
    }

    protected function handleAuthExpiryPush()
    {
        $shopId = $this->payload['shop_id'] ?? null;
        if (!$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if ($store) {
            // Update token expiration to now, which triggers 'AUTH_REQUIRED' or 'TOKEN_EXPIRED'
            $store->update(['token_expires_at' => now()]);
            Log::info("Updated store {$store->name} auth token expiry due to auth_expiry_push webhook.");
        }
    }

    protected function handleShippingDocumentStatusUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $orderSn = $data['ordersn'] ?? null;
        $status = $data['status'] ?? null; // e.g., 'READY' or 'FAILED'
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$orderSn || !$status || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        $localOrder = MarketplaceOrder::where('channel_order_id', $orderSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            // Store it in meta or a specific column if it exists.
            // Assuming you want to trigger frontend update for printing:
            $meta = $localOrder->meta ?? [];
            $meta['shipping_document_status'] = $status;
            $localOrder->update(['meta' => $meta]);
            Log::info("Updated local order {$orderSn} shipping_document_status to: {$status}");
            
            // Broadcast the update so the UI "Cetak Resi" button changes immediately
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->order_status));
        } else {
            Log::info("Order {$orderSn} not found locally during shipping_document_status_update. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
            }
        }
    }

    protected function handleBookingStatusUpdate()
    {
        $data = $this->payload['data'] ?? [];
        // The booking_sn might act as the order_sn or a sub-package id.
        $bookingSn = $data['booking_sn'] ?? null;
        $bookingStatus = $data['booking_status'] ?? null;
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$bookingSn || !$bookingStatus || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        // Simpan/masukkan booking ke tabel bookings (sumber kebenaran Pesanan Kilat).
        // Dibuat walau order_sn belum ada — inilah kasus booking murni / kilat.
        $bModel = \App\Models\MarketplaceBooking::updateOrCreate(
            ['store_id' => $store->id, 'booking_sn' => $bookingSn],
            ['booking_status' => $bookingStatus]
        );

        // Jika status webhook jadi PROCESSED/SHIPPED, otomatis coba tarik resinya
        if (in_array(strtoupper((string) $bookingStatus), ['PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'COMPLETED']) && blank($bModel->tracking_number)) {
            try {
                $driver = app(\App\Services\Channels\ChannelManager::class)->driver($store);
                if (method_exists($driver, 'getBookingTrackingNumber')) {
                    $trk = $driver->getBookingTrackingNumber($store, $bookingSn);
                    $trkNum = $trk['response']['tracking_number'] ?? null;
                    if ($trkNum) {
                        $bModel->update(['tracking_number' => $trkNum]);
                    }
                }
            } catch (\Throwable $e) {}
        }

        // Booking berstatus lanjut tapi belum punya order_sn → promosi via job idempotent
        // (get_booking_detail → order_sn → tarik order → tautkan). Satu implementasi
        // dengan tombol Atur Pengiriman; polling mundur menangani matching asinkron Shopee.
        if (blank($bModel->order_sn)
            && in_array(strtoupper((string) $bookingStatus), ['MATCHED', 'PROCESSED', 'SHIPPED', 'READY_TO_HANDOVER', 'COMPLETED'])) {
            \App\Jobs\PromoteBookingToOrderJob::dispatch($store->id, $bookingSn);
        }

        // Cari order berdasarkan channel_order_id ATAU booking_sn
        $localOrder = MarketplaceOrder::where(function($q) use ($bookingSn) {
                $q->where('channel_order_id', $bookingSn)
                  ->orWhere('booking_sn', $bookingSn);
            })
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $meta['booking_status'] = $bookingStatus;
            
            $updates = ['meta' => $meta];
            
            // Mapping optimis status booking ke order_status agar UI langsung memindahkannya
            $bookingStatusUpper = strtoupper((string) $bookingStatus);
            if ($bookingStatusUpper === 'PROCESSED') {
                $updates['order_status'] = 'PROCESSED';
            } elseif (in_array($bookingStatusUpper, ['SHIPPED', 'READY_TO_HANDOVER'])) {
                // Kurir sudah mengambil / sedang dalam perjalanan → pindah ke tab Sedang Dikirim
                $updates['order_status'] = 'SHIPPED';
            } elseif ($bookingStatusUpper === 'COMPLETED') {
                $updates['order_status'] = 'COMPLETED';
            } elseif (in_array($bookingStatusUpper, ['CANCELLED_BEFORE_SHIPPING', 'CANCELLED'])) {
                $updates['order_status'] = 'CANCELLED';
            }
            
            // Ambil detail booking untuk menukar booking_sn menjadi order_sn asli
            try {
                $manager = app(\App\Services\Channels\ChannelManager::class);
                $driver = $manager->driver($store);
                if (method_exists($driver, 'getBookingDetail')) {
                    $detailRes = $driver->getBookingDetail($store, $bookingSn);
                    if (empty($detailRes['error'])) {
                        $bookings = $detailRes['response']['booking_list'] ?? [];
                        foreach ($bookings as $b) {
                            if ($b['booking_sn'] === $bookingSn) {
                                $ordersList = $b['order_list'] ?? [];
                                if (!empty($ordersList[0]['order_sn'])) {
                                    $realOrderSn = $ordersList[0]['order_sn'];
                                    if ($localOrder->channel_order_id !== $realOrderSn) {
                                        $updates['channel_order_id'] = $realOrderSn;
                                        $updates['external_order_id'] = $realOrderSn;
                                        $updates['booking_sn'] = $bookingSn;
                                        Log::info("Swapped order {$bookingSn} channel_order_id to real order_sn: {$realOrderSn}");
                                    }
                                }
                                break;
                            }
                        }
                    }
                }
            } catch (\Exception $e) {
                Log::error("Failed to get booking detail to swap order_sn for {$bookingSn}: " . $e->getMessage());
            }

            $localOrder->update($updates);
            Log::info("Updated local order {$bookingSn} booking_status to: {$bookingStatus}");
            
            event(new \App\Events\OrderUpdated($store->id, $bookingSn, $localOrder->order_status));
        } else {
            Log::info("Order/Booking {$bookingSn} not found locally during booking_status_update. Syncing via API.");
            try {
                // If bookingSn is actually an orderSn, this will pull the order details
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $bookingSn);
                event(new \App\Events\OrderUpdated($store->id, $bookingSn, null));
            } catch (\Exception $e) {
                Log::error("Failed to sync missing booking/order {$bookingSn}: " . $e->getMessage());
                // Still dispatch event so the UI can at least refresh with the updated MarketplaceBooking status
                event(new \App\Events\OrderUpdated($store->id, $bookingSn, null));
            }
        }
    }

    protected function handleBookingTrackingNoUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $bookingSn = $data['booking_sn'] ?? null;
        $trackingNo = $data['tracking_number'] ?? null;
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$bookingSn || !$trackingNo || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        // Simpan nomor resi ke tabel bookings — TERPISAH dari booking_sn (tidak lagi
        // menimpa identitas booking). Ini yang benar untuk Pesanan Kilat.
        \App\Models\MarketplaceBooking::updateOrCreate(
            ['store_id' => $store->id, 'booking_sn' => $bookingSn],
            ['tracking_number' => $trackingNo]
        );

        $localOrder = MarketplaceOrder::where(function($q) use ($bookingSn) {
                $q->where('channel_order_id', $bookingSn)
                  ->orWhere('booking_sn', $bookingSn);
            })
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $updates = [
                'booking_sn' => $bookingSn, // keep the booking_sn explicitly
                'shipping_awb_no' => $trackingNo
            ];
            
            // If the tracking number is essentially the booking sn, we just keep it in shipping_awb_no
            $localOrder->update($updates);
            Log::info("Updated local order {$bookingSn} with shipping_awb_no: {$trackingNo}");
            event(new \App\Events\OrderUpdated($store->id, $localOrder->channel_order_id, $localOrder->order_status));
        } else {
            Log::info("Order/Booking {$bookingSn} not found locally during booking_trackingno_update. Syncing via API.");
            try {
                // Untuk kilat, order_sn mungkin belum terbentuk atau belum ditarik. 
                // Karena kita sudah mengupdate MarketplaceBooking di atas, kita cukup memicu event.
                // Jika ingin menarik detail penuh, kita panggil syncBookings (atau getBookingDetail).
                // Saat ini, updateOrCreate MarketplaceBooking di atas sudah cukup sebagai dasar data.
                
                // Gunakan SyncMarketplaceBookings job untuk menarik detail booking ini
                dispatch_sync(new \App\Jobs\SyncMarketplaceBookings($store, $bookingSn, null, false));
                
                event(new \App\Events\OrderUpdated($store->id, $bookingSn, null));
            } catch (\Exception $e) {
                Log::error("Failed to sync missing booking/order {$bookingSn}: " . $e->getMessage());
            }
        }
    }

    protected function handleBookingShippingDocumentStatusUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $bookingSn = $data['booking_sn'] ?? null;
        $status = $data['status'] ?? null; // e.g., 'READY' or 'FAILED'
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$bookingSn || !$status || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        // Simpan status dokumen kirim booking ke tabel bookings.
        \App\Models\MarketplaceBooking::updateOrCreate(
            ['store_id' => $store->id, 'booking_sn' => $bookingSn],
            ['shipping_document_status' => $status]
        );

        $localOrder = MarketplaceOrder::where(function($q) use ($bookingSn) {
                $q->where('channel_order_id', $bookingSn)
                  ->orWhere('booking_sn', $bookingSn);
            })
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $meta['booking_shipping_document_status'] = $status;
            $localOrder->update(['meta' => $meta]);
            Log::info("Updated local order {$bookingSn} booking_shipping_document_status to: {$status}");
            
            event(new \App\Events\OrderUpdated($store->id, $bookingSn, $localOrder->order_status));
        } else {
            Log::info("Order/Booking {$bookingSn} not found locally during booking_shipping_document_status_update. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $bookingSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing booking/order {$bookingSn}: " . $e->getMessage());
            }
        }
    }

    protected function handlePackageFulfillmentStatusUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $orderSn = $data['ordersn'] ?? null;
        $fulfillmentStatus = $data['fulfillment_status'] ?? null;
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$orderSn || !$fulfillmentStatus || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        $localOrder = MarketplaceOrder::where(function($q) use ($orderSn) {
                $q->where('channel_order_id', $orderSn)
                  ->orWhere('booking_sn', $orderSn);
            })
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $meta['package_fulfillment_status'] = $fulfillmentStatus;
            
            $updates = ['meta' => $meta];
            
            // Map fulfillment status → order_status agar tab UI otomatis berubah
            // Ref: Shopee V2 PackageFulfillmentStatus data definition
            $fulfillmentUpper = strtoupper((string) $fulfillmentStatus);
            if (in_array($fulfillmentUpper, ['LOGISTICS_PICKUP_DONE', 'LOGISTICS_PICKUP_RETRY'])) {
                // Kurir sudah mengambil paket → Sedang Dikirim
                $updates['order_status'] = 'SHIPPED';
            } elseif ($fulfillmentUpper === 'LOGISTICS_DELIVERY_DONE') {
                // Paket sudah diterima pembeli → Menunggu Konfirmasi
                $updates['order_status'] = 'TO_CONFIRM_RECEIVE';
            } elseif ($fulfillmentUpper === 'LOGISTICS_INVALID_OR_LOST') {
                // Paket hilang / tidak valid — simpan di meta saja, jangan ubah status
                Log::warning("Package {$orderSn} marked as LOGISTICS_INVALID_OR_LOST.");
            } elseif (in_array($fulfillmentUpper, ['LOGISTICS_REQUEST_CREATED', 'LOGISTICS_READY_TO_SHIP'])) {
                // Pengiriman diatur, belum diambil kurir → tetap PROCESSED
            }
            
            $localOrder->update($updates);
            Log::info("Updated local order {$orderSn} package_fulfillment_status to: {$fulfillmentStatus}" .
                (isset($updates['order_status']) ? " (order_status → {$updates['order_status']})" : ''));
            
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->fresh()->order_status));
        } else {
            Log::info("Order {$orderSn} not found locally during package_fulfillment_status_update. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
            }
        }
    }

    protected function handleReturnUpdatesPush()
    {
        $data = $this->payload['data'] ?? [];
        $orderSn = $data['order_sn'] ?? null;
        $returnSn = $data['return_sn'] ?? null;
        $updatedValues = $data['updated_values'] ?? [];
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$orderSn || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        $localOrder = MarketplaceOrder::where('channel_order_id', $orderSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            if ($returnSn) {
                $meta['return_sn'] = $returnSn;
            }
            // Record the updated fields
            $meta['return_updates'] = array_merge($meta['return_updates'] ?? [], $updatedValues);
            
            // Note: A return typically means the order status might have changed to TO_RETURN or CANCELLED, 
            // but we rely on order_status_push for the main order_status. 
            // If we want to be safe, we could force-update the status to 'TO_RETURN' if not already.
            if ($localOrder->order_status !== 'TO_RETURN' && $localOrder->order_status !== 'CANCELLED') {
                 $localOrder->update(['order_status' => 'TO_RETURN', 'meta' => $meta]);
                 \Illuminate\Support\Facades\Log::info("Updated local order {$orderSn} status to TO_RETURN due to return_updates_push.");
            } else {
                 $localOrder->update(['meta' => $meta]);
            }
            
            \Illuminate\Support\Facades\Log::info("Processed return_updates_push for order {$orderSn}, return_sn {$returnSn}");
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->order_status));
        } else {
            \Illuminate\Support\Facades\Log::info("Order {$orderSn} not found locally during return_updates_push. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
            }
        }

        // ==========================================
        // HYBRID ARCHITECTURE: SYNC RETURN DETAIL TO DB
        // ==========================================
        if ($returnSn) {
            try {
                $manager = app(\App\Services\Channels\ChannelManager::class);
                $driver = $manager->driver($store);
                if (method_exists($driver, 'getReturnDetail')) {
                    $res = $driver->getReturnDetail($store, $returnSn);
                    $data = $res['response'] ?? null;
                    
                    if ($data) {
                        $returnObj = \App\Models\MarketplaceReturn::updateOrCreate(
                            [
                                'store_id' => $store->id,
                                'return_sn' => $returnSn,
                            ],
                            [
                                'order_sn' => $data['order_sn'] ?? $orderSn,
                                'status' => $data['status'] ?? null,
                                'reason' => $data['reason'] ?? null,
                                'reason_text_code' => $data['text_reason'] ?? $data['reason_text_code'] ?? null,
                                'return_solution' => $data['return_solution'] ?? null,
                                'amount_before_discount' => $data['amount_before_discount'] ?? 0,
                                'needs_logistics' => $data['needs_logistics'] ?? false,
                                'tracking_number' => $data['tracking_number'] ?? null,
                                'create_time' => $data['create_time'] ?? null,
                                'update_time' => $data['update_time'] ?? null,
                            ]
                        );

                        if (isset($data['item']) && is_array($data['item'])) {
                            $existingItemIds = [];
                            foreach ($data['item'] as $itm) {
                                $sku = $itm['item_sku'] ?? $itm['variation_sku'] ?? null;
                                $internalItemId = null;
                                if ($sku) {
                                    $internalItem = \App\Models\Item::where('code', $sku)->first();
                                    if ($internalItem) {
                                        $internalItemId = $internalItem->id;
                                    }
                                }

                                $retItem = \App\Models\MarketplaceReturnItem::updateOrCreate(
                                    [
                                        'marketplace_return_id' => $returnObj->id,
                                        'item_sku' => $itm['item_sku'] ?? null,
                                        'variation_sku' => $itm['variation_sku'] ?? null,
                                        'item_name' => $itm['item_name'] ?? $itm['name'] ?? null,
                                    ],
                                    [
                                        'item_id' => $internalItemId,
                                        'variation_name' => $itm['variation_name'] ?? null,
                                        'return_item_quantity' => $itm['amount'] ?? $itm['return_item_quantity'] ?? 1,
                                        'images' => isset($itm['images']) && is_array($itm['images']) ? $itm['images'] : null,
                                    ]
                                );
                                $existingItemIds[] = $retItem->id;
                            }
                            
                            if (!empty($existingItemIds)) {
                                \App\Models\MarketplaceReturnItem::where('marketplace_return_id', $returnObj->id)
                                    ->whereNotIn('id', $existingItemIds)
                                    ->delete();
                            }
                        }
                        \Illuminate\Support\Facades\Log::info("Successfully synced return_sn {$returnSn} to database via Webhook.");
                    }
                }
            } catch (\Exception $e) {
                \Illuminate\Support\Facades\Log::error("Failed to sync return detail for return_sn {$returnSn}: " . $e->getMessage());
            }
        }
    }

    protected function handlePackageInfoPush()
    {
        $data = $this->payload['data'] ?? [];
        $orderSn = $data['order_sn'] ?? null;
        $packageNumber = $data['package_number'] ?? null;
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$orderSn || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        $localOrder = MarketplaceOrder::where('channel_order_id', $orderSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $changedFields = $data['changed_fields'] ?? [];
            $newData = $data['new'] ?? [];
            
            foreach ($changedFields as $field) {
                if (isset($newData[$field])) {
                    $meta["package_info_{$field}"] = $newData[$field];
                }
            }
            
            $localOrder->update(['meta' => $meta]);
            Log::info("Processed package_info_push for order {$orderSn}. Fields updated: " . implode(', ', $changedFields));
            
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->order_status));
        } else {
            Log::info("Order {$orderSn} not found locally during package_info_push. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
            }
        }
    }

    /**
     * Handle courier_delivery_binding_status_push (Code 37)
     */
    protected function handleCourierDeliveryBindingStatusUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $trackingNo = $data['first_mile_tracking_number'] ?? null;
        $status = $data['status'] ?? null; // e.g., ORDER_RECEIVED, PICKED_UP, CANCELED
        $shopId = $this->payload['shop_id'] ?? null;

        if (!$trackingNo || !$status || !$shopId) {
            return;
        }

        $store = Store::where('external_shop_id', (string)$shopId)
            ->whereHas('channel', function($q) {
                $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
            })
            ->first();

        if (!$store) return;

        // Find booking by tracking number
        $booking = \App\Models\MarketplaceBooking::where('store_id', $store->id)
            ->where('tracking_number', $trackingNo)
            ->first();

        // Also check if there is an order that has this tracking number as booking_sn
        $localOrder = MarketplaceOrder::where('store_id', $store->id)
            ->where('booking_sn', $trackingNo)
            ->first();

        // If neither found, we can't reliably update anything since we don't know the order_sn
        if (!$booking && !$localOrder) {
            Log::info("Tracking number {$trackingNo} not found locally during courier_delivery_binding_status_update.");
            return;
        }

        if ($booking) {
            $meta = $booking->meta ?? [];
            $meta['courier_status'] = $status;
            $booking->update(['meta' => $meta]);
        }

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $meta['courier_status'] = $status;
            $localOrder->update(['meta' => $meta]);
            Log::info("Updated local order for tracking {$trackingNo} courier_status to: {$status}");
            
            event(new \App\Events\OrderUpdated($store->id, $localOrder->channel_order_id, $localOrder->order_status));
        }
    }

    /**
     * Handle shop_authorization_push (Code 1)
     */
    protected function handleShopAuthUpdate()
    {
        $data = $this->payload['data'] ?? [];
        $shopId = $this->payload['shop_id'] ?? $data['shop_id'] ?? null;
        
        $shopIds = [];
        if ($shopId) {
            $shopIds[] = $shopId;
        }
        if (!empty($data['shop_id_list'])) {
            $shopIds = array_merge($shopIds, $data['shop_id_list']);
        }
        $shopIds = array_unique($shopIds);

        foreach ($shopIds as $id) {
            $store = \App\Models\Store::where('external_shop_id', (string)$id)
                ->whereHas('channel', function($q) {
                    $q->whereIn('code', ['SHOPEE', 'SHP', 'shopee']);
                })->first();

            if ($store) {
                // If it was disconnected, this webhook confirms it has been authorized again
                $store->update(['connection_status' => 'CONNECTED']);
                Log::info("Store {$store->name} (ID: {$store->id}) authorized and marked as CONNECTED via shop_authorization_push.");
            } else {
                Log::info("Shopee shop_authorization_push received for unknown shop_id: {$id}. Store cannot be created via webhook alone because access_token is missing.");
            }
        }
    }
}
