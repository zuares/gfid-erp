<?php

namespace App\Services\Marketplace\Import\Adapters;

use PhpOffice\PhpSpreadsheet\IOFactory;

class ShopeeImportAdapter implements MpImportAdapterInterface
{
    public function channel(): string
    {
        return 'shopee';
    }

    public function parse(string $path, int $storeId, string $sourceFile): array
    {
        $sheetName = 'orders'; // sesuai file kamu
        $spreadsheet = IOFactory::load($path);
        $ws = $spreadsheet->getSheetByName($sheetName) ?? $spreadsheet->getActiveSheet();

        $rows = $ws->toArray(null, true, true, true);
        if (count($rows) < 2) {
            return [];
        }

        // Row 1 = headers
        $headerRow = $rows[1];
        $colMap = $this->buildHeaderMap($headerRow);

        $itemsByKey = [];

        for ($i = 2; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null;
            if (!$r) {
                continue;
            }

            $orderNo = $this->v($r, $colMap, ['no. pesanan', 'no pesanan', 'order id']);
            if (!$orderNo) {
                continue;
            }

            $status = $this->v($r, $colMap, ['status pesanan']);
            $tracking = $this->v($r, $colMap, ['no. resi', 'no resi', 'resi']);
            $svc = $this->v($r, $colMap, ['opsi pengiriman']);
            $orderCreated = $this->dt($this->v($r, $colMap, ['waktu pesanan dibuat']));
            $paidAt = $this->dt($this->v($r, $colMap, ['waktu pembayaran dilakukan']));
            $completedAt = $this->dt($this->v($r, $colMap, ['waktu pesanan selesai']));
            $shippedAt = $this->dt($this->v($r, $colMap, ['waktu pengiriman diatur'])); // best effort

            // item fields
            $sku = $this->v($r, $colMap, ['nomor referensi sku', 'seller sku', 'sku']);
            $skuParent = $this->v($r, $colMap, ['sku induk']);
            $product = $this->v($r, $colMap, ['nama produk']);
            $variant = $this->v($r, $colMap, ['nama variasi', 'variasi']);

            $qty = (int) $this->num($this->v($r, $colMap, ['jumlah', 'qty', 'quantity']));
            $unitPrice = $this->moneyShopee(
                $this->v($r, $colMap, ['harga setelah diskon', 'harga'])
            );

            $subtotal = $this->moneyShopee(
                $this->v($r, $colMap, ['total harga produk', 'subtotal'])
            );

            if ($subtotal <= 0 && $qty > 0) {
                $subtotal = $qty * $unitPrice;
            }

            // group key: (order + tracking) tracking may be empty
            $key = $orderNo . '|' . ($tracking ?: '');

            if (!isset($itemsByKey[$key])) {
                $itemsByKey[$key] = [
                    'store_id' => $storeId,
                    'channel' => 'shopee',
                    'platform_order_id' => (string) $orderNo,
                    'platform_shipment_id' => null,
                    'tracking_no' => $tracking ? (string) $tracking : null,
                    'marketplace_status' => $status ? (string) $status : null,
                    'status_norm' => $this->normStatus($status),

                    'order_created_at' => $orderCreated,
                    'paid_at' => $paidAt,
                    'shipped_at' => $shippedAt,
                    'delivered_at' => null,
                    'completed_at' => $completedAt,

                    'currency' => 'IDR',
                    'order_subtotal' => 0,
                    'discount_total' => 0,
                    'shipping_fee' => 0,
                    'grand_total' => 0,

                    'platform_fee_total' => 0,
                    'refund_total' => 0,
                    'net_payout_actual' => 0,
                    'released_at' => null,

                    'source_file' => $sourceFile,
                    'raw_payload' => [
                        'shipping_service' => $svc,
                    ],
                    'items' => [],
                ];
            }

            $itemsByKey[$key]['items'][] = [
                'sku_code' => $sku ? (string) $sku : null,
                'sku_parent' => $skuParent ? (string) $skuParent : null,
                'product_name' => $product ? (string) $product : null,
                'variant_name' => $variant ? (string) $variant : null,
                'qty' => $qty,
                'unit_price' => $unitPrice,
                'subtotal' => $subtotal,
                'raw_line' => $this->rawLine($r, $headerRow),
            ];
        }

        // finalize totals
        foreach ($itemsByKey as &$s) {
            $s['total_qty'] = array_sum(array_map(fn($i) => (int) $i['qty'], $s['items']));
            $s['grand_total'] = array_sum(array_map(fn($i) => (float) $i['subtotal'], $s['items']));
        }

        return array_values($itemsByKey);
    }

    /* ================= helpers ================= */

    private function buildHeaderMap(array $headerRow): array
    {
        $map = [];
        foreach ($headerRow as $col => $name) {
            if (!$name) {
                continue;
            }

            $key = $this->norm((string) $name);
            $map[$key] = $col;
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

    private function num($v): float
    {
        if ($v === null || $v === '') {
            return 0;
        }

        if (is_numeric($v)) {
            return (float) $v;
        }

        // handle "Rp 10.000" / "10.000"
        $t = preg_replace('/[^0-9\.\-]/', '', (string) $v);
        // if thousand separators exist, remove all dots except decimal (rare). Assume ID uses dots thousand.
        // safest: remove dots, keep last comma as decimal; but most exports numeric already.
        $t = str_replace('.', '', $t);
        $t = str_replace(',', '.', $t);
        return is_numeric($t) ? (float) $t : 0;
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

        // Shopee file format looks like: 2026-01-31 10:21
        $s = str_replace('/', '-', $s);
        return date('Y-m-d H:i:s', strtotime($s));
    }

    private function normStatus(?string $s): string
    {
        $t = strtolower(trim((string) $s));
        if ($t === '') {
            return 'unknown';
        }

        if (str_contains($t, 'selesai')) {
            return 'delivered';
        }

        if (str_contains($t, 'dibatalkan')) {
            return 'canceled';
        }

        if (str_contains($t, 'dikirim') || str_contains($t, 'sedang dikirim') || str_contains($t, 'transit')) {
            return 'in_transit';
        }

        return 'unknown';
    }

    private function rawLine(array $row, array $headerRow): array
    {
        $out = [];
        foreach ($headerRow as $col => $h) {
            if (!$h) {
                continue;
            }

            $out[(string) $h] = $row[$col] ?? null;
        }
        return $out;
    }

    private function moneyShopee($v): int
    {
        if ($v === null || $v === '') {
            return 0;
        }

        // jika sudah numeric
        if (is_numeric($v)) {
            return (int) round(((float) $v) * 1000);
        }

        // handle string "74.95", "74,95", "Rp 74.95", dll
        $t = trim((string) $v);
        if ($t === '') {
            return 0;
        }

        // keep only digits, dot, comma, minus
        $t = preg_replace('/[^0-9,\.\-]/', '', $t);
        if ($t === '' || $t === '-') {
            return 0;
        }

        // normalize comma as dot for float parsing
        $t = str_replace(',', '.', $t);

        // IMPORTANT: do NOT remove dots here, because Shopee uses dot as decimal thousands-unit
        // Example: "74.95" => 74.95 (float) => *1000 => 74950

        $f = (float) $t;
        return (int) round($f * 1000);
    }

}
