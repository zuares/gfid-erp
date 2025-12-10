<?php

namespace App\Console\Commands;

use App\Support\SqlitePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class RestoreDatabase extends Command
{
    protected $signature = 'db:restore';
    protected $description = 'Restore SQLite database dari backup terbaru di storage/backups/';

    public function handle(): int
    {
        $backupDir = SqlitePath::backupDir();

        if (!File::exists($backupDir)) {
            $this->error("Folder backup tidak ditemukan: {$backupDir}");
            return self::FAILURE;
        }

        // Ambil semua file *.sqlite
        $files = collect(File::files($backupDir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sqlite'))
            ->sortByDesc(fn($f) => $f->getCTime()) // terbaru di atas
            ->values();

        if ($files->isEmpty()) {
            $this->error("Tidak ada file .sqlite di folder backups.");
            return self::FAILURE;
        }

        // Ambil file paling baru
        $latest = $files->first();
        $this->info("📦 File backup terbaru:");
        $this->info("→ " . $latest->getFilename());

        $dbPath = SqlitePath::current();

        // Safety backup sebelum overwrite
        if (File::exists($dbPath)) {
            $safety = $backupDir . '/before_restore_' . now()->format('Ymd_His') . '.sqlite';
            File::copy($dbPath, $safety);
            $this->info("✔ Database saat ini disimpan sebagai: " . basename($safety));
        }

        // Restore (overwrite)
        File::copy($latest->getRealPath(), $dbPath);

        $this->info("🎉 Database berhasil direstore ke: {$dbPath}");
        $this->call('optimize:clear');

        return self::SUCCESS;
    }
}
