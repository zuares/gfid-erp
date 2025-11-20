<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ListBackups extends Command
{
    protected $signature = 'db:backup:list {--limit= : Batas jumlah file backup yang ingin ditampilkan}';
    protected $description = 'Menampilkan daftar file backup database di storage/backups/';

    public function handle(): int
    {
        $backupDir = storage_path('backups');

        if (!File::exists($backupDir)) {
            $this->warn("📁 Folder backup belum ada: {$backupDir}");
            return self::SUCCESS;
        }

        $files = collect(File::files($backupDir))
            ->filter(fn($f) => str_ends_with($f->getFilename(), '.sqlite'))
            ->sortByDesc(fn($f) => $f->getCTime())
            ->values();

        if ($files->isEmpty()) {
            $this->warn("⚠️ Tidak ada file backup ditemukan.");
            return self::SUCCESS;
        }

        // 🔥 Ambil opsi limit
        $limit = (int) ($this->option('limit') ?? 0);
        if ($limit > 0) {
            $files = $files->take($limit);
            $this->info("📦 Menampilkan {$limit} backup terbaru:");
        } else {
            $this->info("📦 Daftar Backup (terbaru → terlama):");
        }

        $this->line(str_repeat('-', 50));

        foreach ($files as $file) {
            $this->line(
                sprintf(
                    "%s   |   %s KB",
                    $file->getFilename(),
                    number_format($file->getSize() / 1024, 2)
                )
            );
        }

        $this->line(str_repeat('-', 50));
        $this->info("Total ditampilkan: " . $files->count() . " file");

        return self::SUCCESS;
    }
}
