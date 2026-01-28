<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportShopeeOrdersToSalesInvoices extends Command
{
    protected $signature = 'shopee:import-orders
        {--file= : Path ke file .xlsx (wajib)}
        {--store_id= : store_id (wajib)}
        {--type=shipping : shipping|completed}
        {--channel=shopee : channel label, default shopee}
        {--on-missing=stop : stop|skip|create (default stop). Missing BASE SKU: stop=batal, skip=lewati baris, create=auto buat Item}
        {--dry-run=0 : 1 untuk test tanpa write DB}
    ';

    protected $description = 'Import Shopee Order Shipping/Completed (xlsx) ke sales_invoices + sales_invoice_lines (upsert by store_id + channel_order_no). date memakai paid_at. Support SKU clean (K7BLK) dan SKU pack suffix angka (K5NVY-6 => base K5NVY, qty x 6). Missing BASE SKU bisa stop/skip/create.';

    public function handle(): int
    {
        $file = (string) $this->option('file');
        $storeId = (int) $this->option('store_id');
        $type = strtolower((string) $this->option('type'));
        $channel = (string) $this->option('channel');
        $onMissing = strtolower((string) $this->option('on-missing'));
        $dryRun = ((int) $this->option('dry-run')) === 1;

        if (!$file || !is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }

        if ($storeId <= 0) {
            $this->error("store_id wajib diisi. Contoh: --store_id=3");
            return 1;
        }

        if (!in_array($type, ['shipping', 'completed'], true)) {
            $this->error("type harus shipping atau completed.");
            return 1;
        }

        if (!in_array($onMissing, ['stop', 'skip', 'create'], true)) {
            $this->error("on-missing harus stop|skip|create. Contoh: --on-missing=skip");
            return 1;
        }

        $this->info("Reading: {$file}");
        $sheetRows = $this->readXlsxRows($file);

        if (count($sheetRows) === 0) {
            $this->error("Tidak ada data rows terbaca.");
            return 1;
        }

        // 1) Map header → index
        $header = array_map(fn($v) => trim((string) $v), $sheetRows[0]);
        $idx = $this->buildHeaderIndex($header);

        // Kolom minimal yang kita butuhkan
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
                $this->error("Kolom wajib tidak ketemu: '{$col}'. Pastikan format report Shopee sesuai.");
                return 1;
            }
        }

        // 2) Parse rows → normalize structure
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
                continue; // paid_at wajib
            }

            $skuRef = trim((string) ($r[$idx['Nomor Referensi SKU']] ?? ''));
            $qty = (int) $this->toNumber($r[$idx['Jumlah']] ?? 0);

            $unitPrice = (float) $this->toNumber($r[$idx['Harga Setelah Diskon']] ?? 0);
            $lineTotal = (float) $this->toNumber($r[$idx['Total Harga Produk']] ?? 0);

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
                'paid_at' => $paidAt, // Carbon
                'completed_at' => $completedAt, // ?Carbon
                'awb' => $awb,
                'sku_ref' => $skuRef,
                'qty' => $qty, // jumlah pack / jumlah unit di report
                'unit_price' => $unitPrice,
                'line_total' => $lineTotal > 0 ? $lineTotal : ($qty * $unitPrice),
            ];
        }

        if (count($rows) === 0) {
            $this->error("Tidak ada rows valid (cek kolom paid_at / qty / sku_ref).");
            return 1;
        }

        // 3) Pre-check BASE SKU mapping ke items.code
        $uniqueSkus = collect($rows)->pluck('sku_ref')->unique()->values();

        $baseCodes = $uniqueSkus->map(function ($sku) {
            [$base, $pack] = $this->splitPackSku((string) $sku);
            return $base;
        })->unique()->values();

        $itemsByCode = Item::query()
            ->whereIn('code', $baseCodes->all())
            ->get(['id', 'code'])
            ->keyBy('code');

        $missing = $baseCodes->filter(fn($c) => !$itemsByCode->has($c))->values();

        if ($missing->count() > 0) {
            $this->warn("Ada BASE SKU yang belum ada di items.code:");
            foreach ($missing as $m) {
                $this->line(" - {$m}");
            }

            if ($onMissing === 'stop') {
                $this->error("Mode on-missing=stop → import dibatalkan.");
                $this->line("Jalankan ulang dengan salah satu:");
                $this->line(" - --on-missing=skip   (lewati baris SKU missing)");
                $this->line(" - --on-missing=create (auto buat Item untuk base SKU missing)");
                return 1;
            }

            if ($onMissing === 'create') {
                if ($dryRun) {
                    $this->warn("DRY-RUN + on-missing=create → tidak akan create item. (Info saja)");
                } else {
                    $this->warn("Mode on-missing=create → AUTO-CREATE Item untuk base SKU missing...");

                    foreach ($missing as $code) {
                        $existing = Item::where('code', $code)->first();
                        if ($existing) {
                            $itemsByCode->put($existing->code, $existing);
                            continue;
                        }

                        // Sesuaikan field wajib item kamu bila ada yang required (mis. item_category_id)
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
                    }

                    $this->info("Auto-create item done. created=" . $missing->count());
                }
            }

            if ($onMissing === 'skip') {
                $this->warn("Mode on-missing=skip → baris dengan base SKU missing akan di-skip.");
            }
        }

        // 4) Group by order_no
        $byOrder = collect($rows)->groupBy('order_no');

        $this->info("Orders ditemukan: " . $byOrder->count());
        $this->info("Mode: " . ($dryRun ? "DRY-RUN (no DB write)" : "WRITE"));
        $this->info("on-missing: {$onMissing}");

        $created = 0;
        $updated = 0;
        $lineCount = 0;
        $skippedLines = 0;
        $skippedOrders = 0;

        if ($dryRun) {
            $preview = $byOrder->map(function ($g, $orderNo) use ($itemsByCode, $onMissing, &$skippedLines) {
                $paidAt = $g->first()['paid_at'] ?? null;

                $qtyPcs = 0;
                $keptRows = 0;

                foreach ($g as $l) {
                    [$base, $pack] = $this->splitPackSku((string) $l['sku_ref']);
                    $hasItem = $itemsByCode->has($base);

                    if (!$hasItem && $onMissing === 'skip') {
                        $skippedLines++;
                        continue;
                    }

                    // jika create, di dry-run item belum dibuat, jadi treat missing as skipped (biar keliatan)
                    if (!$hasItem && $onMissing === 'create') {
                        $skippedLines++;
                        continue;
                    }

                    $keptRows++;
                    $qtyPcs += ((int) $l['qty']) * $pack;
                }

                return [
                    $orderNo,
                    $g->count(),
                    $keptRows,
                    $qtyPcs,
                    optional($paidAt)->format('Y-m-d H:i:s'),
                ];
            })->values()->all();

            $this->table(['order_no', 'rows', 'kept_rows', 'qty_pcs', 'paid_at'], $preview);

            if ($onMissing !== 'stop') {
                $this->line("Info: skipped_lines={$skippedLines} (karena base SKU missing di mode {$onMissing})");
            }

            return 0;
        }

        DB::transaction(function () use (
            $byOrder,
            $itemsByCode,
            $storeId,
            $channel,
            $type,
            $onMissing,
            &$created,
            &$updated,
            &$lineCount,
            &$skippedLines,
            &$skippedOrders
        ) {
            foreach ($byOrder as $orderNo => $lines) {
                /** @var \Carbon\Carbon $paidAt */
                $paidAt = $lines->first()['paid_at'];
                $completedAt = $lines->first()['completed_at'] ?? null;
                $awb = $lines->first()['awb'] ?? null;

                // kalau mode skip/create dan order ini semua barisnya missing → jangan buat invoice kosong
                $hasAnyValidLine = false;
                foreach ($lines as $tmp) {
                    [$base, $pack] = $this->splitPackSku((string) $tmp['sku_ref']);
                    if ($itemsByCode->has($base)) {
                        $hasAnyValidLine = true;
                        break;
                    }
                }

                if (!$hasAnyValidLine && $onMissing !== 'stop') {
                    $skippedOrders++;
                    continue;
                }

                $invoice = SalesInvoice::query()
                    ->where('store_id', $storeId)
                    ->where('channel_order_no', $orderNo)
                    ->first();

                $isNew = false;
                if (!$invoice) {
                    $invoice = new SalesInvoice();
                    $invoice->code = $this->generateInvoiceCode();
                    $invoice->store_id = $storeId;
                    $invoice->warehouse_id = $this->guessWarehouseId();
                    $invoice->status = 'draft';
                    $invoice->currency = 'IDR';
                    $isNew = true;
                }

                // Header marketplace fields
                $invoice->channel = $channel;
                $invoice->channel_order_no = $orderNo;
                $invoice->paid_at = $paidAt;
                $invoice->completed_at = $type === 'completed'
                ? ($completedAt ?: $invoice->completed_at)
                : $invoice->completed_at;
                $invoice->marketplace_status = $type;
                $invoice->awb = $awb ?: $invoice->awb;

                // date invoice = paid_at
                $invoice->date = $paidAt->toDateString();

                $invoice->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                    $invoice->lines()->delete();
                }

                $subtotal = 0.0;
                $writtenLinesForInvoice = 0;

                foreach ($lines as $l) {
                    [$baseCode, $packQty] = $this->splitPackSku((string) $l['sku_ref']);

                    $item = $itemsByCode->get($baseCode);
                    if (!$item) {
                        if ($onMissing === 'skip') {
                            $skippedLines++;
                            continue;
                        }

                        // seharusnya tidak terjadi di create karena sudah dibuat di precheck,
                        // tapi jaga-jaga:
                        throw new \RuntimeException("Base SKU tidak ketemu di items: {$baseCode} (from {$l['sku_ref']})");
                    }

                    $qtyOrder = (int) $l['qty']; // jumlah di report (pack)
                    $qtyEff = $qtyOrder * $packQty; // qty real pcs

                    $lineTotal = (float) $l['line_total']; // TETAP dari Shopee (jangan dikali pack)
                    $unitPrice = $qtyEff > 0 ? round($lineTotal / $qtyEff, 2) : 0.0;

                    $subtotal += $lineTotal;

                    SalesInvoiceLine::create([
                        'sales_invoice_id' => $invoice->id,
                        'item_id' => $item->id,
                        'qty' => $qtyEff,
                        'unit_price' => $unitPrice,
                        'line_discount' => 0,
                        'line_total' => $lineTotal,
                        'hpp_unit_snapshot' => 0,
                        'margin_unit' => 0,
                        'margin_total' => 0,
                    ]);

                    $lineCount++;
                    $writtenLinesForInvoice++;
                }

                // kalau akhirnya kosong (semua baris di-skip) → hapus invoice ini
                if ($writtenLinesForInvoice === 0 && $onMissing === 'skip') {
                    $invoice->delete();
                    $skippedOrders++;
                    continue;
                }

                // totals
                $invoice->subtotal = $subtotal;
                $invoice->discount_total = 0;
                $invoice->tax_percent = 0;
                $invoice->tax_amount = 0;
                $invoice->grand_total = $subtotal;
                $invoice->save();
            }
        });

        $this->info("Done. created={$created}, updated={$updated}, lines={$lineCount}, skipped_lines={$skippedLines}, skipped_orders={$skippedOrders}");
        return 0;
    }

    /**
     * Support SKU:
     * - Clean: K7BLK => base K7BLK, pack 1
     * - Pack : K5NVY-6 => base K5NVY, pack 6
     */
    private function splitPackSku(string $sku): array
    {
        $sku = trim($sku);

        // suffix angka di ujung
        if (preg_match('/^(.*)-(\d+)$/', $sku, $m)) {
            $base = trim($m[1]);
            $pack = (int) $m[2];

            if ($base !== '' && $pack > 0) {
                return [$base, $pack];
            }
        }

        return [$sku, 1];
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
        $s = str_replace(['.', ','], ['', '.'], $s);
        $s = preg_replace('/[^0-9\.\-]/', '', $s);

        return is_numeric($s) ? (float) $s : 0.0;
    }

    private function parseDateTime($value): ?Carbon
    {
        if ($value === null || $value === '') {
            return null;
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

    private function generateInvoiceCode(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $n = (int) (SalesInvoice::query()->where('code', 'like', $prefix . '%')->count() + 1);
        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    private function guessWarehouseId(): int
    {
        // sementara: set manual (misal WH-RTS id)
        return 1;
    }
}
