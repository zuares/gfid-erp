<?php

namespace Tests\Unit;

use App\Services\Marketplace\Crm\MarketplaceCrmImportService;
use PHPUnit\Framework\TestCase;
use ReflectionMethod;

class MarketplaceCrmImportServiceTest extends TestCase
{
    public function test_it_parses_tiktok_full_export_and_skips_description_row(): void
    {
        $headers = [
            'Order ID', 'Order Status', 'SKU ID', 'Seller SKU', 'Product Name', 'Variation',
            'Quantity', 'Sku Quantity of return', 'SKU Unit Original Price',
            'SKU Subtotal After Discount', 'Shipping Fee After Discount', 'Order Amount',
            'Created Time', 'Paid Time', 'Delivered Time', 'Tracking ID',
            'Shipping Provider Name', 'Recipient', 'Phone #', 'Province', 'Regency and City',
            'Detail Address', 'Payment Method', 'Purchase Channel', 'Order Channel',
        ];
        $description = array_fill(0, count($headers), 'Export field description.');
        $description[0] = 'Platform unique order ID.';
        $lineOne = [
            'TIKTOK-ORDER-1', 'Selesai', 'PLATFORM-SKU-1', 'SELLER-SKU-1', 'Jacket', 'Black, M',
            2, 0, 100000, 150000, 20000, 210000, '13/09/2026 22:03:06',
            '13/09/2026 22:04:00', '14/09/2026 20:00:00', 'TRACK-1', 'J&T Express',
            'M******* R*****', '(+62)878******64', 'Jawa Barat', 'Kota Bekasi', '********',
            'Credit Card', 'Tokopedia', 'Product cards',
        ];
        $lineTwo = [
            'TIKTOK-ORDER-1', 'Selesai', 'PLATFORM-SKU-2', 'SELLER-SKU-2', 'Pants', 'Black, L',
            1, 0, 50000, 40000, 20000, 210000, '13/09/2026 22:03:06',
            '13/09/2026 22:04:00', '14/09/2026 20:00:00', 'TRACK-1', 'J&T Express',
            'M******* R*****', '(+62)878******64', 'Jawa Barat', 'Kota Bekasi', '********',
            'Credit Card', 'Tokopedia', 'Product cards',
        ];
        $lineThree = array_replace($lineOne, [0 => 'TIKTOK-ORDER-2', 23 => 'TikTok']);

        $temporaryPath = tempnam(sys_get_temp_dir(), 'crm-tiktok-import-');
        $path = $temporaryPath.'.csv';
        rename($temporaryPath, $path);
        $handle = fopen($path, 'wb');
        foreach ([$headers, $description, $lineOne, $lineTwo, $lineThree] as $row) {
            fputcsv($handle, $row, ',', '"', '');
        }
        fclose($handle);

        try {
            $service = new MarketplaceCrmImportService();
            $parse = new ReflectionMethod($service, 'parse');
            $result = $parse->invoke($service, $path, 9, 'tiktok-export.csv');
        } finally {
            @unlink($path);
        }

        $this->assertSame('tiktok', $result['source_channel']);
        $this->assertSame(3, $result['rows']);
        $this->assertCount(2, $result['orders']);
        $this->assertSame(3, $result['items']);
        $this->assertSame('completed', $result['orders'][0]['status']);
        $this->assertSame('2026-09-13 22:03:06', $result['orders'][0]['ordered_at']);
        $this->assertSame('2026-09-14 20:00:00', $result['orders'][0]['completed_at']);
        $this->assertSame(210000, $result['orders'][0]['total_paid']);
        $this->assertSame(20000, $result['orders'][0]['shipping_fee']);
        $this->assertSame('Tokopedia', $result['orders'][0]['purchase_channel']);
        $this->assertSame('TikTok', $result['orders'][1]['purchase_channel']);
        $this->assertNull($result['orders'][0]['buyer_phone']);
        $this->assertNull($result['orders'][0]['buyer_name']);
        $this->assertSame(50000, $result['orders'][0]['items'][0]['line_discount']);
    }
}
