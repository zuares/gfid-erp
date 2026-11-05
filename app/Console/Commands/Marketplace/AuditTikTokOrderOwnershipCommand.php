<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\Marketplace\TikTokShop\TikTokOrderOwnershipAuditService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use InvalidArgumentException;

class AuditTikTokOrderOwnershipCommand extends Command
{
    protected $signature = 'marketplace:audit-tiktok-order-ownership
        {--stores= : Comma-separated TikTok store IDs; defaults to all active TikTok stores}
        {--order= : Audit one TikTok order ID, even if it is not locally duplicated}
        {--limit=0 : Maximum number of duplicated order IDs to audit; 0 means all}
        {--pause-ms=200 : Delay between TikTok API checks}
        {--format=table : Output format: table, json, or csv}
        {--output= : Write the report under storage/app, or use an absolute path}';

    protected $description = 'Read-only audit of TikTok order ownership across stores';

    public function handle(TikTokOrderOwnershipAuditService $audit): int
    {
        try {
            $stores = $this->stores();
            $format = strtolower(trim((string) $this->option('format')));
            if (! in_array($format, ['table', 'json', 'csv'], true)) {
                throw new InvalidArgumentException('--format harus table, json, atau csv.');
            }

            $limit = max(0, (int) $this->option('limit'));
            $pauseMs = max(0, min(5000, (int) $this->option('pause-ms')));
            $orderId = $this->option('order');
            $report = $audit->audit($stores, $orderId !== null ? trim((string) $orderId) : null, $limit, $pauseMs);

            if ($format === 'table') {
                $this->renderTable($report);
            } elseif ($format === 'json') {
                $this->line((string) json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));
            } else {
                $this->line($this->csv($report));
            }

            if ($this->option('output')) {
                if ($format === 'table') {
                    throw new InvalidArgumentException('--output membutuhkan --format=json atau --format=csv.');
                }

                $path = $this->reportPath((string) $this->option('output'));
                File::ensureDirectoryExists(dirname($path));
                File::put($path, $format === 'json'
                    ? json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES).PHP_EOL
                    : $this->csv($report));
                $this->info("Report disimpan: {$path}");
            }

            return self::SUCCESS;
        } catch (InvalidArgumentException $e) {
            $this->error($e->getMessage());

            return self::INVALID;
        } catch (\Throwable $e) {
            $this->error('Audit gagal: '.$e->getMessage());

            return self::FAILURE;
        }
    }

    /**
     * @return \Illuminate\Support\Collection<int, Store>
     */
    private function stores()
    {
        $query = Store::query()
            ->with('channel')
            ->where('is_active', true)
            ->where('status', 'active')
            ->whereHas('channel', fn ($channel) => $channel->whereIn('code', ['tiktok', 'ttk', 'tt']))
            ->orderBy('id');

        $rawStores = trim((string) ($this->option('stores') ?? ''));
        if ($rawStores !== '') {
            $ids = collect(explode(',', $rawStores))
                ->map(fn (string $id): int => (int) trim($id))
                ->filter(fn (int $id): bool => $id > 0)
                ->unique()
                ->values();

            if ($ids->isEmpty()) {
                throw new InvalidArgumentException('--stores harus berisi ID toko numerik.');
            }

            $query->whereIn('id', $ids->all());
        }

        $stores = $query->get();
        if ($stores->isEmpty()) {
            throw new InvalidArgumentException('Tidak ada toko TikTok aktif yang dapat diaudit.');
        }

        return $stores;
    }

    /** @param array<string, mixed> $report */
    private function renderTable(array $report): void
    {
        $summary = $report['summary'];
        $this->info('TikTok order ownership audit (READ-ONLY)');
        $this->line('Toko: '.collect($report['stores'])->map(fn (array $store): string => "{$store['id']}={$store['name']}")->implode(', '));
        $this->line('');
        $this->table(
            ['Order ID', 'Local store', 'API owner', 'Classification', 'Reason'],
            collect($report['findings'])->map(fn (array $finding): array => [
                $finding['channel_order_id'],
                implode(', ', $finding['local_store_ids']),
                collect($finding['api_checks'])
                    ->filter(fn (array $check): bool => $check['status'] === 'found')
                    ->keys()
                    ->implode(', ') ?: '-',
                $finding['classification'],
                $finding['reason'],
            ])->all(),
        );
        $this->line('');
        $this->line('Total: '.($summary['candidate_order_count'] ?? 0)
            .' | confirmed: '.($summary['confirmed_single_store'] ?? 0)
            .' | ambiguous: '.($summary['ambiguous_multiple_stores'] ?? 0)
            .' | API error: '.($summary['api_error'] ?? 0)
            .' | not found: '.($summary['not_found'] ?? 0));
    }

    /** @param array<string, mixed> $report */
    private function csv(array $report): string
    {
        $handle = fopen('php://temp', 'r+');
        fputcsv($handle, [
            'channel_order_id',
            'local_row_ids',
            'local_store_ids',
            'api_owner_store_ids',
            'classification',
            'recommended_store_id',
            'reason',
        ]);

        foreach ($report['findings'] as $finding) {
            fputcsv($handle, [
                $finding['channel_order_id'],
                implode('|', $finding['local_row_ids']),
                implode('|', $finding['local_store_ids']),
                collect($finding['api_checks'])
                    ->filter(fn (array $check): bool => $check['status'] === 'found')
                    ->keys()
                    ->implode('|'),
                $finding['classification'],
                $finding['recommended_store_id'] ?? '',
                $finding['reason'],
            ]);
        }

        rewind($handle);
        $csv = stream_get_contents($handle);
        fclose($handle);

        return (string) $csv;
    }

    private function reportPath(string $output): string
    {
        return str_starts_with($output, '/')
            ? $output
            : storage_path('app/'.ltrim($output, '/'));
    }
}
