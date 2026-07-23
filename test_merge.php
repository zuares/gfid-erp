<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$pdf = new \setasign\Fpdi\Fpdi();

$mockPdf = storage_path('app/mock/shopee_shipping_document.json'); // maybe they have a mock pdf?
echo "Check if there's any sample pdf to test in storage/app...\n";
