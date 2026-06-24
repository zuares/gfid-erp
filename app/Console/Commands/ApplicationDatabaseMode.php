<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

class ApplicationDatabaseMode extends Command
{
    protected $signature = 'app:mode
        {mode : Mode database: status, dev, atau ops}
        {--ops-db= : Path database operasional custom}
        {--dev-db= : Path database development custom}
        {--no-copy : Untuk mode dev, jangan copy DB operasional ke DB dev}
        {--from-current : Untuk mode dev, copy dari DB yang sedang aktif}
        {--init-from-current : Untuk mode ops, buat DB operasional dari DB yang sedang aktif}
        {--force : Tetap lanjut walau database sumber kosong/tidak umum}';

    protected $description = 'Switch database global aplikasi antara operasional dan development.';

    public function handle(): int
    {
        $mode = strtolower((string) $this->argument('mode'));
        if (! in_array($mode, ['status', 'dev', 'ops'], true)) {
            $this->error('Mode harus: status, dev, atau ops.');
            $this->line('Contoh: php artisan app:mode status');
            $this->line('Contoh: php artisan app:mode dev');
            $this->line('Contoh: php artisan app:mode ops');
            return self::FAILURE;
        }

        $opsDb = $this->normalizePath((string) ($this->option('ops-db') ?: env('GFID_OPS_DB') ?: database_path('database.sqlite')));
        $devDb = $this->normalizePath((string) ($this->option('dev-db') ?: env('GFID_DEV_DB') ?: base_path('database_dev.sqlite')));
        $currentDb = $this->normalizePath((string) (config('database.connections.sqlite.database') ?: database_path('database.sqlite')));

        if ($mode === 'status') {
            $this->printStatus($currentDb, $opsDb, $devDb);
            return self::SUCCESS;
        }

        if (config('database.default') !== 'sqlite') {
            $this->error('app:mode saat ini hanya mendukung DB_CONNECTION=sqlite.');
            return self::FAILURE;
        }

        if ($mode === 'dev') {
            return $this->switchToDev($currentDb, $opsDb, $devDb);
        }

        return $this->switchToOps($currentDb, $opsDb, $devDb);
    }

    private function switchToDev(string $currentDb, string $opsDb, string $devDb): int
    {
        $source = $this->option('from-current') ? $currentDb : $opsDb;

        $this->line('=== Switch ke Database Development ===');
        $this->line('Source : ' . $source);
        $this->line('Target : ' . $devDb);
        $this->newLine();

        if (! $this->option('no-copy')) {
            if (! $this->assertReadableDatabase($source, 'database sumber')) {
                return self::FAILURE;
            }

            if (! $this->option('force') && $this->samePath($source, $devDb)) {
                $this->warn('Source dan target dev sama. Copy dilewati.');
            } else {
                $this->backupIfExists($devDb, 'dev');
                File::ensureDirectoryExists(dirname($devDb));
                File::copy($source, $devDb);
                $this->info('Database development diperbarui dari source.');
            }
        } elseif (! File::exists($devDb)) {
            $this->error('Database dev belum ada. Jalankan tanpa --no-copy atau buat file ini dulu:');
            $this->line($devDb);
            return self::FAILURE;
        }

        if (! $this->assertReadableDatabase($devDb, 'database dev')) {
            return self::FAILURE;
        }

        if (! $this->writeEnvDatabase($devDb, 'dev', $opsDb, $devDb)) {
            return self::FAILURE;
        }

        $this->clearLaravelCache();
        $this->info('Mode sekarang: DEV');
        $this->line('DB_DATABASE=' . $devDb);

        return self::SUCCESS;
    }

    private function switchToOps(string $currentDb, string $opsDb, string $devDb): int
    {
        $this->line('=== Switch ke Database Operasional ===');
        $this->line('Target : ' . $opsDb);
        $this->newLine();

        if ($this->option('init-from-current')) {
            $this->line('Init OPS dari DB aktif: ' . $currentDb);

            if (! $this->assertReadableDatabase($currentDb, 'database aktif')) {
                return self::FAILURE;
            }

            if ($this->samePath($currentDb, $opsDb)) {
                $this->warn('DB aktif sudah sama dengan target OPS. Copy dilewati.');
            } else {
                $this->backupIfExists($opsDb, 'ops');
                File::ensureDirectoryExists(dirname($opsDb));
                File::copy($currentDb, $opsDb);
                $this->info('Database operasional dibuat dari DB aktif.');
            }
        }

        if (! $this->assertReadableDatabase($opsDb, 'database operasional')) {
            $this->warn('Kalau database operasional kamu bukan database/database.sqlite, jalankan:');
            $this->line('php artisan app:mode ops --ops-db=/path/ke/database_operasional.sqlite');
            $this->line('');
            $this->warn('Kalau DB aktif sekarang memang mau dijadikan operasional, jalankan:');
            $this->line('php artisan app:mode ops --init-from-current');
            return self::FAILURE;
        }

        if (! $this->writeEnvDatabase($opsDb, 'ops', $opsDb, $devDb)) {
            return self::FAILURE;
        }

        $this->clearLaravelCache();
        $this->info('Mode sekarang: OPS / OPERASIONAL');
        $this->line('DB_DATABASE=' . $opsDb);

        return self::SUCCESS;
    }

    private function printStatus(string $currentDb, string $opsDb, string $devDb): void
    {
        $this->line('=== Status Database Aplikasi ===');
        $this->line('Mode .env : ' . (env('APP_DB_MODE') ?: '-'));
        $this->line('Aktif     : ' . $currentDb . ' ' . $this->fileMeta($currentDb));
        $this->line('Ops       : ' . $opsDb . ' ' . $this->fileMeta($opsDb));
        $this->line('Dev       : ' . $devDb . ' ' . $this->fileMeta($devDb));
    }

    private function assertReadableDatabase(string $path, string $label): bool
    {
        if (! File::exists($path)) {
            $this->error(ucfirst($label) . ' tidak ditemukan: ' . $path);
            return false;
        }

        $size = File::size($path);
        if ($size <= 0 && ! $this->option('force')) {
            $this->error(ucfirst($label) . ' kosong: ' . $path);
            $this->line('Gunakan --force hanya kalau memang sengaja pakai DB kosong.');
            return false;
        }

        return true;
    }

    private function backupIfExists(string $path, string $label): void
    {
        if (! File::exists($path)) {
            return;
        }

        $backupDir = storage_path('backups/app_modes');
        File::ensureDirectoryExists($backupDir);

        $backup = $backupDir . '/' . $label . '_' . now()->format('Ymd_His_u') . '.sqlite';
        File::copy($path, $backup);
        $this->line('Backup DB lama: ' . $backup);
    }

    private function writeEnvDatabase(string $databasePath, string $mode, string $opsDb, string $devDb): bool
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            $this->error('.env tidak ditemukan.');
            return false;
        }

        $content = File::get($envPath);
        $content = $this->setEnvLine($content, 'DB_CONNECTION', 'sqlite');
        $content = $this->setEnvLine($content, 'DB_DATABASE', $databasePath);
        $content = $this->setEnvLine($content, 'APP_DB_MODE', $mode);
        $content = $this->setEnvLine($content, 'GFID_OPS_DB', $opsDb);
        $content = $this->setEnvLine($content, 'GFID_DEV_DB', $devDb);

        File::put($envPath, $content);
        return true;
    }

    private function setEnvLine(string $content, string $key, string $value): string
    {
        $line = $key . '=' . $value;

        if (preg_match('/^' . preg_quote($key, '/') . '=.*$/m', $content)) {
            return preg_replace('/^' . preg_quote($key, '/') . '=.*$/m', $line, $content);
        }

        return rtrim($content) . PHP_EOL . $line . PHP_EOL;
    }

    private function clearLaravelCache(): void
    {
        Artisan::call('optimize:clear');
        $output = trim(Artisan::output());
        if ($output !== '') {
            $this->line($output);
        }
    }

    private function normalizePath(string $path): string
    {
        $path = trim($path);
        if ($path === '') {
            return database_path('database.sqlite');
        }

        if (str_starts_with($path, './')) {
            $path = base_path(substr($path, 2));
        } elseif (! str_starts_with($path, '/')) {
            $path = base_path($path);
        }

        return realpath($path) ?: $path;
    }

    private function samePath(string $a, string $b): bool
    {
        return $this->normalizePath($a) === $this->normalizePath($b);
    }

    private function fileMeta(string $path): string
    {
        if (! File::exists($path)) {
            return '(tidak ada)';
        }

        return '(' . number_format(File::size($path) / 1024 / 1024, 2) . ' MB)';
    }
}
