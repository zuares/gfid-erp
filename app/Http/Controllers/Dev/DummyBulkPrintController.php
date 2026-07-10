<?php

namespace App\Http\Controllers\Dev;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Cache;
use App\Support\DummyMarketplaceOrderProvider;

class DummyBulkPrintController extends Controller
{
    public function createBulkPrintJob(Request $request)
    {
        if (!app()->environment(['local', 'testing'])) {
            abort(404);
        }

        $request->validate([
            'orders' => 'required|array',
            'orders.*.store_id' => 'required',
            'orders.*.order_sn' => 'required',
            'orders.*.position' => 'required|integer',
            'scenario' => 'nullable|string',
            'mode' => 'nullable|string', // unprinted_only, selected, reprint
        ]);

        $payloadOrders = collect($request->input('orders'));
        $scenario = $request->input('scenario');
        
        $provider = app(DummyMarketplaceOrderProvider::class);
        $dummyOrders = $provider->orders()->keyBy('channel_order_id');

        $uuid = Str::uuid()->toString();
        $results = [
            'success_count' => 0,
            'failed_count' => 0,
            'failed_orders' => [],
        ];

        $tempFiles = [];
        $sortedPayload = $payloadOrders->sortBy('position')->values();

        foreach ($sortedPayload as $orderItem) {
            $orderSn = $orderItem['order_sn'];
            $storeId = $orderItem['store_id'];
            
            $dummyOrder = $dummyOrders->get($orderSn);
            
            if (!$dummyOrder) {
                $results['failed_count']++;
                $results['failed_orders'][] = [
                    'order_sn' => $orderSn,
                    'store_name' => "Store ID: $storeId",
                    'reason' => 'Dummy order not found',
                ];
                continue;
            }
            
            $storeName = $dummyOrder['store']['name'] ?? "Store ID: $storeId";
            
            // Skenario override
            $simulatedResult = $dummyOrder['dummy_print_result'];
            if ($scenario === 'all_success') {
                $simulatedResult = 'success';
            } elseif ($scenario === 'all_failed') {
                $simulatedResult = 'document_not_ready';
            }

            if ($simulatedResult !== 'success') {
                $results['failed_count']++;
                $results['failed_orders'][] = [
                    'order_sn' => $orderSn,
                    'store_name' => $storeName,
                    'reason' => "Simulated failure: $simulatedResult",
                ];
            } else {
                // Generate a dummy PDF page
                $pdfPath = storage_path("app/tmp_dummy_{$orderSn}.pdf");
                $this->generateDummyPdf($pdfPath, $storeName, $orderSn, $dummyOrder['shipping_carrier']);
                $tempFiles[] = $pdfPath;
                $results['success_count']++;
            }
        }

        // Merge PDFs if there are any successes
        if (count($tempFiles) > 0) {
            $mergedPath = public_path("tmp/bulk_print_{$uuid}.pdf");
            $this->mergePdfs($tempFiles, $mergedPath);
            
            // Clean up temporary files
            foreach ($tempFiles as $file) {
                if (file_exists($file)) @unlink($file);
            }
        }

        Cache::put('bulk_print_' . $uuid, $results, now()->addMinutes(10));

        return response()->json([
            'uuid' => $uuid,
        ]);
    }

    private function generateDummyPdf($path, $storeName, $orderSn, $type)
    {
        // Require FPDF. Alternatively we can generate a basic valid PDF string manually 
        // to avoid dependency issues if fpdf is not directly loaded, but FPDI uses FPDF.
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage();
        $pdf->SetFont('Helvetica', 'B', 16);
        $pdf->Cell(0, 10, 'DUMMY LABEL', 0, 1, 'C');
        
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Store: ' . $storeName, 0, 1);
        $pdf->Cell(0, 10, 'Order: ' . $orderSn, 0, 1);
        $pdf->Cell(0, 10, 'Type: ' . $type, 0, 1);
        
        $pdf->Output($path, 'F');
    }

    private function mergePdfs(array $files, string $outputPath)
    {
        $pdf = new \setasign\Fpdi\Fpdi();
        
        foreach ($files as $file) {
            $pageCount = $pdf->setSourceFile($file);
            for ($i = 1; $i <= $pageCount; $i++) {
                $templateId = $pdf->importPage($i);
                $size = $pdf->getTemplateSize($templateId);
                
                $pdf->AddPage($size['orientation'], [$size['width'], $size['height']]);
                $pdf->useTemplate($templateId);
            }
        }
        
        $pdf->Output($outputPath, 'F');
    }
}
