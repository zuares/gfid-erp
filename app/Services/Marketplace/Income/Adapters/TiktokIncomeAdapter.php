<?php

namespace App\Services\Marketplace\Income\Adapters;

use PhpOffice\PhpSpreadsheet\IOFactory;

class TiktokIncomeAdapter implements MpIncomeAdapterInterface
{
    public function channel(): string
    {
        return 'tiktok';
    }

    public function parse(string $path, string $sourceFile): array
    {
        $sp = IOFactory::load($path);
        $ws = $sp->getActiveSheet();

        // Konsisten dengan Shopee: no formula eval
        $rows = $this->safeToArray($ws);
        if (count($rows) < 2) {
            return [];
        }

        // TikTok biasanya header di row 1, tapi kita tetap detect biar robust
        [$headerRowIndex, $headerRow] = $this->detectHeaderRow($rows);
        if (!$headerRowIndex) {
            return [];
        }

        $map = $this->buildHeaderMap($headerRow);

        $out = [];

        for ($i = $headerRowIndex + 1; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null;
            if (!$r || $this->rowEmpty($r)) {
                continue;
            }

            // TikTok: order id utama biasanya di "Order/adjustment ID"
            // Jika baris "Adjustment", kadang ada "Related order ID" yang menunjuk order utama
            $type = strtolower(trim((string) ($this->v($r, $map, ['type']) ?? '')));
            $orderId = $this->v($r, $map, ['order/adjustment id', 'order adjustment id', 'order id', 'platform order id', 'orderid']);
            $related = $this->v($r, $map, ['related order id', 'related orderid']);

            // Rule: kalau Adjustment dan related ada → pakai related sebagai platform_order_id (lebih Shopee-like)
            $platformOrderId = $orderId;
            if ($type !== '' && $type !== 'order' && $related) {
                $platformOrderId = $related;
            }

            if (!$platformOrderId) {
                continue;
            }

            $releasedAt = $this->dt(
                $this->v($r, $map, [
                    'order settled time',
                    'order settled date',
                    'settlement time',
                    'released time',
                    'release time',
                    'paid out time',
                    'released at',
                ])
            );

            // Net payout (uang masuk seller)
            $net = $this->moneyIdr(
                $this->v($r, $map, [
                    'total settlement amount',
                    'total settlement',
                    'settlement amount',
                    'net payout',
                    'net amount',
                    'payout',
                    'seller received',
                ])
            );

            // Fee/refund di file TikTok kamu bernilai NEGATIF → simpan POSITIF (abs)
            $feeRaw = $this->moneyIdr(
                $this->v($r, $map, [
                    'total fees',
                    'platform fee',
                    'commission',
                    'service fee',
                    'transaction fee',
                    'payment fee',
                ])
            );
            $fee = abs($feeRaw);

            $refundRaw = $this->moneyIdr(
                $this->v($r, $map, [
                    'customer refund',
                    'refund',
                    'refund amount',
                    'refund total',
                ])
            );
            $refund = abs($refundRaw);

            $key = (string) $platformOrderId;

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

            // Akumulasi (kalau ada beberapa baris per order/adjustment)
            $out[$key]['platform_fee_total'] += $fee;
            $out[$key]['refund_total'] += $refund;
            $out[$key]['net_payout_actual'] += $net;

            // released_at paling baru
            if ($releasedAt && (!$out[$key]['released_at'] || $releasedAt > $out[$key]['released_at'])) {
                $out[$key]['released_at'] = $releasedAt;
            }

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
        return $ws->toArray(null, false, true, true);
    }

    /* ============================================================
     * Header detection (robust)
     * ============================================================
     */
    private function detectHeaderRow(array $rows): array
    {
        // Scan baris 1..20 untuk menemukan header yang mengandung minimal:
        // - order/adjustment id
        // - total settlement / fees
        $max = min(20, count($rows));
        for ($i = 1; $i <= $max; $i++) {
            $r = $rows[$i] ?? [];
            if (!$r) {
                continue;
            }

            $line = $this->norm(implode(' | ', array_map(fn($v) => trim((string) $v), $r)));

            $hasOrder = str_contains($line, 'order adjustment id') || str_contains($line, 'order id');
            $hasSettle = str_contains($line, 'total settlement amount') || str_contains($line, 'settlement');
            $hasFees = str_contains($line, 'total fees') || str_contains($line, 'fee');

            if ($hasOrder && ($hasSettle || $hasFees)) {
                return [$i, $r];
            }
        }

        return [0, []];
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
        // penting: buang simbol seperti "/" jadi spasi → "Order/adjustment ID" match
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
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
     * Money/date parsing (samain gaya dengan Shopee)
     * ============================================================
     */
    private function moneyIdr($v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }

        if (is_numeric($v)) {
            return (int) round((float) $v);
        }

        $t = trim((string) $v);
        if ($t === '') {
            return 0;
        }

        $neg = false;
        if (str_starts_with($t, '(') && str_ends_with($t, ')')) {
            $neg = true;
            $t = substr($t, 1, -1);
        }

        // remove currency labels/spaces
        $t = str_replace(['Rp', 'rp', 'IDR', 'idr', ' '], '', $t);

        // keep digits, dot, comma, minus
        $t = preg_replace('/[^0-9\.\,\-]/', '', $t);
        if ($t === '' || $t === '-') {
            return 0;
        }

        // TikTok biasanya numeric, tapi kalau ada pemisah ribuan → rapihin
        $t = str_replace(',', '', $t);
        // kalau ada format 1.234.567 → hapus dot
        // kalau TikTok suatu saat pakai desimal, ini akan “flatten” (ok untuk IDR integer)
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
