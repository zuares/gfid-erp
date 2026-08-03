<?php

namespace App\Services\Marketplace\Shopee;

use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportShopeeIncomeService
{
    /**
     * Import income & update invoices.
     * Match by store_id + channel + channel_order_no using "No. Pesanan"
     */
    public function run(
        UploadedFile $file,
        int $storeId,
        string $channel = 'shopee',
        string $onMissing = 'skip', // skip|stop
        bool $dryRun = true
    ): array {
        [$rows, $meta] = $this->readIncomeRows($file->getRealPath());

        $feeCols = [
            'Biaya Komisi AMS',
            'Biaya Administrasi',
            'Biaya Layanan',
            'Biaya Proses Pesanan',
            'Premi',
            'Biaya Asuransi Pengiriman',
            'Biaya Program Hemat Biaya Kirim',
            'Biaya Transaksi',
            'Biaya Kampanye',
            'Biaya Affiliate',
            'Affiliate',
            'Bea Masuk, PPN & PPh',
        ];

        $matched = 0;
        $missing = 0;
        $preview = [];

        foreach ($rows as $r) {
            $orderNo = trim((string) ($r['No. Pesanan'] ?? ''));
            if ($orderNo === '') {
                continue;
            }

            $releasedAt = $r['Tanggal Dana Dilepaskan'] ?? null;
            $releasedAtDt = $releasedAt instanceof Carbon ? $releasedAt : $this->parseDateTime($releasedAt);

            $platformFee = $this->sumFees($r, $feeCols);
            $refundTotal = (float) ($r['Jumlah Pengembalian Dana ke Pembeli'] ?? 0);
            $netPayout = (float) ($r['Total Penghasilan'] ?? 0);

            $inv = SalesInvoice::query()
                ->where('store_id', $storeId)
                ->where('channel', $channel)
                ->where('channel_order_no', $orderNo)
                ->first();

            if (!$inv) {
                $missing++;
                if ($onMissing === 'stop') {
                    throw new \RuntimeException("Invoice tidak ketemu untuk No. Pesanan: {$orderNo}");
                }
                if ($dryRun) {
                    $preview[] = $this->previewRow($orderNo, false, $releasedAtDt, $platformFee, $refundTotal, $netPayout);
                }
                continue;
            }

            if ($dryRun) {
                $preview[] = $this->previewRow($orderNo, true, $releasedAtDt, $platformFee, $refundTotal, $netPayout);
                continue;
            }

            $inv->released_at = $releasedAtDt?->format('Y-m-d H:i:s');
            $inv->platform_fee_total = $platformFee;
            $inv->refund_total = $refundTotal;
            $inv->net_payout_actual = $netPayout;
            $inv->save();

            $matched++;
        }

        return [
            'meta' => $meta,
            'rows' => count($rows),
            'matched' => $matched,
            'missing_invoice' => $missing,
            'preview' => array_slice($preview, 0, 50),
        ];
    }

    private function previewRow(string $orderNo, bool $match, ?Carbon $releasedAt, float $platformFee, float $refund, float $net): array
    {
        return [
            'order_no' => $orderNo,
            'match' => $match ? 'MATCH' : 'MISSING_INVOICE',
            'released_at' => $releasedAt?->format('Y-m-d H:i:s') ?? '-',
            'platform_fee' => number_format($platformFee, 2, '.', ''),
            'refund' => number_format($refund, 2, '.', ''),
            'net_payout' => number_format($net, 2, '.', ''),
        ];
    }

    /**
     * Read Income sheet WITHOUT evaluating formulas (prevent formula errors).
     * Uses "No. Pesanan" as key.
     *
     * @return array{0: array<int,array<string,mixed>>, 1: array<string,mixed>}
     */
    private function readIncomeRows(string $path): array
    {
        /** @var IReader $reader */
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getSheetByName('Income') ?? $spreadsheet->getActiveSheet();

        // calculateFormulas = false
        $arr = $sheet->toArray(null, false, true, false);

        // find header row containing "No. Pesanan"
        $headerRowIndex = null;
        foreach ($arr as $i => $row) {
            foreach ($row as $v) {
                if ($this->normHeader($v) === $this->normHeader('No. Pesanan')) {
                    $headerRowIndex = $i;
                    break 2;
                }
            }
        }
        if ($headerRowIndex === null) {
            return [[], ['headers' => [], 'resolved' => []]];
        }

        $headers = array_map(fn($v) => trim((string) $v), $arr[$headerRowIndex]);

        $idx = [];
        $idxNorm = [];
        foreach ($headers as $c => $name) {
            if ($name === '') {
                continue;
            }

            $idx[$name] = $c;
            $idxNorm[$this->normHeader($name)] = $c;
        }

        $colOrder = $this->findCol($idx, $idxNorm, ['No. Pesanan'], mustContain: ['pesanan']);
        $colReleased = $this->findCol($idx, $idxNorm, ['Tanggal Dana Dilepaskan'], mustContain: ['dana', 'dilepas']);
        $colNet = $this->findCol($idx, $idxNorm, ['Total Penghasilan'], mustContain: ['total', 'penghasilan']);

        if ($colOrder === null || $colReleased === null || $colNet === null) {
            return [[], ['headers' => $headers, 'resolved' => []]];
        }

        $moneyCols = [
            'Total Penghasilan',
            'Jumlah Pengembalian Dana ke Pembeli',
            'Biaya Komisi AMS',
            'Biaya Administrasi',
            'Biaya Layanan',
            'Biaya Proses Pesanan',
            'Premi',
            'Biaya Asuransi Pengiriman',
            'Biaya Program Hemat Biaya Kirim',
            'Biaya Transaksi',
            'Biaya Kampanye',
            'Biaya Affiliate',
            'Affiliate',
            'Bea Masuk, PPN & PPh',
        ];

        $rows = [];
        for ($i = $headerRowIndex + 1; $i < count($arr); $i++) {
            $r = $arr[$i];
            if (!is_array($r)) {
                continue;
            }

            $orderNo = trim((string) ($r[$colOrder] ?? ''));
            if ($orderNo === '') {
                continue;
            }

            $rowOut = [];
            foreach ($idx as $name => $colIndex) {
                $rowOut[$name] = $r[$colIndex] ?? null;
            }

            // canonical
            $rowOut['No. Pesanan'] = $orderNo;

            $rowOut['Tanggal Dana Dilepaskan'] = $this->parseDateTime(
                $rowOut['Tanggal Dana Dilepaskan'] ?? ($r[$colReleased] ?? null)
            );

            foreach ($moneyCols as $mc) {
                if (array_key_exists($mc, $rowOut)) {
                    $rowOut[$mc] = $this->toNumber($rowOut[$mc]);
                }
            }

            $rows[] = $rowOut;
        }

        $meta = [
            'headers' => $headers,
            'resolved' => [
                'No. Pesanan' => $headers[$colOrder] ?? 'col#' . $colOrder,
                'Tanggal Dana Dilepaskan' => $headers[$colReleased] ?? 'col#' . $colReleased,
                'Total Penghasilan' => $headers[$colNet] ?? 'col#' . $colNet,
            ],
        ];

        return [$rows, $meta];
    }

    private function normHeader($v): string
    {
        $s = trim((string) $v);
        $s = str_replace(["\r", "\n", "\t"], ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return mb_strtolower($s);
    }

    private function findCol(array $idx, array $idxNorm, array $candidates, array $mustContain = []): ?int
    {
        foreach ($candidates as $c) {
            if (isset($idx[$c])) {
                return $idx[$c];
            }

            $cn = $this->normHeader($c);
            if (isset($idxNorm[$cn])) {
                return $idxNorm[$cn];
            }

        }

        if (!empty($mustContain)) {
            foreach ($idxNorm as $hn => $col) {
                $ok = true;
                foreach ($mustContain as $token) {
                    if (!str_contains($hn, mb_strtolower($token))) {
                        $ok = false;
                        break;
                    }
                }
                if ($ok) {
                    return $col;
                }

            }
        }
        return null;
    }

    private function sumFees(array $r, array $feeCols): float
    {
        $sum = 0.0;
        $normalized = [];
        foreach ($r as $key => $value) {
            $normalized[$this->normHeader((string) $key)] = $value;
        }
        foreach ($feeCols as $c) {
            $v = $normalized[$this->normHeader((string) $c)] ?? ($r[$c] ?? 0);
            $n = is_numeric($v) ? (float) $v : (float) $this->toNumber($v);
            $sum += $n < 0 ? abs($n) : $n;
        }
        return round($sum, 2);
    }

    private function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $s = (string) $value;
        $s = str_replace(["Rp", "rp", " ", "\u{00A0}"], '', $s);
        $s = str_replace(['.', ','], ['', '.'], $s);
        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function parseDateTime($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
        }

        if ($value instanceof Carbon) {
            return $value;
        }

        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($value);
                return Carbon::instance($dt);
            } catch (\Throwable) {
                return null;
            }
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        try {
            return Carbon::parse($s);
        } catch (\Throwable) {
            return null;
        }
    }
}
