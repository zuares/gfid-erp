<?php

namespace App\Console\Commands;

use App\Models\MarketplaceSyncLog;
use Illuminate\Console\Command;

class CompactMarketplaceSyncLogs extends Command
{
    protected $signature = 'marketplace:compact-sync-logs
                            {--keep-order-sns=25 : Jumlah order SN yang dipertahankan per log}
                            {--days=0 : Hanya proses log dalam jumlah hari terakhir; 0 berarti semua}
                            {--dry-run : Hitung kandidat tanpa mengubah data}
                            {--force : Izinkan perubahan data saat environment production}';

    protected $description = 'Padatkan daftar order SN pada payload log sinkronisasi marketplace';

    public function handle(): int
    {
        $sampleSize = max(1, (int) $this->option('keep-order-sns'));
        $days = max(0, (int) $this->option('days'));
        $isDryRun = (bool) $this->option('dry-run');

        if (app()->environment('production') && ! $isDryRun && ! $this->option('force')) {
            $this->error('Compaction di production membutuhkan flag --force. Gunakan --dry-run untuk inspeksi.');

            return self::FAILURE;
        }

        $query = MarketplaceSyncLog::query()
            ->select(['id', 'payload'])
            ->when($days > 0, fn ($q) => $q->where('created_at', '>=', now()->subDays($days)))
            ->orderBy('id');

        $processed = 0;
        $candidates = 0;

        $query->chunkById(500, function ($logs) use ($sampleSize, $isDryRun, &$processed, &$candidates): void {
            foreach ($logs as $log) {
                $processed++;
                $payload = is_array($log->payload) ? $log->payload : [];
                $compact = MarketplaceSyncLog::compactPayload($payload, $sampleSize);

                if ($compact === $payload) {
                    continue;
                }

                $candidates++;
                if ($isDryRun) {
                    continue;
                }

                $log->timestamps = false;
                $log->payload = $compact;
                $log->saveQuietly();
            }
        });

        if ($isDryRun) {
            $this->info(sprintf(
                '%d kandidat dari %d log akan dipadatkan menjadi maksimal %d order SN per payload.',
                $candidates,
                $processed,
                $sampleSize
            ));

            return self::SUCCESS;
        }

        $this->info(sprintf(
            '%d log dipadatkan dari %d log yang diperiksa. Jalankan VACUUM terpisah jika ingin mengecilkan file SQLite.',
            $candidates,
            $processed
        ));

        return self::SUCCESS;
    }
}
