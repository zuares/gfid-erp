<?php
require 'vendor/autoload.php';

$pdf = new \setasign\Fpdi\Fpdi();
$pdf->AddPage('P', [100, 150]);
$pdf->SetFont('Arial', 'B', 14);
$pdf->Cell(0, 10, 'MARKETPLACE AWB MOCKUP', 0, 1, 'C');
$pdf->SetFont('Arial', '', 10);
$pdf->Cell(0, 5, 'No. Pesanan: 260709908SV5CN', 0, 1, 'C');

$pdf->SetFillColor(0, 0, 0);
for($i = 0; $i < 60; $i++) {
    $w = rand(1, 3);
    $pdf->Rect(15 + ($i * 1.2), 30, $w * 0.5, 15, 'F');
}

$pdf->SetY(50);
$pdf->Cell(0, 10, '==================================================', 0, 1, 'C');
$rawPdf = $pdf->Output('S');
file_put_contents('test_fake.pdf', $rawPdf);

$overlayService = new \App\Services\ShippingLabelOverlayService();
$finalPdf = $overlayService->overlayPdfContent($rawPdf, ['marketplace_footer_greeting' => 'Test', 'marketplace_footer_alignment' => 'C']);
file_put_contents('test_final.pdf', $finalPdf);
echo "Done\n";
