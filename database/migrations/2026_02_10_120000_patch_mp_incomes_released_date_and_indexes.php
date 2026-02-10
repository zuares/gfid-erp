<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private string $table = 'mp_incomes';

    private function indexExistsSqlite(string $indexName): bool
    {
        // PRAGMA index_list('table') returns: seq, name, unique, origin, partial
        $rows = DB::select("PRAGMA index_list('{$this->table}')");
        foreach ($rows as $r) {
            // SQLite returns objects
            if (($r->name ?? null) === $indexName) {
                return true;
            }
        }
        return false;
    }

    public function up(): void
    {
        // 1) Add column if missing (SQLite-safe, no AFTER)
        if (!Schema::hasColumn($this->table, 'released_date')) {
            Schema::table($this->table, function (Blueprint $table) {
                $table->date('released_date')->nullable();
            });
        }

        // 2) Backfill released_date when NULL
        // DATE(released_at) works for 'YYYY-MM-DD HH:MM:SS'
        DB::statement("
            UPDATE {$this->table}
            SET released_date = DATE(released_at)
            WHERE released_at IS NOT NULL
              AND (released_date IS NULL OR released_date = '')
        ");

        // 3) Add indexes if missing (SQLite PRAGMA check)
        // a) store+channel+released_date
        $idx1 = 'mp_incomes_store_channel_released_date_idx';
        if (!$this->indexExistsSqlite($idx1)) {
            Schema::table($this->table, function (Blueprint $table) use ($idx1) {
                $table->index(['store_id', 'channel', 'released_date'], $idx1);
            });
        }

        // b) import_batch_id
        $idx2 = 'mp_incomes_import_batch_idx';
        if (!$this->indexExistsSqlite($idx2)) {
            Schema::table($this->table, function (Blueprint $table) use ($idx2) {
                $table->index(['import_batch_id'], $idx2);
            });
        }
    }

    public function down(): void
    {
        // Down migrations in production is rare; keep it safe.
        // Drop indexes if they exist; do NOT drop column to avoid breaking older code.
        if (Schema::hasTable($this->table)) {
            // dropIndex requires exact name; we'll try-catch for safety
            try {
                Schema::table($this->table, function (Blueprint $table) {
                    $table->dropIndex('mp_incomes_store_channel_released_date_idx');
                });
            } catch (\Throwable $e) {}

            try {
                Schema::table($this->table, function (Blueprint $table) {
                    $table->dropIndex('mp_incomes_import_batch_idx');
                });
            } catch (\Throwable $e) {}
        }
    }
};
