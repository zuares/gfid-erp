<?php

namespace App\Jobs;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
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
        Log::info("Processing Shopee Webhook Event: {$this->eventType}");

        if ($this->eventType === 'order_status_update') {
            $this->handleOrderStatusUpdate();
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
        } else {
            Log::info("No specific handler for event: {$this->eventType}");
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
                if ($localOrder->order_status === 'PROCESSED' && $status === 'READY_TO_SHIP') {
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
            $localOrder->update(['booking_sn' => $trackingNo]);
            Log::info("Updated local order {$orderSn} with tracking_no: {$trackingNo}");
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

        // Try to find by order_sn (since usually booking_sn is ordersn, or we sync it)
        $localOrder = MarketplaceOrder::where('channel_order_id', $bookingSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $meta['booking_status'] = $bookingStatus;
            $localOrder->update(['meta' => $meta]);
            Log::info("Updated local order {$bookingSn} booking_status to: {$bookingStatus}");
            
            event(new \App\Events\OrderUpdated($store->id, $bookingSn, $localOrder->order_status));
        } else {
            Log::info("Order/Booking {$bookingSn} not found locally during booking_status_update. Syncing via API.");
            try {
                // If bookingSn is actually an orderSn, this will pull the order details
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $bookingSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing booking/order {$bookingSn}: " . $e->getMessage());
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

        $localOrder = MarketplaceOrder::where('channel_order_id', $bookingSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            // Update the booking_sn (tracking number) for the order
            $localOrder->update(['booking_sn' => $trackingNo]);
            Log::info("Updated local order {$bookingSn} with booking_trackingno: {$trackingNo}");
            event(new \App\Events\OrderUpdated($store->id, $bookingSn, $localOrder->order_status));
        } else {
            Log::info("Order/Booking {$bookingSn} not found locally during booking_trackingno_update. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $bookingSn);
                // The sync process should fetch the new tracking number automatically
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

        $localOrder = MarketplaceOrder::where('channel_order_id', $bookingSn)
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

        $localOrder = MarketplaceOrder::where('channel_order_id', $orderSn)
            ->where('store_id', $store->id)
            ->first();

        if ($localOrder) {
            $meta = $localOrder->meta ?? [];
            $meta['package_fulfillment_status'] = $fulfillmentStatus;
            $localOrder->update(['meta' => $meta]);
            Log::info("Updated local order {$orderSn} package_fulfillment_status to: {$fulfillmentStatus}");
            
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->order_status));
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
                 Log::info("Updated local order {$orderSn} status to TO_RETURN due to return_updates_push.");
            } else {
                 $localOrder->update(['meta' => $meta]);
            }
            
            Log::info("Processed return_updates_push for order {$orderSn}, return_sn {$returnSn}");
            event(new \App\Events\OrderUpdated($store->id, $orderSn, $localOrder->order_status));
        } else {
            Log::info("Order {$orderSn} not found locally during return_updates_push. Syncing via API.");
            try {
                app(\App\Services\OmnichannelSyncService::class)->syncSpecificOrder($store, $orderSn);
            } catch (\Exception $e) {
                Log::error("Failed to sync missing order {$orderSn}: " . $e->getMessage());
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
}
