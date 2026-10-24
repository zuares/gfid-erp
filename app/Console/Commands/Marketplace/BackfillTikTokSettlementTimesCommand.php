<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class BackfillTikTokSettlementTimesCommand extends Command
{
    protected $signature = 'marketplace:backfill-tiktok-settlement-times
        {--store_id=all : TikTok store ID atau all}
        {--days=365 : Rentang hari ke belakang bila --from tidak diisi}
        {--from= : Tanggal mulai statement (Y-m-d)}
        {--to= : Tanggal akhir statement (Y-m-d), default hari ini}
        {--limit=5000 : Maksimum row kandidat per toko}
        {--apply : Tulis tanggal hasil backfill (default dry-run)}';

    protected $description = 'Backfill tanggal cair TikTok dari Finance statements (default dry-run).';

    public function handle(MarketplaceSyncService $syncService): int
    {
        $storeOption = (string) $this->option('store_id');
        $days = (int) $this->option('days');
        $limit = (int) $this->option('limit');

        if ($days < 1 || $days > 730) {
            $this->error('Opsi --days harus berada di antara 1 dan 730.');

            return self::INVALID;
        }
        if ($limit < 1 || $limit > 50000) {
            $this->error('Opsi --limit harus berada di antara 1 dan 50000.');

            return self::INVALID;
        }

        try {
            $from = $this->option('from')
                ? Carbon::createFromFormat('!Y-m-d', (string) $this->option('from'), config('app.timezone'))
                : now()->subDays($days)->startOfDay();
            $to = $this->option('to')
                ? Carbon::createFromFormat('!Y-m-d', (string) $this->option('to'), config('app.timezone'))->endOfDay()
                : now()->endOfDay();
        } catch (\Throwable) {
            $this->error('Format tanggal harus Y-m-d, contoh: 2026-09-01.');

            return self::INVALID;
        }

        if (! $from || ! $to || $from->greaterThan($to)) {
            $this->error('Rentang tanggal tidak valid.');

            return self::INVALID;
        }

        $query = Store::query()
            ->with('channel')
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($channel) => $channel->whereIn('code', ['tiktok', 'ttk', 'tkt', 'tt']))
            ->when($storeOption !== 'all', fn ($stores) => $stores->whereKey((int) $storeOption));
        $stores = $query->get();

        if ($stores->isEmpty()) {
            $this->error('Tidak ada toko TikTok aktif yang cocok.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $this->info(sprintf(
            '%s · Statement %s s/d %s · %d toko',
            $apply ? 'MODE APPLY' : 'MODE DRY-RUN',
            $from->toDateString(),
            $to->toDateString(),
            $stores->count(),
        ));

        $totals = [
            'scanned' => 0,
            'candidates' => 0,
            'matched' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'missing_statement' => 0,
            'missing_date' => 0,
            'errors' => 0,
        ];

        foreach ($stores as $store) {
            $this->line("Memproses {$store->name} (#{$store->id})...");
            $stats = $syncService->backfillTikTokSettlementTimes($store, $from, $to, $apply, $limit);

            foreach ($totals as $key => $value) {
                $totals[$key] += (int) ($stats[$key] ?? 0);
            }

            $this->table(
                ['Scanned', 'Candidates', 'Matched', 'Updated', 'Unchanged', 'No statement', 'No date', 'Errors'],
                [[
                    $stats['scanned'],
                    $stats['candidates'],
                    $stats['matched'],
                    $stats['updated'],
                    $stats['unchanged'],
                    $stats['missing_statement'],
                    $stats['missing_date'],
                    $stats['errors'],
                ]],
            );

            foreach (array_slice($stats['examples'] ?? [], 0, 5) as $example) {
                $this->line(sprintf(
                    '  %s: %s -> %s (%s)',
                    $example['order_id'],
                    $example['old_time'],
                    $example['new_time'],
                    $example['source'],
                ));
            }
        }

        $this->line('SUMMARY '.json_encode($totals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (! $apply) {
            $this->warn('Dry-run: tidak ada perubahan disimpan. Tambahkan --apply setelah hasil preview sesuai.');
        }

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
