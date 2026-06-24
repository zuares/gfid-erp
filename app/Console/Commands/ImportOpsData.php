<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class ImportOpsData extends Command
{
    protected $signature = 'dev:import-ops {--file= : Path ke file SQL}';
    protected $description = 'Import data OPS ke database dev';

    public function handle(): int
    {
        $file = $this->option('file') ?: database_path('ops_import.sql');

        if (!file_exists($file)) {
            $this->error("File tidak ditemukan: {$file}");
            return 1;
        }

        $this->info("Membaca: {$file}");
        $sql = file_get_contents($file);

        // Split per statement
        $statements = array_filter(
            array_map('trim', explode(";\n", $sql)),
            fn($s) => strlen($s) > 3 && !str_starts_with(ltrim($s), '--')
        );

        $this->info("Total statements: " . count($statements));

        DB::statement('PRAGMA foreign_keys=OFF');

        $bar = $this->output->createProgressBar(count($statements));
        $errors = 0;

        foreach ($statements as $stmt) {
            try {
                DB::unprepared($stmt . ';');
            } catch (\Throwable $e) {
                $errors++;
                $this->newLine();
                $this->warn("SKIP: " . substr($stmt, 0, 80) . " — " . $e->getMessage());
            }
            $bar->advance();
        }

        $bar->finish();
        $this->newLine();
        DB::statement('PRAGMA foreign_keys=ON');

        if ($errors > 0) {
            $this->warn("{$errors} statement gagal (biasanya kolom baru yg belum ada di OPS).");
        }

        $this->info('Import selesai!');
        return 0;
    }
}
