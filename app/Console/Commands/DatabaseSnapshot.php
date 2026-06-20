<?php

namespace App\Console\Commands;

use App\Support\SqlitePath;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\DB;

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

        if (DB::connection()->getDriverName() !== 'sqlite') {
            $this->error('Command db:snapshot hanya mendukung database SQLite.');
            return self::FAILURE;
        }

        $target = $backupDir . '/snapshot_dev.sqlite';
        $temporary = $backupDir . '/.snapshot_dev_' . now()->format('Ymd_His_u') . '.sqlite';

        try {
            $quotedTarget = DB::connection()->getPdo()->quote($temporary);
            DB::statement("VACUUM INTO {$quotedTarget}");

            $check = new \PDO('sqlite:' . $temporary);
            $integrity = $check->query('PRAGMA integrity_check')->fetchColumn();
            $check = null;

            if ($integrity !== 'ok') {
                throw new \RuntimeException("Integrity check gagal: {$integrity}");
            }

            if (!@rename($temporary, $target)) {
                throw new \RuntimeException('Gagal mengganti file snapshot secara atomik.');
            }
        } catch (\Throwable $e) {
            File::delete($temporary);
            $this->error('Snapshot gagal: ' . $e->getMessage());
            return self::FAILURE;
        }

        $this->info("Snapshot tersimpan sebagai: storage/backups/snapshot_dev.sqlite");
        $this->info("   Sumber: {$dbPath}");
        $this->info('   Integrity: ok');
        $this->info('   Ukuran: ' . number_format(File::size($target) / 1024, 2) . ' KB');

        return self::SUCCESS;
    }
}
