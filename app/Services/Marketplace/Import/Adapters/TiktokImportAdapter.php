<?php

namespace App\Services\Marketplace\Import\Adapters;

use PhpOffice\PhpSpreadsheet\IOFactory;

class TiktokImportAdapter implements MpImportAdapterInterface
{
    public function channel(): string
    {
        return 'tiktok';
    }

    public function parse(string $path, int $storeId, string $sourceFile): array
    {
        $sp = IOFactory::load($path);

        // Prefer OrderSKUList sheet if exists (full export)
        $wsFull = $sp->getSheetByName('OrderSKUList');
        if ($wsFull) {
            $rows = $this->safeToArray($wsFull);
            $det = $this->detectHeaderRowFull($rows);
            if ($det[0]) {
                return $this->parseFull($rows, $storeId, $sourceFile, $det[0], $det[1]);
            }
        }

        // Fallback to active sheet (could be status-only file)
        $ws = $sp->getActiveSheet();
        $rows = $this->safeToArray($ws);
        if (count($rows) < 2) {
            return [];
        }

        // Detect status-only: header has "Order ID" and very few columns
        [$hdrIdx, $hdr] = $this->detectHeaderRowStatusOnly($rows);
        if ($hdrIdx) {
            return $this->parseStatusOnly($rows, $storeId, $sourceFile, $hdrIdx, $hdr);
        }

        // As last attempt: detect full header in active sheet too
        [$hdrIdx2, $hdr2] = $this->detectHeaderRowFull($rows);
        if ($hdrIdx2) {
            return $this->parseFull($rows, $storeId, $sourceFile, $hdrIdx2, $hdr2);
        }

        return [];
    }

    /* ============================================================
     * FULL PARSER (OrderSKUList)
     * ============================================================
     */
    private function parseFull(array $rows, int $storeId, string $sourceFile, int $headerRowIndex, array $headerRow): array
    {
        $map = $this->buildHeaderMap($headerRow);

        $shipments = [];

        // Often row after header is description, but not always.
        // We start from next row; rows with empty order id will be skipped anyway.
        for ($i = $headerRowIndex + 1; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null;
            if (!$r || $this->rowEmpty($r)) {
                continue;
            }

            $orderId = $this->v($r, $map, ['order id', 'platform order id']);
            $orderId = $orderId ? trim((string) $orderId) : '';
            if ($orderId === '') {
                continue;
            }

            $status = $this->v($r, $map, ['order status', 'status']);
            $substatus = $this->v($r, $map, ['order substatus', 'substatus']);
            $tracking = $this->v($r, $map, ['tracking id', 'tracking no', 'tracking number', 'awb', 'resi']);

            $created = $this->dt($this->v($r, $map, ['created time', 'order created time']));
            $paidAt = $this->dt($this->v($r, $map, ['paid time', 'payment time']));
            $shippedAt = $this->dt($this->v($r, $map, ['shipped time', 'ship time']));
            $deliveredAt = $this->dt($this->v($r, $map, ['delivered time', 'delivered at', 'delivery time']));
            $completedAt = $this->dt($this->v($r, $map, ['completed time', 'completed at']));

            $sku = $this->v($r, $map, ['seller sku', 'sku']);
            $product = $this->v($r, $map, ['product name', 'product']);
            $variant = $this->v($r, $map, ['variation', 'variant', 'variant name']);
            $qty = (int) $this->num($this->v($r, $map, ['quantity', 'qty']));
            $unitPrice = (float) $this->num($this->v($r, $map, ['sku unit original price', 'unit price']));
            $subtotal = (float) $this->num($this->v($r, $map, ['sku subtotal after discount', 'subtotal']));

            if ($subtotal <= 0 && $qty > 0) {
                $subtotal = $qty * $unitPrice;
            }

            $key = $orderId . '|' . ($tracking ? trim((string) $tracking) : '');

            if (!isset($shipments[$key])) {
                $shipments[$key] = [
                    'store_id' => $storeId,
                    'channel' => 'tiktok',
                    'platform_order_id' => $orderId,
                    'platform_shipment_id' => null,
                    'tracking_no' => $tracking ? trim((string) $tracking) : null,

                    'marketplace_status' => $status ? (string) $status : null,
                    'status_norm' => $this->normStatus($status, $substatus),

                    'order_created_at' => $created,
                    'paid_at' => $paidAt,
                    'shipped_at' => $shippedAt,
                    'delivered_at' => $deliveredAt,
                    'completed_at' => $completedAt ?: $deliveredAt,

                    'currency' => (string) ($this->v($r, $map, ['currency']) ?: 'IDR'),
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
                        'order_substatus' => $substatus,
                        'shipping_provider_name' => $this->v($r, $map, ['shipping provider name']),
                        'delivery_option' => $this->v($r, $map, ['delivery option']),
                        'source' => 'full',
                    ],
                    'items' => [],
                ];
            }

            // Only append item when it looks like a real line
            if ($sku || $product || $qty > 0) {
                $shipments[$key]['items'][] = [
                    'sku_code' => $sku ? (string) $sku : null,
                    'sku_parent' => null,
                    'product_name' => $product ? (string) $product : null,
                    'variant_name' => $variant ? (string) $variant : null,
                    'qty' => $qty,
                    'unit_price' => $unitPrice,
                    'subtotal' => $subtotal,
                    'raw_line' => $this->rawLine($r, $headerRow),
                ];
            }
        }

        foreach ($shipments as &$s) {
            $s['total_qty'] = array_sum(array_map(fn($i) => (int) ($i['qty'] ?? 0), $s['items']));
            $s['grand_total'] = array_sum(array_map(fn($i) => (float) ($i['subtotal'] ?? 0), $s['items']));
        }

        return array_values($shipments);
    }

    /* ============================================================
     * STATUS-ONLY PARSER (Order ID list)
     * ============================================================
     */
    private function parseStatusOnly(array $rows, int $storeId, string $sourceFile, int $headerRowIndex, array $headerRow): array
    {
        $map = $this->buildHeaderMap($headerRow);

        $seen = [];
        $out = [];

        // Data may start at next row; sometimes there is a description row.
        for ($i = $headerRowIndex + 1; $i <= count($rows); $i++) {
            $r = $rows[$i] ?? null;
            if (!$r || $this->rowEmpty($r)) {
                continue;
            }

            $orderId = $this->v($r, $map, ['order id', 'platform order id']);
            $orderId = $orderId ? trim((string) $orderId) : '';

            // skip description like "Platform unique order ID."
            if ($orderId === '' || !ctype_digit($orderId)) {
                continue;
            }

            if (isset($seen[$orderId])) {
                continue;
            }
            $seen[$orderId] = true;

            $out[] = [
                'store_id' => $storeId,
                'channel' => 'tiktok',
                'platform_order_id' => $orderId,
                'platform_shipment_id' => null,
                'tracking_no' => null,

                'marketplace_status' => 'Dalam Pengiriman',
                'status_norm' => 'in_transit',

                'order_created_at' => null,
                'paid_at' => null,
                'shipped_at' => null,
                'delivered_at' => null,
                'completed_at' => null,

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
                    'source' => 'status_only',
                ],
                'items' => [],
            ];
        }

        return $out;
    }

    /* ============================================================
     * Helpers (Shopee-style)
     * ============================================================
     */
    private function safeToArray($ws): array
    {
        // Worksheet::toArray(nullValue, calculateFormulas, formatData, returnCellRef)
        return $ws->toArray(null, false, true, true);
    }

    private function detectHeaderRowFull(array $rows): array
    {
        // scan first ~30 rows to find header that contains "Order ID" and "Seller SKU"/"Product"
        $max = min(30, count($rows));
        for ($i = 1; $i <= $max; $i++) {
            $r = $rows[$i] ?? [];
            if (!$r) {
                continue;
            }

            $line = $this->norm(implode(' | ', array_map(fn($v) => trim((string) $v), $r)));

            $hasOrder = str_contains($line, 'order id');
            $hasSku = str_contains($line, 'seller sku') || str_contains($line, 'sku');
            $hasProduct = str_contains($line, 'product name') || str_contains($line, 'product');

            if ($hasOrder && ($hasSku || $hasProduct)) {
                return [$i, $r];
            }
        }
        return [0, []];
    }

    private function detectHeaderRowStatusOnly(array $rows): array
    {
        // status-only usually has just 1-2 columns including "Order ID"
        $max = min(10, count($rows));
        for ($i = 1; $i <= $max; $i++) {
            $r = $rows[$i] ?? [];
            if (!$r) {
                continue;
            }

            $line = $this->norm(implode(' | ', array_map(fn($v) => trim((string) $v), $r)));
            if (!str_contains($line, 'order id')) {
                continue;
            }

            $nonEmpty = count(array_filter($r, fn($v) => trim((string) $v) !== ''));
            if ($nonEmpty <= 3) {
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
        // Shopee-style robust: remove symbols, normalize spaces
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

    private function num($v): float
    {
        if ($v === null || $v === '') {
            return 0;
        }

        if (is_numeric($v)) {
            return (float) $v;
        }

        $t = trim((string) $v);
        if ($t === '') {
            return 0;
        }

        $t = preg_replace('/[^0-9\.\,\-]/', '', $t);
        // convert comma decimals to dot if needed
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

        $s = str_replace('/', '-', $s);
        $ts = strtotime($s);
        if (!$ts) {
            return null;
        }

        return date('Y-m-d H:i:s', $ts);
    }

    private function normStatus(?string $status, ?string $sub): string
    {
        $a = strtolower(trim((string) $status));
        $b = strtolower(trim((string) $sub));

        if ($a === '' && $b === '') {
            return 'unknown';
        }

        if (str_contains($a, 'selesai') || str_contains($a, 'completed') || str_contains($b, 'delivered') || str_contains($b, 'terkirim')) {
            return 'delivered';
        }

        if (str_contains($a, 'dibatalkan') || str_contains($a, 'cancel')) {
            return 'canceled';
        }

        if (str_contains($a, 'dikirim') || str_contains($a, 'ship') || str_contains($b, 'transit') || str_contains($a, 'in transit')) {
            return 'in_transit';
        }

        // common: "To ship" / "Processing"
        if (str_contains($a, 'to ship') || str_contains($a, 'processing') || str_contains($a, 'dikemas')) {
            return 'processing';
        }

        return 'unknown';
    }

    private function rawLine(array $row, array $headerRow): array
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
