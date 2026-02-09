<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function quoteIdent(string $name): string
    {
        // SQLite identifier quoting: "name", escape internal quotes by doubling
        return '"' . str_replace('"', '""', $name) . '"';
    }

    public function up(): void
    {
        // Only for SQLite
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $db = DB::connection('sqlite');

        // foreign_keys OFF so we can drop triggers/views safely if they reference old tables
        $db->statement('PRAGMA foreign_keys=OFF;');

        // 1) Drop any schema objects (trigger/view/index) whose SQL contains stock_opnames_old
        $objects = $db->select("
            SELECT type, name
            FROM sqlite_master
            WHERE sql IS NOT NULL
              AND sql LIKE '%stock_opnames_old%'
            ORDER BY type, name
        ");

        foreach ($objects as $o) {
            $type = strtolower((string) $o->type);
            $name = (string) $o->name;

            // Only drop what is safe/expected
            if ($type === 'trigger') {
                $db->statement('DROP TRIGGER IF EXISTS ' . $this->quoteIdent($name));
            } elseif ($type === 'view') {
                $db->statement('DROP VIEW IF EXISTS ' . $this->quoteIdent($name));
            } elseif ($type === 'index') {
                $db->statement('DROP INDEX IF EXISTS ' . $this->quoteIdent($name));
            }
            // Note: we intentionally do NOT drop tables here (except stock_opnames_old below)
        }

        // 2) Drop the leftover old table if it exists
        $db->statement('DROP TABLE IF EXISTS stock_opnames_old');

        $db->statement('PRAGMA foreign_keys=ON;');
    }

    public function down(): void
    {
        // no-op (cleanup migration)
        return;
    }
};
