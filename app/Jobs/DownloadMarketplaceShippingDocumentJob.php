<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\MarketplaceOrder;
use App\Services\Channels\ChannelManager;
use App\Services\ShippingLabelOverlayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadMarketplaceShippingDocumentJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $storeId;
    public $orderSn;

    // Retry configurations
    public $tries = 3;
    public $backoff = [60, 180, 300]; // Wait 1min, then 3mins, then 5mins on failures

    /**
     * Create a new job instance.
     */
    public function __construct($storeId, $orderSn)
    {
        $this->storeId = $storeId;
        $this->orderSn = $orderSn;
    }

    /**
     * Execute the job.
     */
    public function handle(ChannelManager $manager)
    {
        $store = Store::find($this->storeId);
        if (!$store) return;

        $order = MarketplaceOrder::where('store_id', $this->storeId)
            ->where('channel_order_id', $this->orderSn)
            ->first();

        if (!$order) return;

        // Cek apakah sudah ter-cache
        $disk = Storage::disk('local');
        $cachePath = "shipping_labels/{$store->id}/{$this->orderSn}.pdf.gz";
        $cachePathNoCard = "shipping_labels/{$store->id}/{$this->orderSn}_nocard.pdf.gz";

        // Jika sudah ada salah satu cache, lewati (sudah aman)
        if ($disk->exists($cachePath) || $disk->exists($cachePathNoCard)) {
            return;
        }

        try {
            $driver = $manager->driver($store);

            $payload = ['order_sn' => $this->orderSn];
            
            // Priority check tracking/package number
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

            // Step 1: Create Document
            $createRes = $driver->createShippingDocument($store, [$payload]);
            if (isset($createRes['error']) && $createRes['error']) {
                $msg = $createRes['message'] ?? '';
                if (str_contains(strtolower($msg), 'shipped')) {
                    // Already shipped, cannot download
                    return;
                }
                if (isset($createRes['response']['result_list']) && is_array($createRes['response']['result_list'])) {
                    foreach ($createRes['response']['result_list'] as $resItem) {
                        if (isset($resItem['fail_message']) && str_contains(strtolower($resItem['fail_message']), 'shipped')) {
                            return; // Cannot cache
                        }
                    }
                }
                Log::warning("AutoDownloadLabel: Create failed for {$this->orderSn}", ['res' => $createRes]);
                $this->release(60); // Retry in 1 min
                return;
            }

            // Step 2: Download Document
            $downloadRes = $driver->getShippingDocument($store, [$payload]);
            
            if (isset($downloadRes['error']) && $downloadRes['error'] === 'invalid_response') {
                $content = $downloadRes['message']; // Raw body from stream
                if (str_starts_with($content, '%PDF')) {
                    // Kita cache versi default (dengan greeting card aktif)
                    $overlayService = new ShippingLabelOverlayService();
                    $content = $overlayService->overlayPdfContent($content, ['marketplace_print_greeting_card' => '1']);

                    $disk->put($cachePath, gzencode($content, 9));
                    Log::info("AutoDownloadLabel: Successfully cached {$this->orderSn}");
                    return;
                }
            }
            
            // If it returns normal JSON error (e.g., document not ready)
            if (isset($downloadRes['error'])) {
                Log::warning("AutoDownloadLabel: Document not ready for {$this->orderSn}", ['res' => $downloadRes]);
                $this->release(120); // Retry in 2 mins
                return;
            }

        } catch (\Throwable $e) {
            Log::error("AutoDownloadLabel: Error processing {$this->orderSn} - " . $e->getMessage());
            throw $e; // Throw to trigger retries
        }
    }
}
