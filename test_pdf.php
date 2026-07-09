<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

try {
    $service = new \App\Services\ShippingLabelOverlayService();
    // Simulate getting settings
    $settings = \App\Models\SystemSetting::pluck('setting_value', 'setting_key')->toArray();
    
    // We need a dummy PDF.
    $pdfContent = file_get_contents(storage_path('app/public/dummy_label.pdf')); // Need to create one
    if(!$pdfContent) {
        $pdf = new \setasign\Fpdi\Fpdi();
        $pdf->AddPage('P', [100, 150]);
        $pdf->SetFont('Helvetica', '', 12);
        $pdf->Cell(0, 10, 'Original Label Page', 0, 1);
        $pdfContent = $pdf->Output('S');
    }
    
    $out = $service->overlayPdfContent($pdfContent, $settings);
    file_put_contents('/tmp/overlay_result.pdf', $out);
    echo "Success! Output size: " . strlen($out) . " bytes\n";
} catch (\Exception $e) {
    echo "Error: " . $e->getMessage() . "\n" . $e->getTraceAsString();
}
