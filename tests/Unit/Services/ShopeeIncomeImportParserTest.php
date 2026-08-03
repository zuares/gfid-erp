<?php

namespace Tests\Unit\Services;

use App\Services\Marketplace\Income\Adapters\ShopeeIncomeAdapter;
use App\Services\Marketplace\Shopee\ImportShopeeIncomeService;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use ReflectionClass;
use Tests\TestCase;

class ShopeeIncomeImportParserTest extends TestCase
{
    public function test_shopee_income_adapter_mengakumulasi_biaya_affiliate_dan_asuransi(): void
    {
        $path = $this->makeIncomeWorkbook([
            'No. Pesanan',
            'Tanggal Dana Dilepaskan',
            'Total Penghasilan',
            'Jumlah Pengembalian Dana ke Pembeli',
            'Biaya Administrasi',
            'Biaya Layanan',
            'Premi',
            'Biaya Asuransi Pengiriman',
            'Biaya Affiliate',
            'Affiliate',
            'Biaya Transaksi',
        ], [
            'ORDER-123',
            '2026-07-28 10:00:00',
            100000,
            0,
            -1000,
            -2000,
            -3000,
            -4000,
            -5000,
            -6000,
            -7000,
        ]);

        try {
            $rows = app(ShopeeIncomeAdapter::class)->parse($path, 'income.xlsx');

            $this->assertCount(1, $rows);
            $this->assertSame('ORDER-123', $rows[0]['platform_order_id']);
            $this->assertSame(28000, $rows[0]['platform_fee_total']);
        } finally {
            @unlink($path);
        }
    }

    public function test_import_service_sum_fees_mengenali_alias_biaya_baru(): void
    {
        $service = new ImportShopeeIncomeService();
        $reflection = new ReflectionClass($service);
        $method = $reflection->getMethod('sumFees');
        $method->setAccessible(true);

        $row = [
            'biaya administrasi' => -1000,
            'Biaya Layanan' => -2000,
            'premi' => -3000,
            'Biaya ASURANSI Pengiriman' => -4000,
            'biaya affiliate' => -5000,
            'affiliate' => -6000,
            'Biaya Transaksi' => -7000,
        ];

        $feeCols = [
            'Biaya Administrasi',
            'Biaya Layanan',
            'Premi',
            'Biaya Asuransi Pengiriman',
            'Biaya Affiliate',
            'Affiliate',
            'Biaya Transaksi',
        ];

        $sum = $method->invoke($service, $row, $feeCols);

        $this->assertSame(28000.0, $sum);
    }

    private function makeIncomeWorkbook(array $headers, array $row): string
    {
        $spreadsheet = new Spreadsheet();
        $sheet = $spreadsheet->getActiveSheet();
        $sheet->setTitle('Income');

        foreach ($headers as $index => $header) {
            $sheet->setCellValue($this->columnLetter($index + 1) . '1', $header);
        }

        foreach ($row as $index => $value) {
            $sheet->setCellValue($this->columnLetter($index + 1) . '2', $value);
        }

        $path = tempnam(sys_get_temp_dir(), 'shp_income_') . '.xlsx';
        $writer = new Xlsx($spreadsheet);
        $writer->save($path);
        $spreadsheet->disconnectWorksheets();

        return $path;
    }

    private function columnLetter(int $index): string
    {
        $letter = '';
        while ($index > 0) {
            $index--;
            $letter = chr(65 + ($index % 26)) . $letter;
            $index = intdiv($index, 26);
        }

        return $letter;
    }
}
