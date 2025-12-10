<?php

namespace App\Console\Commands;

use App\Support\SqlitePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class DatabaseSnapshot extends Command
{
    protected $signature = 'db:snapshot';
    protected $description = 'Simpan snapshot database SQLite ke 1 file tetap (checkpoint)';

    public function handle(): int
    {
        $dbPath = SqlitePath::current();
        $backupDir = SqlitePath::backupDir();

        if (!File::exists($dbPath)) {
            $this->error("Database SQLite tidak ditemukan: {$dbPath}");
            return self::FAILURE;
        }

        if (!File::exists($backupDir)) {
            File::makeDirectory($backupDir, 0755, true);
        }

        $target = $backupDir . '/snapshot_dev.sqlite';

        File::copy($dbPath, $target);

        $this->info("✅ Snapshot tersimpan sebagai: storage/backups/snapshot_dev.sqlite");
        $this->info("   Sumber: {$dbPath}");

        return self::SUCCESS;
    }
}
