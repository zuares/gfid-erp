<?php

namespace App\Jobs;

use App\Models\Store;
use App\Models\MarketplaceOrder;
use App\Services\Channels\ChannelManager;
use App\Services\ShippingLabelOverlayService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldBeUnique;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Storage;

class DownloadMarketplaceShippingDocumentJob implements ShouldQueue, ShouldBeUnique
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public $storeId;
    public $orderSn;

    // Retry configurations
    public $tries = 6;
    public $backoff = [60, 180, 300]; // Wait 1min, then 3mins, then 5mins on failures

    /**
     * UNIK per (store, order_sn): selama job untuk order yang sama masih antre /
     * berjalan, dispatch baru DIABAIKAN. Tanpa ini, cron sync 5-menit men-dispatch
     * ulang job untuk semua order READY_TO_SHIP/PROCESSED di jendelanya setiap kali
     * jalan — antrean pernah tersumbat ~979 job duplikat.
     */
    public int $uniqueFor = 3600;

    public function uniqueId(): string
    {
        return $this->storeId . ':' . $this->orderSn;
    }

    /**
     * Create a new job instance.
     */
    public function __construct($storeId, $orderSn)
    {
        $this->storeId = $storeId;
        $this->orderSn = $orderSn;

        // Queue 'labels' terpisah: worker default memprosesnya SETELAH queue default
        // kosong (--queue=default,labels), jadi webhook/real-time tak pernah tertahan
        // di belakang unduhan resi massal.
        $this->onQueue('labels');
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
                // Kasus transient: dokumen label belum siap dicetak. Shopee butuh
                // beberapa saat setelah order untuk menyiapkan PDF-nya. Ini BUKAN
                // kegagalan sungguhan — cukup coba lagi nanti tanpa membisingkan log.
                $notReady = false;
                if (isset($createRes['response']['result_list']) && is_array($createRes['response']['result_list'])) {
                    foreach ($createRes['response']['result_list'] as $resItem) {
                        $fe = strtolower((string) ($resItem['fail_error'] ?? ''));
                        $fm = strtolower((string) ($resItem['fail_message'] ?? ''));
                        if (str_contains($fe, 'package_can_not_print')
                            || str_contains($fm, 'not yet ready')
                            || str_contains($fm, 'try again later')) {
                            $notReady = true;
                            break;
                        }
                    }
                }

                if ($notReady) {
                    if ($this->attempts() >= $this->tries) {
                        // Sudah dicoba maksimal tapi Shopee belum juga menyiapkan label.
                        // Hentikan diam-diam (delete) agar TIDAK memicu ERROR
                        // MaxAttemptsExceededException + stacktrace di log.
                        Log::info("AutoDownloadLabel: label {$this->orderSn} belum siap setelah {$this->tries} percobaan, berhenti.");
                        $this->delete();
                        return;
                    }
                    Log::info("AutoDownloadLabel: label belum siap untuk {$this->orderSn}, coba lagi nanti");
                    $this->release(180); // beri waktu Shopee menyiapkan dokumen
                    return;
                }

                if ($this->attempts() >= $this->tries) {
                    Log::warning("AutoDownloadLabel: Create gagal untuk {$this->orderSn} setelah {$this->tries} percobaan, berhenti.", ['res' => $createRes]);
                    $this->delete();
                    return;
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
                if ($this->attempts() >= $this->tries) {
                    Log::warning("AutoDownloadLabel: Document {$this->orderSn} tak kunjung siap setelah {$this->tries} percobaan, berhenti.", ['res' => $downloadRes]);
                    $this->delete();
                    return;
                }
                Log::warning("AutoDownloadLabel: Document not ready for {$this->orderSn}", ['res' => $downloadRes]);
                $this->release(120); // Retry in 2 mins
                return;
            }

        } catch (\Throwable $e) {
            Log::error("AutoDownloadLabel: Error processing {$this->orderSn} - " . $e->getMessage());
            throw $e; // Throw to trigger retries
        }
    }

    /**
     * Dipanggil bila job benar-benar gagal permanen (mis. exception terus-menerus).
     * Dicatat ringkas agar tidak perlu menelusuri stacktrace panjang.
     */
    public function failed(\Throwable $e): void
    {
        Log::warning("AutoDownloadLabel: job untuk {$this->orderSn} gagal permanen: " . $e->getMessage());
    }
}
