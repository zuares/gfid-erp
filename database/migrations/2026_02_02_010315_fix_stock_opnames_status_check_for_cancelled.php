<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    private function sqliteHasColumn(string $table, string $column): bool
    {
        $rows = DB::select("PRAGMA table_info('$table')");
        foreach ($rows as $r) {
            if (($r->name ?? null) === $column) {
                return true;
            }

        }
        return false;
    }

    public function up(): void
    {
        // hanya untuk sqlite
        if (DB::getDriverName() !== 'sqlite') {
            return;
        }

        DB::statement('PRAGMA foreign_keys=OFF;');

        // 1) rename table lama
        DB::statement('ALTER TABLE stock_opnames RENAME TO stock_opnames_old;');

        // 2) buat table baru dengan CHECK updated
        DB::statement("
            CREATE TABLE stock_opnames (
                id INTEGER PRIMARY KEY AUTOINCREMENT NOT NULL,
                code varchar NOT NULL,
                date date NOT NULL,
                warehouse_id INTEGER NOT NULL,
                type varchar NOT NULL DEFAULT 'periodic',
                status varchar NOT NULL DEFAULT 'draft'
                    CHECK (status IN ('draft','counting','reviewed','finalized','cancelled')),
                notes TEXT NULL,

                created_by INTEGER NOT NULL,
                reviewed_by INTEGER NULL,
                finalized_by INTEGER NULL,

                reviewed_at datetime NULL,
                finalized_at datetime NULL,

                cancelled_at datetime NULL,
                cancelled_by INTEGER NULL,
                cancel_reason varchar NULL,

                created_at datetime NULL,
                updated_at datetime NULL,

                FOREIGN KEY (warehouse_id) REFERENCES warehouses(id),
                FOREIGN KEY (created_by) REFERENCES users(id),
                FOREIGN KEY (reviewed_by) REFERENCES users(id),
                FOREIGN KEY (finalized_by) REFERENCES users(id),
                FOREIGN KEY (cancelled_by) REFERENCES users(id)
            );
        ");

        // 3) copy data (sqlite-safe: kalau kolom cancelled_* belum ada di old, isi NULL)
        $old = 'stock_opnames_old';

        $cancelledAtExpr = $this->sqliteHasColumn($old, 'cancelled_at') ? 'cancelled_at' : 'NULL AS cancelled_at';
        $cancelledByExpr = $this->sqliteHasColumn($old, 'cancelled_by') ? 'cancelled_by' : 'NULL AS cancelled_by';
        $cancelReasonExpr = $this->sqliteHasColumn($old, 'cancel_reason') ? 'cancel_reason' : 'NULL AS cancel_reason';

        DB::statement("
            INSERT INTO stock_opnames (
                id, code, date, warehouse_id, type, status, notes,
                created_by, reviewed_by, finalized_by, reviewed_at, finalized_at,
                cancelled_at, cancelled_by, cancel_reason,
                created_at, updated_at
            )
            SELECT
                id, code, date, warehouse_id, type, status, notes,
                created_by, reviewed_by, finalized_by, reviewed_at, finalized_at,
                $cancelledAtExpr, $cancelledByExpr, $cancelReasonExpr,
                created_at, updated_at
            FROM {$old};
        ");

        // 4) drop old
        DB::statement('DROP TABLE stock_opnames_old;');

        DB::statement('PRAGMA foreign_keys=ON;');
    }

    public function down(): void
    {
        // rollback tidak dibuat
    }
};
