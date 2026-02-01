<?php

namespace App\Services\Marketplace\Shopee;

use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Carbon\Carbon;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportShopeeOrdersService
{
    public function run(
        UploadedFile $file,
        int $storeId,
        string $type, // shipping|completed
        string $channel = 'shopee',
        string $onMissing = 'stop', // stop|skip|create
        bool $dryRun = true,
        bool $skipExisting = true, // ✅ default UX: skip existing
    ): array {
        // ----------------------------
        // Validate args
        // ----------------------------
        if ($storeId <= 0) {
            return $this->fail('store_id wajib.');
        }

        if (!in_array($type, ['shipping', 'completed'], true)) {
            return $this->fail('type harus shipping atau completed.');
        }

        if (!in_array($onMissing, ['stop', 'skip', 'create'], true)) {
            return $this->fail('onMissing harus stop|skip|create.');
        }

        $path = $file->getRealPath();
        if (!$path || !is_file($path)) {
            return $this->fail('File tidak valid.');
        }

        // ----------------------------
        // Read XLSX
        // ----------------------------
        $sheetRows = $this->readXlsxRows($path);
        if (count($sheetRows) === 0) {
            return $this->fail('Tidak ada data terbaca di file.');
        }

        $header = array_map(fn($v) => trim((string) $v), $sheetRows[0]);
        $idx = $this->buildHeaderIndex($header);

        $requiredCols = [
            'No. Pesanan',
            'Waktu Pembayaran Dilakukan',
            'Nomor Referensi SKU',
            'Jumlah',
            'Harga Setelah Diskon',
            'Total Harga Produk',
        ];
        foreach ($requiredCols as $col) {
            if (!isset($idx[$col])) {
                return $this->fail("Kolom wajib tidak ketemu: '{$col}'. Pastikan format report Shopee sesuai.");
            }
        }

        // ----------------------------
        // Parse rows
        // ----------------------------
        $rows = [];
        for ($i = 1; $i < count($sheetRows); $i++) {
            $r = $sheetRows[$i];

            if (!is_array($r) || count(array_filter($r, fn($x) => $x !== null && $x !== '')) === 0) {
                continue;
            }

            $orderNo = trim((string) ($r[$idx['No. Pesanan']] ?? ''));
            if ($orderNo === '') {
                continue;
            }

            $paidAt = $this->parseDateTime($r[$idx['Waktu Pembayaran Dilakukan']] ?? null);
            if (!$paidAt) {
                continue;
            }

            $skuRef = trim((string) ($r[$idx['Nomor Referensi SKU']] ?? ''));
            $qty = (int) $this->toNumber($r[$idx['Jumlah']] ?? 0);

            $unitPrice = (float) $this->toPrice($r[$idx['Harga Setelah Diskon']] ?? 0);
            $lineTotal = (float) $this->toPrice($r[$idx['Total Harga Produk']] ?? 0);

            if ($qty <= 0 || $skuRef === '') {
                continue;
            }

            $completedAt = null;
            if (isset($idx['Waktu Pesanan Selesai'])) {
                $completedAt = $this->parseDateTime($r[$idx['Waktu Pesanan Selesai']] ?? null);
            }

            $awb = null;
            if (isset($idx['No. Resi'])) {
                $awb = trim((string) ($r[$idx['No. Resi']] ?? '')) ?: null;
            }

            $rows[] = [
                'order_no' => $orderNo,
                'paid_at' => $paidAt,
                'completed_at' => $completedAt,
                'awb' => $awb,
                'sku_ref' => $skuRef,
                'qty' => $qty, // qty pack
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal > 0 ? $lineTotal : ($qty * $unitPrice),
            ];
        }

        if (count($rows) === 0) {
            return $this->fail('Tidak ada rows valid (cek paid_at / qty / sku_ref).');
        }

        // ----------------------------
        // Precheck base SKU -> Item
        // ----------------------------
        $uniqueSkus = collect($rows)->pluck('sku_ref')->unique()->values();

        $baseCodes = $uniqueSkus->map(function ($sku) {
            [$base, $pack] = $this->splitPackSku((string) $sku);
            return $base;
        })->unique()->values();

        $itemsByCode = Item::query()
            ->whereIn('code', $baseCodes->all())
            ->get(['id', 'code', 'hpp'])
            ->keyBy('code');

        $missingBase = $baseCodes->filter(fn($c) => !$itemsByCode->has($c))->values();
        $createdItems = 0;

        if ($missingBase->count() > 0 && $onMissing === 'stop') {
            return [
                'ok' => false,
                'message' => 'Ada BASE SKU yang belum ada di items.code. Mode stop.',
                'missing_base_skus' => $missingBase->all(),
            ];
        }

        if ($missingBase->count() > 0 && $onMissing === 'create' && !$dryRun) {
            foreach ($missingBase as $code) {
                $existing = Item::where('code', $code)->first();
                if ($existing) {
                    $itemsByCode->put($existing->code, $existing);
                    continue;
                }

                $new = Item::create([
                    'code' => $code,
                    'name' => $code . ' (AUTO from Shopee)',
                    'unit' => 'pcs',
                    'type' => 'finished_good',
                    'active' => 1,
                    'hpp' => 0,
                    'last_purchase_price' => 0,
                ]);

                $itemsByCode->put($new->code, $new);
                $createdItems++;
            }
        }

        // group by order
        $byOrder = collect($rows)->groupBy('order_no');
        $orderNos = $byOrder->keys()->values()->all();

        // existing map (bulk)
        $existingMap = $this->existingOrdersMap($storeId, $channel, $orderNos);

        // ----------------------------
        // DRY RUN preview
        // ----------------------------
        if ($dryRun) {
            $skippedLines = 0;

            $list = $byOrder->map(function ($g, $orderNo) use ($itemsByCode, $onMissing, &$skippedLines, $existingMap) {
                $paidAt = $g->first()['paid_at'] ?? null;

                $qtyPcs = 0;
                $keptRows = 0;
                $subtotal = 0.0;

                foreach ($g as $l) {
                    [$base, $pack] = $this->splitPackSku((string) $l['sku_ref']);
                    $hasItem = $itemsByCode->has($base);

                    if (!$hasItem && in_array($onMissing, ['skip', 'create'], true)) {
                        $skippedLines++;
                        continue;
                    }

                    $keptRows++;
                    $qtyPcs += ((int) $l['qty']) * $pack;
                    $subtotal += (float) ($l['line_total'] ?? 0);
                }

                $exists = array_key_exists($orderNo, $existingMap);

                return [
                    'order_no' => $orderNo,
                    'paid_at' => $paidAt ? $paidAt->format('Y-m-d H:i:s') : null,

                    'qty_pcs' => (int) $qtyPcs,
                    'subtotal' => (int) round($subtotal, 0),

                    'rows' => (int) $g->count(),
                    'kept_rows' => (int) $keptRows,

                    'exists' => $exists,
                    'existing_invoice_id' => $exists ? (int) $existingMap[$orderNo] : null,
                ];
            })->values()->all();

            $totalOrders = count($list);
            $existingOrders = count(array_filter($list, fn($x) => (bool) ($x['exists'] ?? false)));
            $newOrders = $totalOrders - $existingOrders;

            $sumAmount = array_sum(array_map(fn($x) => (float) ($x['subtotal'] ?? 0), $list));
            $sumQty = array_sum(array_map(fn($x) => (int) ($x['qty_pcs'] ?? 0), $list));

            // optional: amount/qty khusus NEW saja (buat UX lebih jelas)
            $sumAmountNew = array_sum(array_map(fn($x) => (bool) ($x['exists'] ?? false) ? 0 : (float) ($x['subtotal'] ?? 0), $list));
            $sumQtyNew = array_sum(array_map(fn($x) => (bool) ($x['exists'] ?? false) ? 0 : (int) ($x['qty_pcs'] ?? 0), $list));

            return [
                'ok' => true,
                'mode' => 'dry-run',
                'message' => 'Preview OK. Baris yang sudah ada akan otomatis di-skip saat Confirm.',
                'summary' => [
                    'total_orders' => $totalOrders,
                    'new_orders' => $newOrders,
                    'existing_orders' => $existingOrders,

                    'total_amount' => (int) round($sumAmount, 0),
                    'total_qty' => (int) $sumQty,

                    'new_amount' => (int) round($sumAmountNew, 0),
                    'new_qty' => (int) $sumQtyNew,
                ],
                'created_items' => $createdItems,
                'skipped_lines' => $skippedLines,
                'missing_base_skus' => $missingBase->all(),

                // UI tampil max 80 biar enak
                'preview' => array_slice($list, 0, 80),
                'note' => 'Preview ditampilkan max 80 order.',
            ];
        }

        // ----------------------------
        // WRITE MODE (Confirm)
        // ----------------------------
        $createdInvoices = 0;
        $linesCreated = 0;
        $skippedOrders = 0;
        $skippedLines = 0;

        DB::transaction(function () use (
            $byOrder,
            $itemsByCode,
            $existingMap,
            $storeId,
            $channel,
            $type,
            $onMissing,
            $skipExisting,
            &$createdInvoices,
            &$linesCreated,
            &$skippedOrders,
            &$skippedLines
        ) {
            foreach ($byOrder as $orderNo => $lines) {

                // ✅ skip existing
                if ($skipExisting && isset($existingMap[$orderNo])) {
                    $skippedOrders++;
                    continue;
                }

                /** @var Carbon $paidAt */
                $paidAt = $lines->first()['paid_at'];
                $completedAt = $lines->first()['completed_at'] ?? null;
                $awb = $lines->first()['awb'] ?? null;

                // Kalau onMissing=skip/create: jika semua line missing -> skip order
                if ($onMissing !== 'stop') {
                    $hasAnyValid = false;
                    foreach ($lines as $tmp) {
                        [$base, $pack] = $this->splitPackSku((string) $tmp['sku_ref']);
                        if ($itemsByCode->has($base)) {$hasAnyValid = true;break;}
                    }
                    if (!$hasAnyValid) {
                        $skippedOrders++;
                        continue;
                    }
                }

                $invoice = new SalesInvoice();
                $invoice->code = $this->generateInvoiceCode();
                $invoice->store_id = $storeId;
                $invoice->warehouse_id = $this->guessWarehouseId(); // TODO: map ke WH-RTS id kamu
                $invoice->status = 'draft';
                $invoice->currency = 'IDR';

                $invoice->channel = $channel;
                $invoice->channel_order_no = $orderNo;
                $invoice->paid_at = $paidAt;
                $invoice->completed_at = $type === 'completed' ? ($completedAt ?: null): null;
                $invoice->marketplace_status = $type;
                $invoice->awb = $awb ?: null;
                $invoice->date = $paidAt->toDateString();

                $invoice->save();
                $createdInvoices++;

                $subtotal = 0.0;
                $writtenLines = 0;

                foreach ($lines as $l) {
                    [$baseCode, $packQty] = $this->splitPackSku((string) $l['sku_ref']);

                    /** @var Item|null $item */
                    $item = $itemsByCode->get($baseCode);
                    if (!$item) {
                        if ($onMissing === 'skip') {
                            $skippedLines++;
                            continue;
                        }
                        throw new \RuntimeException("Base SKU tidak ketemu: {$baseCode}");
                    }

                    $qtyOrder = (int) $l['qty'];
                    $qtyEff = $qtyOrder * $packQty;

                    $lineTotal = (float) ($l['line_total'] ?? 0.0);
                    $unitPrice = $qtyEff > 0 ? round($lineTotal / $qtyEff, 2) : 0.0;

                    $subtotal += $lineTotal;

                    // snapshot HPP
                    $hppUnit = (float) ($item->hpp ?? 0.0);
                    $hppTotal = $hppUnit * $qtyEff;

                    $marginTotal = $lineTotal - $hppTotal;
                    $marginUnit = $qtyEff > 0 ? ($marginTotal / $qtyEff) : 0.0;

                    SalesInvoiceLine::create([
                        'sales_invoice_id' => $invoice->id,
                        'item_id' => $item->id,
                        'qty' => $qtyEff,
                        'unit_price' => $unitPrice,
                        'line_discount' => 0,
                        'line_total' => $lineTotal,

                        'hpp_unit_snapshot' => $hppUnit,
                        'hpp_total_snapshot' => $hppTotal,

                        'margin_unit' => $marginUnit,
                        'margin_total' => $marginTotal,
                    ]);

                    $linesCreated++;
                    $writtenLines++;
                }

                // kalau semua line ke-skip (onMissing=skip) -> hapus invoice biar bersih
                if ($writtenLines === 0 && $onMissing === 'skip') {
                    $invoice->delete();
                    $createdInvoices--;
                    $skippedOrders++;
                    continue;
                }

                $invoice->subtotal = $subtotal;
                $invoice->discount_total = 0;
                $invoice->tax_percent = 0;
                $invoice->tax_amount = 0;
                $invoice->grand_total = $subtotal;
                $invoice->save();
            }
        });

        return [
            'ok' => true,
            'mode' => 'write',
            'message' => 'Import selesai.',
            'summary' => [
                'orders_in_file' => $byOrder->count(),
                'created_invoices' => $createdInvoices,
                'skipped_orders' => $skippedOrders, // includes existing
                'lines_created' => $linesCreated,
                'skipped_lines' => $skippedLines,
            ],
            'created_items' => $createdItems,
            'missing_base_skus' => $missingBase->all(),
        ];
    }

    // =========================================================
    // Helpers
    // =========================================================
    private function existingOrdersMap(int $storeId, string $channel, array $orderNos): array
    {
        if (empty($orderNos)) {
            return [];
        }

        return SalesInvoice::query()
            ->where('store_id', $storeId)
            ->where('channel', $channel)
            ->whereIn('channel_order_no', $orderNos)
            ->pluck('id', 'channel_order_no') // [order_no => invoice_id]
            ->all();
    }

    private function readXlsxRows(string $file): array
    {
        $spreadsheet = IOFactory::load($file);
        $sheet = $spreadsheet->getActiveSheet();
        return $sheet->toArray(null, true, true, false);
    }

    private function buildHeaderIndex(array $header): array
    {
        $idx = [];
        foreach ($header as $i => $name) {
            $name = trim((string) $name);
            if ($name !== '') {
                $idx[$name] = $i;
            }

        }
        return $idx;
    }

    private function splitPackSku(string $sku): array
    {
        $sku = trim($sku);

        if (preg_match('/^(.*)-(\d+)$/', $sku, $m)) {
            $base = trim($m[1]);
            $pack = (int) $m[2];
            if ($base !== '' && $pack > 0) {
                return [$base, $pack];
            }

        }

        return [$sku, 1];
    }

    private function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            return (float) $value;
        }

        $s = trim((string) $value);
        $s = str_replace(["Rp", "rp", " ", "\u{00A0}"], '', $s);

        if (preg_match('/^\-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            return (float) $s;
        }

        if (preg_match('/^\-?\d{1,3}(,\d{3})+(\.\d+)?$/', $s)) {
            $s = str_replace(',', '', $s);
            return (float) $s;
        }

        $s = str_replace(['.', ','], ['', '.'], $s);
        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function toPrice($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }

        if (is_numeric($value)) {
            $v = (float) $value;
            if ($v < 1000 && floor($v) !== $v) {
                return (float) round($v * 1000, 0);
            }

            return $v;
        }

        $s = trim((string) $value);
        $s = str_replace(["Rp", "rp", " ", "\u{00A0}"], '', $s);

        if (preg_match('/^\-?\d{1,3}(\.\d{3})+(,\d+)?$/', $s)) {
            $s = str_replace('.', '', $s);
            $s = str_replace(',', '.', $s);
            return (float) $s;
        }

        return (float) $this->toNumber($value);
    }

    private function parseDateTime($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
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

    private function generateInvoiceCode(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $n = (int) (SalesInvoice::query()->where('code', 'like', $prefix . '%')->count() + 1);
        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    private function guessWarehouseId(): int
    {
        return 1; // TODO: ganti ke WH-RTS id kamu
    }

    private function fail(string $message): array
    {
        return ['ok' => false, 'message' => $message];
    }
}
