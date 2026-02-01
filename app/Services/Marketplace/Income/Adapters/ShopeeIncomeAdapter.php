<?php

namespace App\Services\Marketplace\Income\Adapters;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ShopeeIncomeAdapter implements MpIncomeAdapterInterface
{
    public function channel(): string
    {
        return 'shopee';
    }

    public function parse(string $path, string $sourceFile): array
    {
        $sp = IOFactory::load($path);

        // File kamu punya sheet: Summary, Income, Service Fee Details, Order Processing Fee
        $ws = $sp->getSheetByName('Income') ?: $sp->getActiveSheet();

        // IMPORTANT: calculateFormulas = false (hindari error Income!H5 formula)
        $rows = $this->safeToArray($ws);
        if (count($rows) < 2) {
            return [];
        }

        // Cari baris header (biasanya row 6 di file kamu)
        [$headerRowIndex, $headerRow] = $this->detectHeaderRow($rows);
        if (!$headerRowIndex) {
            return [];
        }

        $map = $this->buildHeaderMap($headerRow);

        // Kita akan auto-detect semua kolom yang berawalan "Biaya"
        $feeCols = $this->detectFeeColumns($headerRow);

        $out = [];

        for ($i = $headerRowIndex + 1; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null;
            if (!$r || $this->rowEmpty($r)) {
                continue;
            }

            $orderNo = $this->v($r, $map, [
                'no. pesanan', 'no pesanan', 'nomor pesanan', 'order id', 'order no',
            ]);
            if (!$orderNo) {
                continue;
            }

            // Released time/date
            $releasedAt = $this->dt(
                $this->v($r, $map, ['tanggal dana dilepaskan', 'waktu dana dilepaskan', 'released at', 'released time'])
            );

            // Net payout
            // Di file kamu: "Total Penghasilan" sudah rupiah penuh (contoh 63336)
            $net = $this->moneyIdr(
                $this->v($r, $map, ['total penghasilan', 'net payout', 'payout', 'settlement amount'])
            );

            // Refund: "Pengembalian Dana ke Pembeli" (bisa 0, atau negatif/positif tergantung report)
            $refundRaw = $this->moneyIdr(
                $this->v($r, $map, ['pengembalian dana ke pembeli', 'refund', 'pengembalian', 'refund amount'])
            );
            $refund = abs($refundRaw);

            // Fee total: jumlah semua kolom yang berawalan "Biaya ..."
            // Di report Shopee, biaya biasanya angka NEGATIF, jadi kita simpan POSITIF (abs)
            $feeSum = 0;
            foreach ($feeCols as $col) {
                $feeSum += $this->moneyIdr($r[$col] ?? null);
            }
            $fee = abs($feeSum);

            $key = (string) $orderNo;

            if (!isset($out[$key])) {
                $out[$key] = [
                    'platform_order_id' => $key,
                    'released_at' => $releasedAt,
                    'platform_fee_total' => 0,
                    'refund_total' => 0,
                    'net_payout_actual' => 0,
                    'raw' => [],
                ];
            }

            // Kalau ada beberapa baris per order, akumulasi (aman)
            $out[$key]['platform_fee_total'] += $fee;
            $out[$key]['refund_total'] += $refund;
            $out[$key]['net_payout_actual'] += $net;

            // Simpan released_at paling baru
            if ($releasedAt && (!$out[$key]['released_at'] || $releasedAt > $out[$key]['released_at'])) {
                $out[$key]['released_at'] = $releasedAt;
            }

            // Raw snapshot per baris (audit)
            $out[$key]['raw'][] = $this->rawRow($r, $headerRow);
        }

        return array_values($out);
    }

    /* ============================================================
     * Helpers: Safe reading (no formula evaluation)
     * ============================================================
     */
    private function safeToArray($ws): array
    {
        // Worksheet::toArray(nullValue, calculateFormulas, formatData, returnCellRef)
        // calculateFormulas = false => avoid PhpSpreadsheet calculation engine crash
        return $ws->toArray(null, false, true, true);
    }

    /* ============================================================
     * Header + column detection
     * ============================================================
     */
    private function detectHeaderRow(array $rows): array
    {
        // Scan baris 1..40 untuk menemukan header tabel yang mengandung:
        // - "No. Pesanan" / "Order"
        // - "Tanggal Dana" / "Total Penghasilan" / "Biaya"
        $max = min(40, count($rows));
        for ($i = 1; $i <= $max; $i++) {
            $r = $rows[$i] ?? [];
            if (!$r) {
                continue;
            }

            $line = strtolower(implode(' | ', array_map(fn($v) => trim((string) $v), $r)));

            $hasOrder = str_contains($line, 'pesanan') || str_contains($line, 'order');
            $hasIncome = str_contains($line, 'dana') || str_contains($line, 'penghasilan') || str_contains($line, 'biaya')
            || str_contains($line, 'refund') || str_contains($line, 'pengembalian');

            if ($hasOrder && $hasIncome) {
                return [$i, $r];
            }
        }

        return [0, []];
    }

    private function detectFeeColumns(array $headerRow): array
    {
        $cols = [];
        foreach ($headerRow as $col => $name) {
            $h = trim((string) $name);
            if ($h === '') {
                continue;
            }

            // Kolom biaya di file kamu banyak yang berawalan "Biaya ..."
            // mis: Biaya Administrasi, Biaya Layanan, Biaya Proses Pesanan, dll.
            $hn = strtolower($h);
            if (str_starts_with($hn, 'biaya')) {
                $cols[] = $col;
            }
        }
        return $cols;
    }

    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $col => $name) {
            $name = trim((string) $name);
            if ($name === '') {
                continue;
            }

            $map[$this->norm($name)] = $col;
        }
        return $map;
    }

    private function v(array $row, array $map, array $candidates)
    {
        foreach ($candidates as $c) {
            $k = $this->norm($c);
            if (isset($map[$k])) {
                $val = $row[$map[$k]] ?? null;
                if ($val !== null && $val !== '') {
                    return $val;
                }

            }
        }
        return null;
    }

    private function norm(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/\s+/', ' ', $s);
        return $s;
    }

    private function rowEmpty(array $r): bool
    {
        foreach ($r as $v) {
            if (trim((string) $v) !== '') {
                return false;
            }

        }
        return true;
    }

    /* ============================================================
     * Money/date parsing
     * ============================================================
     */
    private function moneyIdr($v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }

        // numeric cell (already number)
        if (is_numeric($v)) {
            return (int) round((float) $v);
        }

        $t = trim((string) $v);
        if ($t === '') {
            return 0;
        }

        // parentheses negative: (1.234) => -1234
        $neg = false;
        if (str_starts_with($t, '(') && str_ends_with($t, ')')) {
            $neg = true;
            $t = substr($t, 1, -1);
        }

        // remove currency text and spaces
        $t = str_replace(['Rp', 'rp', ' '], '', $t);

        // keep digits, dot, comma, minus
        $t = preg_replace('/[^0-9\.\,\-]/', '', $t);
        if ($t === '' || $t === '-') {
            return 0;
        }

        // Shopee income umumnya integer, kadang pakai pemisah ribuan:
        // "1.234.567" => 1234567
        // kalau ada koma desimal, kita drop (umumnya tidak ada di ID)
        $t = str_replace(',', '', $t);
        $t = str_replace('.', '', $t);

        $n = is_numeric($t) ? (int) $t : 0;
        return $neg ? -abs($n) : $n;
    }

    private function dt($v): ?string
    {
        if (!$v) {
            return null;
        }

        if ($v instanceof \DateTimeInterface) {
            return $v->format('Y-m-d H:i:s');
        }

        $s = trim((string) $v);
        if ($s === '') {
            return null;
        }

        // Tanggal di file kamu: "2026-01-26" (kadang tanpa jam)
        // Normalize
        $s = str_replace('/', '-', $s);

        $ts = strtotime($s);
        if (!$ts) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function rawRow(array $row, array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $col => $h) {
            $h = trim((string) $h);
            if ($h === '') {
                continue;
            }

            $out[$h] = $row[$col] ?? null;
        }
        return $out;
    }
}
