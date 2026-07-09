<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$store = \App\Models\Store::find(4);
$order = \App\Models\MarketplaceOrder::where('channel_order_id', '260709908SV5CN')->first();
$driver = app(\App\Services\Channels\ChannelManager::class)->driver($store);
$payload = ['order_sn' => '260709908SV5CN', 'package_number' => 'OFG237309534223631'];
$res = $driver->getShippingDocument($store, [$payload]);

if (isset($res['error']) && $res['error'] === 'invalid_response') {
    $content = $res['message'];
    if (str_starts_with($content, '%PDF')) {
        
        $overlayService = new \App\Services\ShippingLabelOverlayService();
        $modifiedContent = $overlayService->overlayPdfContent($content);
        
        if ($modifiedContent === $content) {
            echo "FAILED: Result is identical. Let's trace why...\n";
            $isBrandingEnabled = \App\Models\SystemSetting::get('marketplace_print_branding', '1');
            echo "Branding setting is: " . $isBrandingEnabled . " (Type: " . gettype($isBrandingEnabled) . ")\n";
            
            // Re-run manually to catch exception
            $tmpFile = tempnam(sys_get_temp_dir(), 'resi_in_');
            file_put_contents($tmpFile, $content);
            $qpdfPath = exec('which qpdf');
            if ($qpdfPath) {
                $uncompressedFile = tempnam(sys_get_temp_dir(), 'resi_uncomp_');
                exec(sprintf("%s --empty --pages %s 1-z -- %s 2>&1", escapeshellarg($qpdfPath), escapeshellarg($tmpFile), escapeshellarg($uncompressedFile)), $output, $returnVar);
                if ($returnVar === 0 && file_exists($uncompressedFile)) {
                    $tmpFile = $uncompressedFile;
                } else {
                    echo "qpdf execution failed with code $returnVar\n";
                    print_r($output);
                }
            }
            
            $pdf = new \setasign\Fpdi\Fpdi();
            try {
                $pageCount = $pdf->setSourceFile($tmpFile);
                echo "FPDI Loaded successfully! So why did it fail in the service?\n";
            } catch (\Exception $e) {
                echo "FPDI EXCEPTION: " . $e->getMessage() . "\n";
            }
        } else {
            echo "SUCCESS: Modifed content is different!\n";
        }
    }
}
