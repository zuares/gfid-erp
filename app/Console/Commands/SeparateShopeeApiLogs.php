<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Database\Connection;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;

class SeparateShopeeApiLogs extends Command
{
    protected $signature = 'shopee:separate-api-logs
                            {--dry-run : Hanya tampilkan rencana dan jumlah data}
                            {--force : Izinkan perubahan data saat environment production}
                            {--activate : Aktifkan koneksi logs setelah copy berhasil}
                            {--remove-source : Hapus tabel log dari database utama setelah aktivasi}
                            {--batch=500 : Jumlah baris per batch saat copy}';

    protected $description = 'Siapkan dan pindahkan shopee_api_logs ke database SQLite terpisah';

    public function handle(): int
    {
        $dryRun = (bool) $this->option('dry-run');
        $activate = (bool) $this->option('activate');
        $removeSource = (bool) $this->option('remove-source');
        $batchSize = max(50, (int) $this->option('batch'));

        if (app()->environment('production') && ! $dryRun && ! $this->option('force')) {
            $this->error('Pemisahan log di production membutuhkan flag --force.');

            return self::FAILURE;
        }

        if (($activate || $removeSource) && ! $this->option('force')) {
            $this->error('--activate dan --remove-source membutuhkan --force.');

            return self::FAILURE;
        }

        if ($removeSource && ! $activate) {
            $this->error('--remove-source hanya boleh digunakan bersama --activate.');

            return self::FAILURE;
        }

        $source = DB::connection('sqlite');
        $target = DB::connection('logs');
        $sourcePath = (string) config('database.connections.sqlite.database');
        $targetPath = (string) config('database.connections.logs.database');

        if ($this->sameDatabase($sourcePath, $targetPath)) {
            $this->error('Database sumber dan target sama. Periksa DB_LOGS_DATABASE.');

            return self::FAILURE;
        }

        if (! Schema::connection('sqlite')->hasTable('shopee_api_logs')) {
            $this->warn('Tabel shopee_api_logs tidak ditemukan di database utama.');

            return self::SUCCESS;
        }

        $sourceCount = $source->table('shopee_api_logs')->count();
        $sourceMaxId = $source->table('shopee_api_logs')->max('id');
        $targetHasTable = File::exists($targetPath)
            && Schema::connection('logs')->hasTable('shopee_api_logs');
        $targetCount = $targetHasTable
            ? $target->table('shopee_api_logs')->count()
            : 0;
        $targetMaxId = $targetHasTable
            ? $target->table('shopee_api_logs')->max('id')
            : null;

        $this->table(
            ['Item', 'Nilai'],
            [
                ['Database sumber', $sourcePath],
                ['Database target', $targetPath],
                ['Koneksi aktif saat ini', config('database.shopee_api_log_connection', 'sqlite')],
                ['Log di sumber', $sourceCount],
                ['Log di target', $targetCount],
                ['ID terakhir sumber', $sourceMaxId ?? '-'],
                ['ID terakhir target', $targetMaxId ?? '-'],
            ]
        );

        if ($dryRun) {
            $this->info('Dry-run selesai; tidak ada data atau konfigurasi yang diubah.');

            return self::SUCCESS;
        }

        $this->ensureTargetTable($target);

        $copied = $this->copyRows($source, $target, $batchSize);
        $sourceCountAfter = $source->table('shopee_api_logs')->count();
        $targetCountAfter = $target->table('shopee_api_logs')->count();
        $sourceMaxIdAfter = $source->table('shopee_api_logs')->max('id');
        $targetMaxIdAfter = $target->table('shopee_api_logs')->max('id');

        $this->info(sprintf(
            '%d baris diproses. Sumber: %d, target: %d.',
            $copied,
            $sourceCountAfter,
            $targetCountAfter
        ));

        if ($targetCountAfter < $sourceCountAfter || $targetMaxIdAfter < $sourceMaxIdAfter) {
            $this->error('Verifikasi gagal: target belum mencakup seluruh sumber. Aktivasi dibatalkan.');
            $this->warn('Jalankan ulang saat traffic log dihentikan sementara agar tidak ada baris baru saat copy.');

            return self::FAILURE;
        }

        if ($activate) {
            $this->activateLogsConnection();
            $this->info('Koneksi log diaktifkan melalui SHOPEE_API_LOG_CONNECTION=logs.');
            $this->warn('Jalankan "php artisan optimize:clear" dan restart worker setelah command selesai.');
        }

        if ($removeSource) {
            Schema::connection('sqlite')->dropIfExists('shopee_api_logs');
            $this->warn('Tabel shopee_api_logs di database utama telah dihapus.');
            $this->warn('Jalankan VACUUM database utama setelah memastikan aplikasi normal.');
        }

        return self::SUCCESS;
    }

    private function ensureTargetTable(Connection $target): void
    {
        $targetPath = (string) config('database.connections.logs.database');
        File::ensureDirectoryExists(dirname($targetPath));

        // Laravel tidak membuat file SQLite kosong sebelum Schema::hasTable().
        if (! File::exists($targetPath)) {
            File::put($targetPath, '');
        }

        $schema = Schema::connection('logs');

        if (! $schema->hasTable('shopee_api_logs')) {
            $schema->create('shopee_api_logs', function (Blueprint $table): void {
                $table->id();
                $table->string('method');
                $table->string('endpoint');
                $table->longText('request_payload')->nullable();
                $table->longText('response_payload')->nullable();
                $table->integer('status_code')->nullable();
                $table->float('duration')->nullable();
                $table->timestamps();
                $table->index('created_at', 'shopee_api_logs_created_at_index');
            });

            $this->info('Database target dan tabel shopee_api_logs berhasil dibuat.');
        }
    }

    private function copyRows(Connection $source, Connection $target, int $batchSize): int
    {
        $copied = 0;
        $columns = [
            'id',
            'method',
            'endpoint',
            'request_payload',
            'response_payload',
            'status_code',
            'duration',
            'created_at',
            'updated_at',
        ];

        $source->table('shopee_api_logs')
            ->select($columns)
            ->orderBy('id')
            ->chunkById($batchSize, function ($rows) use ($target, &$copied): void {
                $values = $rows->map(static fn ($row): array => (array) $row)->all();
                $copied += $target->table('shopee_api_logs')->insertOrIgnore($values);
            }, 'id', 'id');

        return $copied;
    }

    private function activateLogsConnection(): void
    {
        $envPath = base_path('.env');

        if (! File::exists($envPath)) {
            throw new \RuntimeException('.env tidak ditemukan; aktivasi dibatalkan.');
        }

        $contents = File::get($envPath);
        $line = 'SHOPEE_API_LOG_CONNECTION=logs';

        if (preg_match('/^SHOPEE_API_LOG_CONNECTION=.*$/m', $contents)) {
            $contents = preg_replace('/^SHOPEE_API_LOG_CONNECTION=.*$/m', $line, $contents);
        } else {
            $contents = rtrim($contents) . PHP_EOL . $line . PHP_EOL;
        }

        File::put($envPath, $contents);
    }

    private function sameDatabase(string $sourcePath, string $targetPath): bool
    {
        $source = realpath($sourcePath) ?: $sourcePath;
        $target = realpath($targetPath) ?: $targetPath;

        return $source === $target;
    }
}
