<?php

namespace App\Jobs;

use App\Models\MarketplaceOrder;
use App\Services\Marketplace\MarketplaceFinancialDataQualityService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class MarketplaceRefreshDataQualityJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public int $timeout = 1800;

    public int $tries = 1;

    public function __construct(
        public string $storeId,
        public bool $dryRun = false,
    ) {
        $this->onQueue('marketplace-quality');
    }

    public static function cacheKey(string $storeId): string
    {
        return 'marketplace:quality_audit:' . $storeId;
    }

    public function handle(MarketplaceFinancialDataQualityService $quality): void
    {
        $cacheKey = self::cacheKey($this->storeId);

        $query = MarketplaceOrder::query()
            ->with(['settlement', 'items'])
            ->when($this->storeId !== 'all', fn ($query) => $query->where('store_id', (int) $this->storeId));

        $totalOrders = (clone $query)->count();

        Cache::put($cacheKey, [
            'status' => 'processing',
            'store_id' => $this->storeId,
            'dry_run' => $this->dryRun,
            'started_at' => now()->toIso8601String(),
            'total_orders' => $totalOrders,
            'processed_orders' => 0,
            'counts' => [
                'ready' => 0,
                'incomplete' => 0,
                'not_applicable' => 0,
                'unknown' => 0,
            ],
            'errors' => [],
            'message' => 'Sedang memeriksa data keuangan (' . $totalOrders . ' order)...',
        ], 3600);

        $counts = [
            'ready' => 0,
            'incomplete' => 0,
            'not_applicable' => 0,
            'unknown' => 0,
        ];
        $errors = [];
        $processed = 0;

        $query->orderBy('id')->chunkById(200, function ($orders) use ($quality, &$counts, &$errors, &$processed, $totalOrders, $cacheKey): void {
            foreach ($orders as $order) {
                try {
                    $assessment = $this->dryRun
                        ? $quality->assessOrder($order)
                        : $quality->refreshOrder($order);

                    $st = $assessment['status'] ?? 'unknown';
                    $counts[$st] = ($counts[$st] ?? 0) + 1;
                } catch (\Throwable $e) {
                    Log::error("Error assessing order #{$order->id} ({$order->channel_order_id}): " . $e->getMessage(), [
                        'order_id' => $order->id,
                        'store_id' => $this->storeId,
                        'exception' => $e,
                    ]);

                    $errors[] = [
                        'order_id' => $order->id,
                        'order_sn' => $order->channel_order_id ?: $order->external_order_id,
                        'message' => $e->getMessage(),
                    ];
                }

                $processed++;
            }

            Cache::put($cacheKey, [
                'status' => 'processing',
                'store_id' => $this->storeId,
                'dry_run' => $this->dryRun,
                'started_at' => now()->toIso8601String(),
                'total_orders' => $totalOrders,
                'processed_orders' => $processed,
                'counts' => $counts,
                'errors' => array_slice($errors, 0, 10),
                'message' => sprintf('Sedang memeriksa data: %d/%d order selesai...', $processed, $totalOrders),
            ], 3600);
        });

        $modeText = $this->dryRun ? 'Audit dry-run selesai' : 'Status kualitas data berhasil disimpan';
        $summaryMessage = sprintf(
            '%s. %d order diperiksa: %d ready, %d incomplete, %d not applicable, %d unknown.%s',
            $modeText,
            $processed,
            $counts['ready'] ?? 0,
            $counts['incomplete'] ?? 0,
            $counts['not_applicable'] ?? 0,
            $counts['unknown'] ?? 0,
            count($errors) > 0 ? sprintf(' (%d order gagal diperiksa)', count($errors)) : '',
        );

        Cache::put($cacheKey, [
            'status' => 'completed',
            'store_id' => $this->storeId,
            'dry_run' => $this->dryRun,
            'finished_at' => now()->toIso8601String(),
            'total_orders' => $totalOrders,
            'processed_orders' => $processed,
            'counts' => $counts,
            'errors' => array_slice($errors, 0, 10),
            'has_errors' => count($errors) > 0,
            'message' => $summaryMessage,
        ], 3600);
    }

    public function failed(\Throwable $exception): void
    {
        $cacheKey = self::cacheKey($this->storeId);

        Log::error("MarketplaceRefreshDataQualityJob failed for store {$this->storeId}: " . $exception->getMessage(), [
            'store_id' => $this->storeId,
            'exception' => $exception,
        ]);

        Cache::put($cacheKey, [
            'status' => 'failed',
            'store_id' => $this->storeId,
            'dry_run' => $this->dryRun,
            'failed_at' => now()->toIso8601String(),
            'error' => $exception->getMessage(),
            'message' => 'Audit kualitas data gagal: ' . $exception->getMessage(),
        ], 3600);
    }
}
