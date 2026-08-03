<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class PullProductionDatabase extends Command
{
    protected $signature = 'db:pull-prod
        {--source= : File SQLite production/backup lokal}
        {--ssh= : Host SSH, contoh deploy@example.com}
        {--remote-path= : Path database SQLite production di host SSH}
        {--target= : Path database dev tujuan, default database aktif}
        {--force : Lewati konfirmasi dan izinkan environment production}
        {--no-backup : Jangan backup database dev sebelum ditimpa}';

    protected $description = 'Ambil snapshot SQLite production dan pasang sebagai database development';

    public function handle(): int
    {
        if (app()->environment('production') && ! $this->option('force')) {
            $this->error('Dibatalkan: command ini tidak boleh dijalankan di environment production.');
            $this->line('Jika benar-benar diperlukan, gunakan --force.');

            return self::FAILURE;
        }

        if (config('database.default') !== 'sqlite') {
            $this->error('db:pull-prod saat ini hanya mendukung database SQLite.');

            return self::FAILURE;
        }

        $target = $this->targetPath();
        if ($target === null) {
            return self::FAILURE;
        }

        $source = trim((string) $this->option('source'));
        $ssh = trim((string) $this->option('ssh'));
        $remotePath = trim((string) $this->option('remote-path'));

        if (($ssh === '') !== ($remotePath === '')) {
            $this->error('Gunakan --ssh dan --remote-path secara bersamaan.');

            return self::FAILURE;
        }

        if ($source === '' && $ssh === '') {
            $this->error('Sumber database belum diisi.');
            $this->line('Lokal : php artisan db:pull-prod --source=/path/backup-prod.sqlite');
            $this->line('SSH   : php artisan db:pull-prod --ssh=deploy@example.com --remote-path=/var/www/app/database/database.sqlite');

            return self::FAILURE;
        }

        if ($source !== '' && $ssh !== '') {
            $this->error('Pilih salah satu: --source atau pasangan --ssh + --remote-path.');

            return self::FAILURE;
        }

        $backupDir = storage_path('backups');
        File::ensureDirectoryExists($backupDir);
        $staged = storage_path('app/private/.db-pull-' . Str::uuid() . '.sqlite');
        $remoteSnapshot = '/tmp/gfid-db-pull-' . Str::uuid() . '.sqlite';
        $remoteWasCreated = false;

        try {
            if ($ssh !== '') {
                $this->info("Membuat snapshot SQLite production via SSH: {$ssh}");
                if (! $this->createRemoteSnapshot($ssh, $remotePath, $remoteSnapshot)) {
                    return self::FAILURE;
                }
                $remoteWasCreated = true;

                if (! $this->runProcess(['scp', "{$ssh}:{$remoteSnapshot}", $staged])) {
                    return self::FAILURE;
                }
            } else {
                $sourcePath = $this->resolveLocalPath($source);
                if ($sourcePath === null) {
                    return self::FAILURE;
                }

                $this->info('Menyalin file database production/backup lokal.');
                if (! File::copy($sourcePath, $staged)) {
                    $this->error('Gagal menyalin sumber database ke file sementara.');

                    return self::FAILURE;
                }
            }

            if (! File::exists($staged) || File::size($staged) === 0) {
                $this->error('Snapshot database kosong atau tidak berhasil diambil.');

                return self::FAILURE;
            }

            $this->line("Target dev: {$target}");
            $this->warn('Data production dapat berisi kredensial, token, dan data pelanggan.');

            if (! $this->option('force') && ! $this->confirm('Timpa database development dengan snapshot ini?')) {
                $this->info('Dibatalkan.');

                return self::SUCCESS;
            }

            if (! $this->option('no-backup') && File::exists($target)) {
                $safetyBackup = $backupDir . '/before_pull_prod_' . now()->format('Ymd_His') . '.sqlite';
                if (! File::copy($target, $safetyBackup)) {
                    $this->error('Backup database dev gagal dibuat; proses dibatalkan.');

                    return self::FAILURE;
                }
                $this->info('Backup dev: ' . $safetyBackup);
            }

            File::ensureDirectoryExists(dirname($target));
            $replacement = $target . '.pulling-' . Str::uuid();
            if (! File::copy($staged, $replacement)) {
                $this->error('Gagal menyiapkan database pengganti.');

                return self::FAILURE;
            }

            if (! rename($replacement, $target)) {
                File::delete($replacement);
                $this->error('Gagal memasang database dev yang baru.');

                return self::FAILURE;
            }

            $this->call('optimize:clear');
            $this->info('Database production berhasil dipasang sebagai database development.');

            return self::SUCCESS;
        } finally {
            File::delete($staged);

            if ($remoteWasCreated) {
                $this->runProcess(['ssh', $ssh, 'rm', '-f', '--', $remoteSnapshot], true);
            }
        }
    }

    private function targetPath(): ?string
    {
        $target = trim((string) $this->option('target'));
        if ($target === '') {
            $target = (string) config('database.connections.sqlite.database');
        }

        if ($target === '' || $target === ':memory:') {
            $this->error('Database target SQLite tidak valid.');

            return null;
        }

        return $this->absolutePath($target);
    }

    private function resolveLocalPath(string $path): ?string
    {
        $resolved = $this->absolutePath($path);
        if (! File::exists($resolved) || ! File::isReadable($resolved)) {
            $this->error("File sumber tidak ditemukan atau tidak bisa dibaca: {$resolved}");

            return null;
        }

        return $resolved;
    }

    private function absolutePath(string $path): string
    {
        if (str_starts_with($path, DIRECTORY_SEPARATOR)) {
            return $path;
        }

        return base_path($path);
    }

    private function createRemoteSnapshot(string $ssh, string $remotePath, string $remoteSnapshot): bool
    {
        if (str_contains($remotePath, "\n") || str_contains($remotePath, "\r")) {
            $this->error('Remote path tidak valid.');

            return false;
        }

        $backupCommand = sprintf(
            'sqlite3 --bail %s %s',
            escapeshellarg($remotePath),
            escapeshellarg('.backup ' . $remoteSnapshot),
        );

        return $this->runProcess(['ssh', $ssh, $backupCommand]);
    }

    private function runProcess(array $command, bool $quiet = false): bool
    {
        $process = new Process($command, base_path());
        $process->setTimeout(null);
        $process->run(function (string $type, string $buffer) use ($quiet): void {
            if (! $quiet && trim($buffer) !== '') {
                $this->line(trim($buffer));
            }
        });

        if (! $process->isSuccessful()) {
            if (! $quiet && trim($process->getErrorOutput()) !== '') {
                $this->error(trim($process->getErrorOutput()));
            }
            $this->error('Perintah eksternal gagal: ' . $process->getCommandLine());
        }

        return $process->isSuccessful();
    }
}
