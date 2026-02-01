<?php

namespace App\Console\Commands;

use App\Models\Item;
use App\Models\SalesInvoice;
use App\Models\SalesInvoiceLine;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class ImportMarketplaceOrders extends Command
{
    protected $signature = 'orders:import
        {--platform= : shopee|tiktok|lazada|tokopedia (wajib)}
        {--file= : Path ke file .xlsx (wajib)}
        {--store_id= : store_id (wajib)}
        {--type=shipping : shipping|completed}
        {--channel= : optional override channel label (default = platform)}
        {--on-missing=stop : stop|skip|create}
        {--dry-run=0 : 1 untuk test tanpa write DB}
    ';

    protected $description = 'Import orders multi-platform -> sales_invoices + lines (upsert by store_id + channel + channel_order_no).';

    public function handle(): int
    {
        $platform = strtolower(trim((string) $this->option('platform')));
        $file = (string) $this->option('file');
        $storeId = (int) $this->option('store_id');
        $type = strtolower((string) $this->option('type'));
        $channel = trim((string) $this->option('channel')) ?: $platform;
        $onMissing = strtolower((string) $this->option('on-missing'));
        $dryRun = ((int) $this->option('dry-run')) === 1;

        if (!in_array($platform, ['shopee', 'tiktok', 'lazada', 'tokopedia'], true)) {
            $this->error("platform wajib: shopee|tiktok|lazada|tokopedia");
            return 1;
        }
        if (!$file || !is_file($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }
        if ($storeId <= 0) {
            $this->error("store_id wajib diisi.");
            return 1;
        }
        if (!in_array($type, ['shipping', 'completed'], true)) {
            $this->error("type harus shipping atau completed.");
            return 1;
        }
        if (!in_array($onMissing, ['stop', 'skip', 'create'], true)) {
            $this->error("on-missing harus stop|skip|create.");
            return 1;
        }

        $this->info("Reading: {$file}");
        $sheetRows = $this->readXlsxRows($file);
        if (count($sheetRows) === 0) {
            $this->error("Tidak ada data rows terbaca.");
            return 1;
        }

        $header = array_map(fn($v) => trim((string) $v), $sheetRows[0]);

        // 1) pilih adapter
        $adapter = match ($platform) {
            'shopee' => new PlatformAdapters\ShopeeAdapter(),
            'tiktok' => new PlatformAdapters\TiktokAdapter(),
            'lazada' => new PlatformAdapters\LazadaAdapter(),
            'tokopedia' => new PlatformAdapters\TokopediaAdapter(),
        };

        // 2) validasi header wajib
        $missingCols = $adapter->missingRequiredColumns($header);
        if (!empty($missingCols)) {
            $this->error("Kolom wajib tidak ketemu untuk {$platform}:");
            foreach ($missingCols as $c) {
                $this->line(" - {$c}");
            }

            return 1;
        }

        // 3) normalize rows (line-level)
        $idx = $this->buildHeaderIndex($header);
        $lines = [];

        for ($i = 1; $i < count($sheetRows); $i++) {
            $r = $sheetRows[$i];
            if (!is_array($r) || count(array_filter($r, fn($x) => $x !== null && $x !== '')) === 0) {
                continue;
            }

            $line = $adapter->parseRow($r, $idx, function ($val) {
                return $this->parseDateTime($val);
            }, function ($val) {
                return $this->toNumber($val);
            }, function (string $sku) {
                return $this->splitPackSku($sku);
            });

            if ($line) {
                $lines[] = $line;
            }
        }

        if (count($lines) === 0) {
            $this->error("Tidak ada rows valid setelah parsing adapter.");
            return 1;
        }

        // 4) pre-check item mapping (BASE SKU -> items.code)
        $baseCodes = collect($lines)->pluck('base_sku')->unique()->values();

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
                return 1;
            }

            if ($onMissing === 'create') {
                if ($dryRun) {
                    $this->warn("DRY-RUN + on-missing=create → tidak create item.");
                } else {
                    foreach ($missing as $code) {
                        $existing = Item::where('code', $code)->first();
                        if ($existing) {$itemsByCode->put($existing->code, $existing);
                            continue;}

                        $new = Item::create([
                            'code' => $code,
                            'name' => $code . ' (AUTO ' . strtoupper($platform) . ')',
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

        // 5) group per order
        $byOrder = collect($lines)->groupBy('order_no');

        $this->info("Orders ditemukan: " . $byOrder->count());
        $this->info("Platform={$platform} Channel={$channel} Type={$type}");
        $this->info("Mode: " . ($dryRun ? "DRY-RUN" : "WRITE"));
        $this->info("on-missing: {$onMissing}");

        $created = 0;
        $updated = 0;
        $lineCount = 0;
        $skippedLines = 0;
        $skippedOrders = 0;

        if ($dryRun) {
            $preview = $byOrder->map(function (Collection $g, $orderNo) use ($itemsByCode, $onMissing, &$skippedLines) {
                $paidAt = $g->first()['paid_at'] ?? null;

                $qtyPcs = 0; $kept = 0;

                foreach ($g as $l) {
                    $hasItem = $itemsByCode->has($l['base_sku']);
                    if (!$hasItem && in_array($onMissing, ['skip', 'create'], true)) {
                        $skippedLines++;
                        continue;
                    }
                    $kept++;
                    $qtyPcs += (int) $l['qty_eff'];
                }

                return [
                    $orderNo,
                    $g->count(),
                    $kept,
                    $qtyPcs,
                    optional($paidAt)->format('Y-m-d H:i:s'),
                ];
            })->values()->all();

            $this->table(['order_no', 'rows', 'kept_rows', 'qty_pcs', 'paid_at'], $preview);
            $this->line("Info: skipped_lines={$skippedLines}");
            return 0;
        }

        DB::transaction(function () use (
            $byOrder, $itemsByCode, $storeId, $channel, $type, $onMissing,
            &$created, &$updated, &$lineCount, &$skippedLines, &$skippedOrders
        ) {
            foreach ($byOrder as $orderNo => $lines) {
                /** @var Carbon $paidAt */
                $paidAt = $lines->first()['paid_at'];
                $completedAt = $lines->first()['completed_at'] ?? null;
                $awb = $lines->first()['awb'] ?? null;

                $hasAnyValid = $lines->contains(fn($l) => $itemsByCode->has($l['base_sku']));
                if (!$hasAnyValid && $onMissing !== 'stop') {
                    $skippedOrders++;
                    continue;
                }

                $invoice = SalesInvoice::query()
                    ->where('store_id', $storeId)
                    ->where('channel', $channel)
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

                $invoice->channel = $channel;
                $invoice->channel_order_no = $orderNo;
                $invoice->paid_at = $paidAt;
                $invoice->completed_at = $type === 'completed'
                ? ($completedAt ?: $invoice->completed_at)
                : $invoice->completed_at;
                $invoice->marketplace_status = $type;
                $invoice->awb = $awb ?: $invoice->awb;
                $invoice->date = $paidAt->toDateString();

                $invoice->save();

                if ($isNew) {
                    $created++;
                } else {
                    $updated++;
                    $invoice->lines()->delete();
                }

                $subtotal = 0.0;
                $written = 0;

                foreach ($lines as $l) {
                    $item = $itemsByCode->get($l['base_sku']);
                    if (!$item) {
                        if ($onMissing === 'skip') {$skippedLines++;continue;}
                        throw new \RuntimeException("Base SKU tidak ketemu: {$l['base_sku']}");
                    }

                    $lineTotal = (float) $l['line_total'];
                    $qtyEff = (int) $l['qty_eff'];
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
                    $written++;
                }

                if ($written === 0 && $onMissing === 'skip') {
                    $invoice->delete();
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

        $this->info("Done. created={$created}, updated={$updated}, lines={$lineCount}, skipped_lines={$skippedLines}, skipped_orders={$skippedOrders}");
        return 0;
    }

    // ===== Helpers (sama seperti punyamu) =====

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

    private function readXlsxRows(string $file): array
    {
        $spreadsheet = IOFactory::load($file);
        return $spreadsheet->getActiveSheet()->toArray(null, true, true, false);
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

        if (is_numeric($value)) {
            try {
                return Carbon::instance(ExcelDate::excelToDateTimeObject($value));
            } catch (\Throwable) {return null;}
        }

        $s = trim((string) $value);
        if ($s === '') {
            return null;
        }

        try {return Carbon::parse($s);} catch (\Throwable) {return null;}
    }

    private function generateInvoiceCode(): string
    {
        $prefix = 'INV-' . now()->format('Ymd') . '-';
        $n = (int) (SalesInvoice::query()->where('code', 'like', $prefix . '%')->count() + 1);
        return $prefix . str_pad((string) $n, 3, '0', STR_PAD_LEFT);
    }

    private function guessWarehouseId(): int
    {return 1;}
}
