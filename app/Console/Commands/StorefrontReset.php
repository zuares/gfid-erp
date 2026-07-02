<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class StorefrontReset extends Command
{
    protected $signature   = 'storefront:reset {--yes : Skip confirmation}';
    protected $description = 'Hapus semua data storefront (customers, orders, visitors, events)';

    public function handle(): int
    {
        if (! $this->option('yes')) {
            if (! $this->confirm('⚠️  Ini akan menghapus SEMUA data storefront. Lanjutkan?', false)) {
                $this->info('Dibatalkan.');
                return self::SUCCESS;
            }
        }

        DB::statement('PRAGMA foreign_keys = OFF');

        $tables = [
            'storefront_customers',
            'storefront_orders',
            'storefront_events',
            'storefront_visitors',
        ];

        foreach ($tables as $table) {
            if (DB::getSchemaBuilder()->hasTable($table)) {
                DB::table($table)->truncate();
                $this->line("  ✓ <fg=green>{$table}</> dikosongkan");
            } else {
                $this->line("  <fg=yellow>skip</> {$table} (tabel tidak ada)");
            }
        }

        DB::statement('PRAGMA foreign_keys = ON');

        // Clear blade view cache
        $this->call('view:clear');

        $this->newLine();
        $this->info('✅  Semua data storefront berhasil dihapus.');
        $this->line('   Silakan buka <href=http://gfid-dev.test/storefront>http://gfid-dev.test</> untuk tes ulang.');

        return self::SUCCESS;
    }
}
