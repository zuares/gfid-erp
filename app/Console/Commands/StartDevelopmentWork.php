<?php

namespace App\Console\Commands;

use App\Support\SqlitePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;

class StartDevelopmentWork extends Command
{
    protected $signature = 'dev:start
        {type : Tipe kerja: fix atau feat}
        {name* : Nama pekerjaan}
        {--branch= : Nama branch custom}
        {--prefix=codex : Prefix branch, contoh: codex, claude}
        {--no-branch : Jangan buat/switch branch git}
        {--copy-db : Copy database kerja, berguna untuk fix yang agak riskan}
        {--no-db-copy : Jangan copy database, bahkan untuk mode feat:}
        {--activate-db : Update .env DB_DATABASE ke database kerja hasil copy}
        {--force : Tetap lanjut walau system check menemukan masalah keras}';

    protected $description = 'Siapkan kerja fix/fitur baru: health check, branch, dan database kerja bila diperlukan.';

    public function handle(): int
    {
        $type = strtolower(rtrim((string) $this->argument('type'), ':'));
        $type = match ($type) {
            'feature' => 'feat',
            default => $type,
        };

        if (! in_array($type, ['fix', 'feat'], true)) {
            $this->error('Tipe harus fix atau feat.');
            $this->line('Contoh: php artisan dev:start fix supplier-items');
            $this->line('Contoh: php artisan dev:start feat cash-receipts');
            return self::FAILURE;
        }

        $name = trim(implode(' ', (array) $this->argument('name')));
        $slug = Str::slug($name);
        if ($slug === '') {
            $this->error('Nama pekerjaan tidak boleh kosong.');
            return self::FAILURE;
        }

        $this->line('=== Persiapan Development ===');
        $this->line('Tipe : ' . ($type === 'feat' ? 'feat - fitur baru' : 'fix - perbaikan'));
        $this->line('Nama : ' . $name);
        $this->newLine();

        $this->info('1. Menjalankan system:check...');
        $checkCode = $this->call('system:check');

        if ($checkCode !== self::SUCCESS && ! $this->option('force')) {
            $this->error('System check menemukan masalah keras. Development dibatalkan agar operasional aman.');
            $this->line('Kalau tetap mau lanjut setelah paham risikonya, jalankan dengan --force.');
            return self::FAILURE;
        }

        $prefix = rtrim((string) $this->option('prefix') ?: 'codex', '/');
        $branch = (string) ($this->option('branch') ?: "{$prefix}/{$type}-{$slug}");
        if (! $this->option('no-branch')) {
            $this->newLine();
            $this->info('2. Menyiapkan branch git...');
            if (! $this->prepareBranch($branch)) {
                return self::FAILURE;
            }
        }

        $shouldCopyDb = ! $this->option('no-db-copy') && ($type === 'feat' || $this->option('copy-db'));
        $workDbPath = null;
        if ($shouldCopyDb) {
            $this->newLine();
            $this->info('3. Membuat database kerja...');
            $workDbPath = $this->copyWorkingDatabase($type, $slug);
            if (! $workDbPath) {
                return self::FAILURE;
            }

            if ($this->option('activate-db')) {
                if (! $this->activateDatabase($workDbPath)) {
                    return self::FAILURE;
                }
            }
        }

        $this->newLine();
        $this->info('Siap kerja.');
        $this->line('Branch: ' . ($this->option('no-branch') ? '(tidak diubah)' : $branch));

        if ($workDbPath) {
            $this->line('Database kerja: ' . $workDbPath);
            if (! $this->option('activate-db')) {
                $this->newLine();
                $this->warn('Database kerja belum diaktifkan di .env.');
                $this->line('Aktifkan manual jika mau pakai DB copy ini:');
                $this->line('DB_DATABASE=' . $workDbPath);
                $this->line('');
                $this->line('Atau ulangi command dengan --activate-db.');
            }
        }

        $this->newLine();
        $this->line('Setelah selesai coding, jalankan:');
        $this->line('php artisan migrate');
        $this->line('php artisan optimize:clear');
        $this->line('php artisan system:check --no-backup --no-snapshot');

        return self::SUCCESS;
    }

    private function prepareBranch(string $branch): bool
    {
        if (! $this->runGit(['rev-parse', '--is-inside-work-tree'], quiet: true)) {
            $this->warn('Folder ini bukan git repository. Branch dilewati.');
            return true;
        }

        $current = trim($this->runGitOutput(['branch', '--show-current']));
        if ($current === $branch) {
            $this->info("Sudah berada di branch {$branch}.");
            return true;
        }

        if ($this->runGit(['show-ref', '--verify', '--quiet', "refs/heads/{$branch}"], quiet: true)) {
            return $this->runGit(['switch', $branch]);
        }

        return $this->runGit(['switch', '-c', $branch]);
    }

    private function copyWorkingDatabase(string $type, string $slug): ?string
    {
        if (config('database.default') !== 'sqlite') {
            $this->error('Copy database otomatis hanya mendukung SQLite.');
            return null;
        }

        $source = SqlitePath::current();
        if (! File::exists($source)) {
            $this->error("Database sumber tidak ditemukan: {$source}");
            return null;
        }

        $target = database_path("database_{$type}_{$slug}.sqlite");
        if (File::exists($target)) {
            $target = database_path("database_{$type}_{$slug}_" . now()->format('Ymd_His') . '.sqlite');
        }

        File::copy($source, $target);

        $this->info('Database kerja dibuat.');
        $this->line($target);

        return $target;
    }

    private function activateDatabase(string $databasePath): bool
    {
        $envPath = base_path('.env');
        if (! File::exists($envPath)) {
            $this->error('.env tidak ditemukan.');
            return false;
        }

        $content = File::get($envPath);
        $line = 'DB_DATABASE=' . $databasePath;

        if (preg_match('/^DB_DATABASE=.*$/m', $content)) {
            $content = preg_replace('/^DB_DATABASE=.*$/m', $line, $content);
        } else {
            $content .= PHP_EOL . $line . PHP_EOL;
        }

        File::put($envPath, $content);
        Artisan::call('config:clear');

        $this->info('.env sudah diarahkan ke database kerja.');
        return true;
    }

    private function runGit(array $arguments, bool $quiet = false): bool
    {
        $process = new Process(['git', ...$arguments], base_path());
        $process->run();

        if (! $quiet) {
            $output = trim($process->getOutput());
            $error = trim($process->getErrorOutput());
            if ($output !== '') {
                $this->line($output);
            }
            if ($error !== '') {
                $this->line($error);
            }
        }

        return $process->isSuccessful();
    }

    private function runGitOutput(array $arguments): string
    {
        $process = new Process(['git', ...$arguments], base_path());
        $process->run();

        return $process->isSuccessful() ? $process->getOutput() : '';
    }
}
