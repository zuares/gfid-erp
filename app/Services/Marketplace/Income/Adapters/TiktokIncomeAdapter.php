<?php

namespace App\Services\Marketplace\Income\Adapters;

use PhpOffice\PhpSpreadsheet\IOFactory;

class TiktokIncomeAdapter implements MpIncomeAdapterInterface
{
    private array $lastStats = [];

    public function channel(): string
    {
        return 'tiktok';
    }

    /** Metadata parser untuk preview UI tanpa mengubah kontrak adapter lama. */
    public function lastStats(): array
    {
        return $this->lastStats;
    }

    public function parse(string $path, string $sourceFile): array
    {
        $sp = IOFactory::load($path);

        $this->lastStats = [
            'rows_seen' => 0,
            'order_rows' => 0,
            'adjustment_rows' => 0,
            'rows_skipped_no_order_id' => 0,
            'rows_skipped_unlinked_adjustment' => 0,
            'rows_skipped' => 0,
            'orders' => 0,
            'sheet_name' => null,
            'header_row' => 0,
            'error' => null,
        ];

        [$ws, $rows, $headerRowIndex, $headerRow] = $this->findIncomeWorksheet($sp);

        if (!$ws || !$headerRowIndex) {
            $this->lastStats['error'] = 'Tidak menemukan sheet income TikTok dengan header order dan settlement/fee.';
            return [];
        }

        $this->lastStats['sheet_name'] = $ws->getTitle();
        $this->lastStats['header_row'] = $headerRowIndex;
        $this->lastStats['rows_seen'] = max(0, count($rows) - $headerRowIndex);
        $map = $this->buildHeaderMap($headerRow);
        $out = [];

        for ($i = $headerRowIndex + 1; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null;
            if (!$r || $this->rowEmpty($r)) {
                continue;
            }

            $type = strtolower(trim((string) ($this->v($r, $map, ['type', 'jenis transaksi']) ?? '')));
            $orderId = $this->cleanId($this->v($r, $map, [
                'order/adjustment id',
                'order adjustment id',
                'order id',
                'platform order id',
                'orderid',
                'id pesanan penyesuaian',
            ]));
            $related = $this->cleanId($this->v($r, $map, [
                'related order id',
                'related orderid',
                'id pesanan terkait',
            ]));

            $isOrder = $this->isOrderTransaction($type);
            $platformOrderId = $isOrder || $type === '' ? $orderId : $related;

            // Fee iklan/platform tanpa ID order tidak boleh menjadi order palsu.
            if (!$this->validId($platformOrderId)) {
                if ($isOrder || $type === '') {
                    $this->lastStats['rows_skipped_no_order_id']++;
                } else {
                    $this->lastStats['rows_skipped_unlinked_adjustment']++;
                }
                continue;
            }

            if ($isOrder) {
                $this->lastStats['order_rows']++;
            } else {
                $this->lastStats['adjustment_rows']++;
            }

            $releasedAt = $this->dt($this->v($r, $map, [
                'order settled time',
                'order settled date',
                'settlement time',
                'released time',
                'release time',
                'paid out time',
                'released at',
                'waktu pembayaran pesanan',
                'waktu pemesanan',
            ]));

            $net = $this->moneyIdr($this->v($r, $map, [
                'total settlement amount',
                'total settlement',
                'settlement amount',
                'net payout',
                'net amount',
                'payout',
                'seller received',
                'jumlah penyelesaian pembayaran',
            ]));

            // Export TikTok Indonesia memakai "Total Biaya" bernilai negatif.
            $fee = abs($this->moneyIdr($this->v($r, $map, [
                'total fees',
                'platform fee',
                'commission',
                'service fee',
                'transaction fee',
                'payment fee',
                'total biaya',
            ])));

            $refund = abs($this->moneyIdr($this->v($r, $map, [
                'customer refund',
                'refund',
                'refund amount',
                'refund total',
                'subtotal pengembalian dana setelah diskon penjual',
                'subtotal pengembalian dana sebelum diskon penjual',
            ])));

            $key = (string) $platformOrderId;
            if (!isset($out[$key])) {
                $out[$key] = [
                    'platform_order_id' => $key,
                    'released_at' => $releasedAt,
                    'platform_fee_total' => 0,
                    'refund_total' => 0,
                    'net_payout_actual' => 0,
                    'hint' => [
                        'transaction_types' => [],
                        'currency' => $this->v($r, $map, ['currency', 'mata uang']),
                    ],
                    'raw' => [],
                ];
            }

            $typeLabel = trim((string) ($this->v($r, $map, ['type', 'jenis transaksi']) ?? ''));
            if ($typeLabel !== '' && !in_array($typeLabel, $out[$key]['hint']['transaction_types'], true)) {
                $out[$key]['hint']['transaction_types'][] = $typeLabel;
            }

            $out[$key]['platform_fee_total'] += $fee;
            $out[$key]['refund_total'] += $refund;
            $out[$key]['net_payout_actual'] += $net;

            if ($releasedAt && (!$out[$key]['released_at'] || $releasedAt > $out[$key]['released_at'])) {
                $out[$key]['released_at'] = $releasedAt;
            }

            $out[$key]['raw'][] = $this->rawRow($r, $headerRow);
        }

        $this->lastStats['orders'] = count($out);
        $this->lastStats['rows_skipped'] = $this->lastStats['rows_skipped_no_order_id']
            + $this->lastStats['rows_skipped_unlinked_adjustment'];

        return array_values($out);
    }

    /**
     * Export TikTok bisa memiliki beberapa sheet dan sheet aktif dapat berubah
     * antar-download. Cari berdasarkan header, bukan posisi sheet aktif.
     * Detail pesanan diprioritaskan agar sheet ringkasan tidak terbaca sebagai
     * income kosong.
     */
    private function findIncomeWorksheet($spreadsheet): array
    {
        $worksheets = iterator_to_array($spreadsheet->getWorksheetIterator(), false);
        usort($worksheets, function ($a, $b): int {
            return $this->sheetPriority($a->getTitle()) <=> $this->sheetPriority($b->getTitle());
        });

        foreach ($worksheets as $worksheet) {
            $rows = $this->safeToArray($worksheet);
            if (count($rows) < 2) {
                continue;
            }

            [$headerRowIndex, $headerRow] = $this->detectHeaderRow($rows);
            if ($headerRowIndex > 0) {
                return [$worksheet, $rows, $headerRowIndex, $headerRow];
            }
        }

        return [null, [], 0, []];
    }

    private function sheetPriority(string $title): int
    {
        return match ($this->norm($title)) {
            'detail pesanan' => 0,
            'income' => 1,
            'orders', 'order' => 2,
            default => 10,
        };
    }

    private function safeToArray($ws): array
    {
        return $ws->toArray(null, false, true, true);
    }

    private function detectHeaderRow(array $rows): array
    {
        $max = min(20, count($rows));
        for ($i = 1; $i <= $max; $i++) {
            $r = $rows[$i] ?? [];
            if (!$r) {
                continue;
            }

            $line = $this->norm(implode(' | ', array_map(fn($v) => trim((string) $v), $r)));
            $hasOrder = str_contains($line, 'order adjustment id')
                || str_contains($line, 'order id')
                || str_contains($line, 'id pesanan penyesuaian');
            $hasSettle = str_contains($line, 'total settlement amount')
                || str_contains($line, 'settlement')
                || str_contains($line, 'jumlah penyelesaian pembayaran');
            $hasFees = str_contains($line, 'total fees')
                || str_contains($line, 'fee')
                || str_contains($line, 'total biaya');

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
            if ($name !== '') {
                $map[$this->norm($name)] = $col;
            }
        }
        return $map;
    }

    private function v(array $row, array $map, array $candidates)
    {
        foreach ($candidates as $candidate) {
            $key = $this->norm($candidate);
            if (!isset($map[$key])) {
                continue;
            }

            $value = $row[$map[$key]] ?? null;
            if ($value !== null && $value !== '') {
                return $value;
            }
        }
        return null;
    }

    private function norm(string $s): string
    {
        $s = strtolower(trim($s));
        $s = preg_replace('/[^a-z0-9]+/', ' ', $s);
        $s = preg_replace('/\s+/', ' ', $s);
        return trim($s);
    }

    private function cleanId($value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }

        $value = trim((string) $value);
        $value = preg_replace('/\.0+$/', '', $value);
        return $value !== '' ? $value : null;
    }

    private function validId(?string $id): bool
    {
        return $id !== null && !in_array(strtolower(trim($id)), ['', '0', '-', '/', 'null', 'n/a'], true);
    }

    private function isOrderTransaction(string $type): bool
    {
        $type = $this->norm($type);
        return $type === ''
            || $type === 'order'
            || str_contains($type, 'order ')
            || str_contains($type, 'pesanan');
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

        $neg = str_starts_with($t, '(') && str_ends_with($t, ')');
        if ($neg) {
            $t = substr($t, 1, -1);
        }

        $t = str_replace(['Rp', 'rp', 'IDR', 'idr', ' '], '', $t);
        $t = preg_replace('/[^0-9\.\,\-]/', '', $t);
        if ($t === '' || $t === '-') {
            return 0;
        }

        $t = str_replace([',', '.'], '', $t);
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
        return $ts ? date('Y-m-d H:i:s', $ts) : null;
    }

    private function rawRow(array $row, array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $col => $header) {
            $header = trim((string) $header);
            if ($header !== '') {
                $out[$header] = $row[$col] ?? null;
            }
        }
        return $out;
    }
}
