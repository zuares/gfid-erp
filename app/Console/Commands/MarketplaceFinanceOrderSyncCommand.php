<?php

namespace App\Console\Commands;

use App\Models\MarketplaceFinanceSyncRun;
use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;

class MarketplaceFinanceOrderSyncCommand extends Command
{
    private const SOURCES = ['order_settlements', 'income_estimates', 'mp_incomes'];

    protected $signature = 'marketplace:finance-order-sync
                            {--store=Greatfit.id : Nama toko Shopee yang persis, bukan ID lintas lingkungan}
                            {--apply : Tulis hasil sinkronisasi order; default hanya dry-run}
                            {--trigger=manual : Sumber eksekusi: manual atau schedule}';

    protected $description = 'Sinkronkan Finance per order untuk satu toko tanpa payout, settlement bank, atau jurnal';

    public function handle(MarketplaceFinanceBackfillService $backfill): int
    {
        $name = trim((string) $this->option('store'));
        $trigger = strtolower(trim((string) $this->option('trigger')));
        if (! in_array($trigger, ['manual', 'schedule'], true)) {
            $this->error('Opsi --trigger harus manual atau schedule.');

            return self::INVALID;
        }

        $stores = Store::query()
            ->with('channel')
            ->where('name', $name)
            ->where('status', 'active')
            ->where('is_active', true)
            ->limit(2)
            ->get();
        if ($name === '' || $stores->count() !== 1 || strtolower((string) $stores->first()->channel?->code) !== 'shopee') {
            $this->error('Toko harus cocok persis dengan satu toko Shopee yang terdaftar.');

            return self::INVALID;
        }

        $store = $stores->first();
        $apply = (bool) $this->option('apply');
        $run = MarketplaceFinanceSyncRun::create([
            'trigger' => $trigger === 'schedule' ? 'order_schedule' : 'order_manual',
            'status' => 'processing',
            'started_at' => now(),
        ]);
        $audit = [
            'scope' => 'order_only',
            'store_id' => $store->id,
            'store_name' => $store->name,
            'sources' => self::SOURCES,
            'mode' => $apply ? 'apply' : 'dry-run',
        ];

        try {
            $this->line(sprintf(
                'Toko: %s (ID lingkungan ini: %d) | %s | order-only, tanpa payout/settlement bank/jurnal',
                $store->name,
                $store->id,
                $apply ? 'APPLY' : 'DRY-RUN',
            ));

            $preflight = $backfill->run([
                'dry_run' => true,
                'store_id' => $store->id,
                'sources' => self::SOURCES,
            ]);
            $audit['preflight'] = $preflight['summary'];
            $this->line('Preflight: '.json_encode($preflight['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (! $this->clean($preflight['summary'])) {
                return $this->failRun($run, $audit, 'Preflight memiliki error, duplikat, atau sumber tanpa pasangan.');
            }

            if (! $apply) {
                $this->info('DRY-RUN bersih: hanya audit run dicatat; transaksi Finance, payout, settlement, bank, dan jurnal tidak diubah.');
                $this->completeRun($run, $audit);

                return self::SUCCESS;
            }

            $result = $backfill->run([
                'dry_run' => false,
                'store_id' => $store->id,
                'sources' => self::SOURCES,
            ]);
            $audit['apply'] = $result['summary'];
            $this->line('Apply: '.json_encode($result['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (! $this->clean($result['summary'])) {
                return $this->failRun($run, $audit, 'Apply belum bersih; periksa audit run sebelum eksekusi berikutnya.');
            }

            $verification = $backfill->run([
                'dry_run' => true,
                'store_id' => $store->id,
                'sources' => self::SOURCES,
            ]);
            $audit['verification'] = $verification['summary'];
            $this->line('Verifikasi: '.json_encode($verification['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            if (! $this->clean($verification['summary'])
                || (int) $verification['summary']['created'] !== 0
                || (int) $verification['summary']['updated'] !== 0) {
                return $this->failRun($run, $audit, 'Verifikasi idempotensi belum bersih.');
            }

            $this->info('Sinkronisasi order-only selesai. Payout, settlement bank, alokasi, dan jurnal tidak diproses.');
            $this->completeRun($run, $audit);

            return self::SUCCESS;
        } catch (\Throwable $exception) {
            $audit['exception'] = class_basename($exception);
            $this->failRun($run, $audit, 'Sinkronisasi order-only gagal: '.$exception->getMessage());

            return self::FAILURE;
        }
    }

    /** @param array<string,mixed> $summary */
    private function clean(array $summary): bool
    {
        return (int) ($summary['errors'] ?? 0) === 0
            && (int) ($summary['duplicates'] ?? 0) === 0
            && (int) ($summary['unmatched'] ?? 0) === 0;
    }

    /** @param array<string,mixed> $audit */
    private function completeRun(MarketplaceFinanceSyncRun $run, array $audit): void
    {
        $run->update([
            'status' => 'success',
            'output' => json_encode($audit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'finished_at' => now(),
        ]);
        Log::info('[marketplace-finance-order-sync] completed', $audit);
    }

    /** @param array<string,mixed> $audit */
    private function failRun(MarketplaceFinanceSyncRun $run, array $audit, string $message): int
    {
        $run->update([
            'status' => 'error',
            'error_message' => mb_substr($message, 0, 1000),
            'output' => json_encode($audit, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'finished_at' => now(),
        ]);
        Log::warning('[marketplace-finance-order-sync] failed', $audit + ['message' => $message]);
        $this->error($message);

        return self::FAILURE;
    }
}
