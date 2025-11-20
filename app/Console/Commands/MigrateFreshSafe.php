<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\Artisan;

class MigrateFreshSafe extends Command
{
    protected $signature = 'migrate:fresh-safe {--seed}';
    protected $description = 'Backup database dahulu, lalu jalankan migrate:fresh dengan aman';

    public function handle(): int
    {
        $this->info("📦 Membuat backup database sebelum migrate:fresh...");

        // Jalankan backup database
        Artisan::call('db:backup');
        $this->info(trim(Artisan::output()));

        $this->info("🔄 Menjalankan migrate:fresh...");

        // Menjalankan migrate:fresh asli
        Artisan::call('migrate:fresh', [
            '--seed' => $this->option('seed'),
        ]);

        $this->info(trim(Artisan::output()));

        $this->info("🎉 migrate:fresh selesai dan backup aman tersimpan!");

        return self::SUCCESS;
    }
}
