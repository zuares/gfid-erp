<?php
namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\MarketplaceOrder;
use App\Models\Store;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;

class MarketplaceLogisticsControllerBulkMethods extends Controller
{
    public function createBulkPrintJob(Request $request)
    {
        $payloadOrders = $request->input('orders', []);
        $mode = $request->input('mode', 'unprinted_only');
        
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

        // Merge PDFs
        $finalPdfContent = null;
        if (count($pdfContents) === 1) {
            $finalPdfContent = $pdfContents[0]['content'];
        } else {
            $pdf = new \setasign\Fpdi\Fpdi();
            $tempFiles = [];
            $overlayService = new \App\Services\ShippingLabelOverlayService();
            foreach ($pdfContents as $item) {
                $tmpPath = storage_path('app/temp_pdf_' . uniqid() . '.pdf');
                $uncompressedContent = $overlayService->uncompressPdfContent($item['content']);
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
            'download_url' => url('/documents/bulk-print/' . $uuid)
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
}
