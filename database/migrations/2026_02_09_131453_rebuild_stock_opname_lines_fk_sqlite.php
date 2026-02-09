<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        $db = DB::connection('sqlite');

        $db->statement('PRAGMA foreign_keys=OFF;');

        // Rename old table
        $db->statement('ALTER TABLE stock_opname_lines RENAME TO stock_opname_lines_tmp;');

        // Recreate correct table
        $db->statement("
            CREATE TABLE stock_opname_lines (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                stock_opname_id INTEGER NOT NULL,
                item_id INTEGER NOT NULL,
                system_qty NUMERIC NOT NULL DEFAULT 0,
                physical_qty NUMERIC,
                difference_qty NUMERIC NOT NULL DEFAULT 0,
                is_counted TINYINT(1) NOT NULL DEFAULT 0,
                notes TEXT,
                created_at DATETIME,
                updated_at DATETIME,
                unit_cost NUMERIC,
                FOREIGN KEY(stock_opname_id)
                    REFERENCES stock_opnames(id)
                    ON DELETE CASCADE
                    ON UPDATE CASCADE,
                FOREIGN KEY(item_id)
                    REFERENCES items(id)
                    ON DELETE RESTRICT
                    ON UPDATE CASCADE
            );
        ");

        // Copy data back
        $db->statement("
            INSERT INTO stock_opname_lines (
                id,
                stock_opname_id,
                item_id,
                system_qty,
                physical_qty,
                difference_qty,
                is_counted,
                notes,
                created_at,
                updated_at,
                unit_cost
            )
            SELECT
                id,
                stock_opname_id,
                item_id,
                system_qty,
                physical_qty,
                difference_qty,
                is_counted,
                notes,
                created_at,
                updated_at,
                unit_cost
            FROM stock_opname_lines_tmp;
        ");

        // Drop temp
        $db->statement('DROP TABLE stock_opname_lines_tmp;');

        // Extra safety: drop old table if still exists
        $db->statement('DROP TABLE IF EXISTS stock_opnames_old;');

        $db->statement('PRAGMA foreign_keys=ON;');
    }

    public function down(): void
    {
        // no-op (one-way fix)
    }
};
