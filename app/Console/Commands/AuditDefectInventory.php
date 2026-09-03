<?php

namespace App\Console\Commands;

use App\Services\Inventory\DefectInventoryAuditService;
use Illuminate\Console\Command;
use JsonException;

class AuditDefectInventory extends Command
{
    protected $signature = 'inventory:audit-defect-stock
                            {--json : Output laporan sebagai JSON}
                            {--fail-on-finding : Exit code gagal jika ditemukan anomali}';

    protected $description = 'Audit read-only stok barang cacat, mutasi, HPP, dan akun 1204.';

    public function handle(DefectInventoryAuditService $audit): int
    {
        $report = $audit->audit();

        if ($this->option('json')) {
            try {
                $this->line(json_encode($report, JSON_THROW_ON_ERROR | JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
            } catch (JsonException $e) {
                $this->error('Gagal membentuk JSON audit: ' . $e->getMessage());
                return self::FAILURE;
            }
        } else {
            $this->renderText($report);
        }

        return $this->option('fail-on-finding') && $report['summary']['finding_count'] > 0
            ? self::FAILURE
            : self::SUCCESS;
    }

    private function renderText(array $report): void
    {
        $this->info('Audit stok barang cacat — READ ONLY');
        $this->line('Generated: ' . $report['generated_at']);
        $this->newLine();

        $this->table(
            ['Gudang', 'Stok qty', 'Mutasi qty', 'Selisih qty', 'Nilai mutasi', 'Tanpa HPP'],
            collect($report['warehouses'])->map(fn (array $row) => [
                $row['code'],
                $this->number($row['stock_qty']),
                $this->number($row['mutation_qty']),
                $this->number($row['qty_variance']),
                $this->money($row['mutation_value']),
                $this->number($row['unvalued_qty']),
            ])->all()
        );

        $account = $report['account_1204'];
        $this->line(sprintf(
            'Akun 1204: %s | saldo %s | debit %s | credit %s',
            $account['found'] ? ($account['name'] ?? 'aktif') : 'TIDAK DITEMUKAN',
            $this->money($account['balance'] ?? 0),
            $this->money($account['debit'] ?? 0),
            $this->money($account['credit'] ?? 0),
        ));
        $this->line(sprintf(
            'Total terpilih: stok %s | mutasi %s | selisih %s | nilai mutasi %s | selisih ke akun 1204 %s',
            $this->number($report['totals']['stock_qty']),
            $this->number($report['totals']['mutation_qty']),
            $this->number($report['totals']['qty_variance']),
            $this->money($report['totals']['mutation_value']),
            $this->money($report['totals']['account_1204_variance'] ?? 0),
        ));

        if ($report['pair_mismatches'] !== []) {
            $this->newLine();
            $this->warn('Mismatch stok vs mutasi:');
            $this->table(
                ['Gudang', 'SKU', 'Stok', 'Mutasi', 'Selisih'],
                collect($report['pair_mismatches'])->map(fn (array $row) => [
                    $row['warehouse_code'],
                    $row['item_code'],
                    $this->number($row['stock_qty']),
                    $this->number($row['mutation_qty']),
                    $this->number($row['qty_variance']),
                ])->all()
            );
        }

        if ($report['duplicate_sku_groups'] !== []) {
            $this->newLine();
            $this->warn('Kelompok SKU reject ganda:');
            $this->table(
                ['Kunci normalisasi', 'Kode'],
                collect($report['duplicate_sku_groups'])->map(fn (array $row) => [
                    $row['normalized_key'],
                    implode(', ', $row['codes']),
                ])->all()
            );
        }

        $this->newLine();
        if ($report['findings'] === []) {
            $this->info('Status: CLEAN — tidak ada temuan dari pemeriksaan baseline.');
        } else {
            $this->error('Status: REVIEW — ' . count($report['findings']) . ' temuan.');
            foreach ($report['findings'] as $finding) {
                $this->line(sprintf('[%s] %s: %s', strtoupper($finding['severity']), $finding['code'], $finding['message']));
            }
        }
    }

    private function number(float|int|null $value): string
    {
        return number_format((float) $value, 3, ',', '.');
    }

    private function money(float|int|null $value): string
    {
        return 'Rp ' . number_format((float) $value, 2, ',', '.');
    }
}
