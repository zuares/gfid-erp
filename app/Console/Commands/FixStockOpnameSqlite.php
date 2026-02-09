<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class FixStockOpnameSqlite extends Command
{
    protected $signature = 'dev:fix-stockopname-sqlite {--dry : Only show what would be dropped}';
    protected $description = 'SQLite cleanup: drop any triggers/views/indexes referencing stock_opnames_old and drop the table if exists';

    public function handle(): int
    {
        $db = DB::connection('sqlite');

        // 1) Find all objects that reference stock_opnames_old
        $rows = $db->select("
            select type, name, sql
            from sqlite_master
            where sql like '%stock_opnames_old%'
            order by type, name
        ");

        if (empty($rows)) {
            $this->info('No sqlite schema objects referencing stock_opnames_old found.');
        } else {
            $this->warn('Found objects referencing stock_opnames_old:');
            foreach ($rows as $r) {
                $this->line("- {$r->type}: {$r->name}");
            }

            if ($this->option('dry')) {
                $this->comment('Dry run. Nothing dropped.');
                return self::SUCCESS;
            }

            // 2) Drop them
            $db->statement('PRAGMA foreign_keys=OFF;');

            foreach ($rows as $r) {
                $name = str_replace('"', '""', $r->name); // escape quotes

                if ($r->type === 'trigger') {
                    $db->statement("DROP TRIGGER IF EXISTS \"$name\"");
                }

                if ($r->type === 'view') {
                    $db->statement("DROP VIEW IF EXISTS \"$name\"");
                }

                if ($r->type === 'index') {
                    $db->statement("DROP INDEX IF EXISTS \"$name\"");
                }

                $this->info("Dropped {$r->type}: {$r->name}");
            }

            // 3) Drop old table (if exists)
            $db->statement('DROP TABLE IF EXISTS stock_opnames_old');
            $this->info('Dropped table: stock_opnames_old (if existed)');

            $db->statement('PRAGMA foreign_keys=ON;');
        }

        // Optional: quick check
        $left = $db->select("
            select count(*) as c
            from sqlite_master
            where sql like '%stock_opnames_old%'
        ");
        $count = (int) ($left[0]->c ?? 0);

        if ($count > 0) {
            $this->error("Still found {$count} schema objects referencing stock_opnames_old. Need deeper cleanup.");
            return self::FAILURE;
        }

        $this->info('Cleanup done.');
        return self::SUCCESS;
    }
}
