<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class BackupDatabase extends Command
{
    protected $signature = 'db:backup';
    protected $description = 'Backup SQLite database ke folder storage/backups/ (max 20 file, sisanya dihapus otomatis)';

    public function handle(): int
    {
        $dbPath = config('database.connections.sqlite.database') ?? database_path('database.sqlite');
        $backupDir = storage_path('backups');

        if (!File::exists($dbPath)) {
            $this->error("Database SQLite tidak ditemukan: $dbPath");
            return self::FAILURE;
        }

        // Pastikan folder backup ada
        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        // 1️⃣ Buat backup baru
        $filename = 'backup_' . now()->format('Ymd_His') . '.sqlite';
        $target = $backupDir . '/' . $filename;

        File::copy($dbPath, $target);

        $this->info("🎉 Backup berhasil disimpan:");
        $this->info("→ storage/backups/{$filename}");

        // 2️⃣ Batasi jumlah backup maks 20 file (hapus yang paling lama)
        $this->cleanupOldBackups($backupDir);

        return self::SUCCESS;
    }

    /**
     * Hapus file backup lama jika jumlahnya lebih dari 20.
     */
    protected function cleanupOldBackups(string $backupDir): void
    {
        // Ambil semua file .sqlite di folder backup
        $files = collect(File::files($backupDir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sqlite'))
            ->sortByDesc(fn($f) => $f->getCTime()) // terbaru dulu
            ->values();

        $maxFiles = 20;

        if ($files->count() <= $maxFiles) {
            $this->info("ℹ️ Jumlah backup saat ini: {$files->count()} (<= {$maxFiles}, tidak ada yang dihapus).");
            return;
        }

        // Ambil file yang harus dihapus (mulai dari yang paling lama)
        $toDelete = $files->slice($maxFiles);

        foreach ($toDelete as $file) {
            $name = $file->getFilename();
            File::delete($file->getRealPath());
            $this->info("🗑️ Menghapus backup lama: {$name}");
        }

        $this->info("✅ Cleanup selesai. Backup yang disimpan: {$maxFiles} file terbaru.");
    }
}
