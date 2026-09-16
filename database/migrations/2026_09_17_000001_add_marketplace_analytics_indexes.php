<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $indexes = [
            ['marketplace_orders', ['store_id', 'ordered_at'], 'idx_mo_store_ordered_at'],
            ['marketplace_order_items', ['marketplace_order_id', 'data_status'], 'idx_moi_order_data_status'],
            ['marketplace_returns', ['store_id', 'create_time'], 'idx_mr_store_create_time'],
            ['marketplace_returns', ['store_id', 'order_sn'], 'idx_mr_store_order_sn'],
        ];

        foreach ($indexes as [$tableName, $columns, $indexName]) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            $hasColumns = collect($columns)->every(fn (string $column): bool => Schema::hasColumn($tableName, $column));
            if (! $hasColumns) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($columns, $indexName): void {
                try {
                    $table->index($columns, $indexName);
                } catch (Throwable) {
                    // Keep migrations safe for installations with a manual index.
                }
            });
        }
    }

    public function down(): void
    {
        foreach ([
            ['marketplace_returns', 'idx_mr_store_order_sn'],
            ['marketplace_returns', 'idx_mr_store_create_time'],
            ['marketplace_order_items', 'idx_moi_order_data_status'],
            ['marketplace_orders', 'idx_mo_store_ordered_at'],
        ] as [$tableName, $indexName]) {
            if (! Schema::hasTable($tableName)) {
                continue;
            }

            Schema::table($tableName, function (Blueprint $table) use ($indexName): void {
                try {
                    $table->dropIndex($indexName);
                } catch (Throwable) {
                    // The index may already be absent on a manually repaired database.
                }
            });
        }
    }
};
