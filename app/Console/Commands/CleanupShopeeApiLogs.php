<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

class CleanupShopeeApiLogs extends Command
{
    protected $signature = 'shopee:cleanup-api-logs
                            {--days=14 : Pertahankan log selama jumlah hari ini}
                            {--redact-days=1 : Payload mentah dipertahankan selama jumlah hari ini}
                            {--dry-run : Hanya tampilkan jumlah data yang akan diproses}
                            {--force : Izinkan perubahan data saat environment production}
                            {--vacuum : Jalankan VACUUM SQLite setelah cleanup}';

    protected $description = 'Hapus log API Shopee yang melewati masa retensi';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $redactDays = max(0, (int) $this->option('redact-days'));
        $connection = DB::connection(config('database.shopee_api_log_connection', 'sqlite'));
        $schema = Schema::connection($connection->getName());

        if ($redactDays >= $days) {
            $this->error('--redact-days harus lebih kecil dari --days.');

            return self::FAILURE;
        }

        if (! $schema->hasTable('shopee_api_logs')) {
            $this->warn('Tabel shopee_api_logs belum tersedia; tidak ada yang diproses.');

            return self::SUCCESS;
        }

        $isDryRun = (bool) $this->option('dry-run');
        if (app()->environment('production') && ! $isDryRun && ! $this->option('force')) {
            $this->error('Cleanup di production membutuhkan flag --force. Gunakan --dry-run untuk inspeksi.');

            return self::FAILURE;
        }

        $driver = $connection->getDriverName();
        if ($driver === 'sqlite' && ! $this->sqliteIsHealthy($connection)) {
            $this->error('Database SQLite tidak sehat; cleanup dibatalkan. Backup/repair database terlebih dahulu.');

            return self::FAILURE;
        }

        $redactCutoff = now()->subDays($redactDays);
        $deleteCutoff = now()->subDays($days);
        $payloadQuery = $connection->table('shopee_api_logs')
            ->whereNotNull('created_at')
            ->where('created_at', '<', $redactCutoff)
            ->where(function ($query) {
                $query->whereNotNull('request_payload')
                    ->orWhereNotNull('response_payload');
            });
        $deleteQuery = $connection->table('shopee_api_logs')
            ->whereNotNull('created_at')
            ->where('created_at', '<', $deleteCutoff);

        $payloadCount = $payloadQuery->count();
        $deleteCount = $deleteQuery->count();

        if ($isDryRun) {
            $this->table(
                ['Aksi', 'Jumlah', 'Batas'],
                [
                    ['Redaksi payload + sanitasi URL', $payloadCount, $redactCutoff->toDateTimeString()],
                    ['Hapus baris log', $deleteCount, $deleteCutoff->toDateTimeString()],
                ]
            );

            return self::SUCCESS;
        }

        $redacted = $payloadQuery->update($this->sanitizedPayloadUpdate($driver));
        $deleted = $deleteQuery->delete();

        if ($this->option('vacuum')) {
            if ($driver !== 'sqlite') {
                $this->warn('--vacuum hanya didukung untuk SQLite; dilewati.');
            } else {
                $this->warn('Menjalankan VACUUM; database dapat terkunci sementara.');
                $connection->statement('VACUUM');
            }
        }

        if ($driver === 'sqlite' && ! $this->sqliteIsHealthy($connection)) {
            $this->error('Cleanup selesai tetapi pemeriksaan integritas akhir gagal. Hentikan worker dan pulihkan backup.');

            return self::FAILURE;
        }

        $this->info(sprintf(
            '%d payload dirahasiakan, %d log dihapus. Retensi baris: %d hari; retensi payload: %d hari.',
            $redacted,
            $deleted,
            $days,
            $redactDays
        ));

        return self::SUCCESS;
    }

    private function sqliteIsHealthy($connection): bool
    {
        try {
            return ($connection->selectOne('PRAGMA quick_check')->quick_check ?? null) === 'ok';
        } catch (\Throwable) {
            return false;
        }
    }

    /**
     * Null-kan payload dan hilangkan query string/token dari endpoint lama.
     * Ekspresi endpoint disesuaikan dengan driver database.
     */
    private function sanitizedPayloadUpdate(string $driver): array
    {
        $endpoint = match ($driver) {
            'sqlite' => "CASE WHEN instr(endpoint, '?') > 0 THEN substr(endpoint, 1, instr(endpoint, '?') - 1) ELSE endpoint END",
            'mysql', 'mariadb' => "SUBSTRING_INDEX(endpoint, '?', 1)",
            'pgsql' => "split_part(endpoint, '?', 1)",
            default => null,
        };

        return array_filter([
            'endpoint' => $endpoint ? DB::raw($endpoint) : null,
            'request_payload' => null,
            'response_payload' => null,
        ], static fn ($value) => $value !== null);
    }
}
