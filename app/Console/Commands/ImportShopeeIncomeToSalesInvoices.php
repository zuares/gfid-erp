<?php

namespace App\Console\Commands;

use App\Models\SalesInvoice;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Reader\IReader;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportShopeeIncomeToSalesInvoices extends Command
{
    protected $signature = 'shopee:import-income
        {--file= : Path ke file Income .xlsx (wajib)}
        {--store_id= : store_id (wajib)}
        {--channel=shopee : channel label (default shopee)}
        {--on-missing=skip : skip|stop (invoice tidak ketemu)}
        {--dry-run=0 : 1 untuk test tanpa write DB}
        {--debug-cols=0 : 1 untuk print kolom header terdeteksi}
        {--limit=0 : Batasi jumlah row diproses (0=all) untuk test}
    ';

    protected $description = 'Import Shopee Income (Dana Dilepaskan) untuk update sales_invoices: released_at, platform_fee_total, refund_total, net_payout_actual. Match by store_id+channel+channel_order_no using Income column "No. Pesanan" (FULL).';

    public function handle(): int
    {
        $file = (string) $this->option('file');
        $storeId = (int) $this->option('store_id');
        $channel = (string) $this->option('channel');
        $onMissing = strtolower((string) $this->option('on-missing'));
        $dryRun = ((int) $this->option('dry-run')) === 1;
        $debugCols = ((int) $this->option('debug-cols')) === 1;
        $limit = max(0, (int) $this->option('limit'));

        if (!$file || !is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }
        if ($storeId <= 0) {
            $this->error("store_id wajib diisi.");
            return 1;
        }
        if (!in_array($onMissing, ['skip', 'stop'], true)) {
            $this->error("on-missing harus skip|stop");
            return 1;
        }

        $this->info("Reading: {$file} (sheet: Income)");

        [$rows, $meta] = $this->readIncomeRows($file);

        if ($debugCols) {
            $this->line("Detected headers (" . count($meta['headers'] ?? []) . "):");
            foreach (($meta['headers'] ?? []) as $h) {
                $this->line(" - {$h}");
            }
            $this->line("Resolved:");
            foreach (($meta['resolved'] ?? []) as $k => $v) {
                $this->line(" - {$k}: {$v}");
            }
        }

        if (count($rows) === 0) {
            $this->error("Tidak ada rows income terbaca.");
            return 1;
        }

        if ($limit > 0) {
            $rows = array_slice($rows, 0, $limit);
        }

        $this->info("Rows income: " . count($rows));
        $this->info("Mode: " . ($dryRun ? "DRY-RUN" : "WRITE"));

        // fee columns yang kita jumlahkan jadi platform_fee_total
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
        $missingInv = 0;

        if ($dryRun) {
            $preview = [];
            foreach (array_slice($rows, 0, 25) as $r) {
                $orderKey = trim((string) ($r['No. Pesanan'] ?? ''));
                $releasedAt = $r['Tanggal Dana Dilepaskan'] ?? null;

                $platformFee = $this->sumFees($r, $feeCols);
                $refundTotal = (float) ($r['Jumlah Pengembalian Dana ke Pembeli'] ?? 0);
                $netPayout = (float) ($r['Total Penghasilan'] ?? 0);

                $inv = SalesInvoice::query()
                    ->where('store_id', $storeId)
                    ->where('channel', $channel)
                    ->where('channel_order_no', $orderKey)
                    ->first();

                $status = $inv ? 'MATCH' : 'MISSING_INVOICE';

                $preview[] = [
                    $orderKey ?: '-',
                    $status,
                    $releasedAt instanceof Carbon ? $releasedAt->format('Y-m-d H:i:s') : (string) $releasedAt,
                    number_format($platformFee, 2, '.', ''),
                    number_format($refundTotal, 2, '.', ''),
                    number_format($netPayout, 2, '.', ''),
                ];
            }

            $this->table(['order_no', 'match', 'released_at', 'platform_fee', 'refund', 'net_payout'], $preview);
            $this->line("Catatan: DRY-RUN preview max 25 baris.");
            $this->line("Tips: pakai --debug-cols=1 kalau header tidak kebaca, pakai --limit=10 untuk test cepat.");
            return 0;
        }

        DB::transaction(function () use (
            $rows,
            $storeId,
            $channel,
            $onMissing,
            $feeCols,
            &$matched,
            &$missingInv
        ) {
            foreach ($rows as $r) {
                $orderKey = trim((string) ($r['No. Pesanan'] ?? ''));
                if ($orderKey === '') {
                    continue;
                }

                /** @var SalesInvoice|null $inv */
                $inv = SalesInvoice::query()
                    ->where('store_id', $storeId)
                    ->where('channel', $channel)
                    ->where('channel_order_no', $orderKey)
                    ->first();

                if (!$inv) {
                    $missingInv++;
                    if ($onMissing === 'stop') {
                        throw new \RuntimeException("Invoice tidak ketemu untuk order_no: {$orderKey}");
                    }
                    continue;
                }

                $platformFee = $this->sumFees($r, $feeCols);
                $refundTotal = (float) ($r['Jumlah Pengembalian Dana ke Pembeli'] ?? 0);
                $netPayout = (float) ($r['Total Penghasilan'] ?? 0);

                $releasedAt = $r['Tanggal Dana Dilepaskan'] ?? null;
                $releasedAtDt = $releasedAt instanceof Carbon ? $releasedAt : $this->parseDateTime($releasedAt);

                $inv->released_at = $releasedAtDt?->format('Y-m-d H:i:s');
                $inv->platform_fee_total = $platformFee;
                $inv->refund_total = $refundTotal;
                $inv->net_payout_actual = $netPayout;
                $inv->save();

                $matched++;
            }
        });

        $this->info("Done. matched={$matched}, missing_invoice={$missingInv}");
        return 0;
    }

    /**
     * Read Income sheet WITHOUT evaluating formulas (avoid CalculationException).
     * Resolve header row dynamically (find the row containing "No. Pesanan").
     *
     * @return array{0: array<int,array<string,mixed>>, 1: array<string,mixed>}
     */
    private function readIncomeRows(string $file): array
    {
        /** @var IReader $reader */
        $reader = IOFactory::createReaderForFile($file);
        $reader->setReadDataOnly(true);
        $reader->setReadEmptyCells(false);

        $spreadsheet = $reader->load($file);
        $sheet = $spreadsheet->getSheetByName('Income') ?? $spreadsheet->getActiveSheet();

        // calculateFormulas=false -> don't evaluate formulas
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

        $rawHeader = $arr[$headerRowIndex];
        $headers = array_map(fn($v) => trim((string) $v), $rawHeader);

        $idx = [];
        $idxNorm = [];

        foreach ($headers as $c => $name) {
            if ($name === '') {
                continue;
            }
            $idx[$name] = $c;
            $idxNorm[$this->normHeader($name)] = $c;
        }

        // resolve MUST-HAVE columns
        $colOrder = $this->findCol($idx, $idxNorm, ['No. Pesanan'], mustContain: ['pesanan']);
        $colReleased = $this->findCol($idx, $idxNorm, ['Tanggal Dana Dilepaskan'], mustContain: ['dana', 'dilepas']);
        $colNet = $this->findCol($idx, $idxNorm, ['Total Penghasilan'], mustContain: ['total', 'penghasilan']);

        if ($colOrder === null || $colReleased === null || $colNet === null) {
            return [[], ['headers' => $headers, 'resolved' => []]];
        }

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

            // output all columns with original header names
            $rowOut = [];
            foreach ($idx as $name => $colIndex) {
                $rowOut[$name] = $r[$colIndex] ?? null;
            }

            // ensure canonical keys exist
            $rowOut['No. Pesanan'] = $orderNo;

            // parse released date
            $rowOut['Tanggal Dana Dilepaskan'] = $this->parseDateTime(
                $rowOut['Tanggal Dana Dilepaskan'] ?? ($r[$colReleased] ?? null)
            );

            // normalize numeric ONLY for money/fee columns (don't touch No. Pesanan)
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
                    $token = mb_strtolower($token);
                    if (!str_contains($hn, $token)) {
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
            // biasanya fee negatif => ambil magnitude
            $sum += $n < 0 ? abs($n) : $n;
        }
        return round($sum, 2);
    }

    private function toNumber($value): float
    {
        if ($value === null) {
            return 0.0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }

        $s = (string) $value;
        $s = str_replace(["Rp", "rp", " ", "\u{00A0}"], '', $s);

        // format Indo: 1.234,56 => 1234.56
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

        // Excel serial date
        if (is_numeric($value)) {
            try {
                $dt = ExcelDate::excelToDateTimeObject($value);
                return Carbon::instance($dt);
            } catch (\Throwable $e) {
                return null;
            }
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        try {
            return Carbon::parse($s);
        } catch (\Throwable $e) {
            return null;
        }
    }
}
