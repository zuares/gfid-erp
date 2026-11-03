<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('sewing_pickups') && ! Schema::hasColumn('sewing_pickups', 'idempotency_key')) {
            Schema::table('sewing_pickups', function (Blueprint $table): void {
                $table->string('idempotency_key', 80)->nullable()->unique();
            });
        }

        if (Schema::hasTable('sewing_pickup_supply_lines')) {
            Schema::table('sewing_pickup_supply_lines', function (Blueprint $table): void {
                if (! Schema::hasColumn('sewing_pickup_supply_lines', 'stock_shortage_qty')) {
                    $table->decimal('stock_shortage_qty', 18, 6)->default(0)->after('stock_available_snapshot');
                }
                if (! Schema::hasColumn('sewing_pickup_supply_lines', 'stock_shortage_status')) {
                    $table->string('stock_shortage_status', 20)->default('none')->after('stock_shortage_qty');
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('sewing_pickup_supply_lines')) {
            Schema::table('sewing_pickup_supply_lines', function (Blueprint $table): void {
                $columns = [];
                if (Schema::hasColumn('sewing_pickup_supply_lines', 'stock_shortage_status')) {
                    $columns[] = 'stock_shortage_status';
                }
                if (Schema::hasColumn('sewing_pickup_supply_lines', 'stock_shortage_qty')) {
                    $columns[] = 'stock_shortage_qty';
                }
                if ($columns !== []) {
                    $table->dropColumn($columns);
                }
            });
        }

        if (Schema::hasTable('sewing_pickups') && Schema::hasColumn('sewing_pickups', 'idempotency_key')) {
            Schema::table('sewing_pickups', function (Blueprint $table): void {
                $table->dropUnique(['idempotency_key']);
                $table->dropColumn('idempotency_key');
            });
        }
    }
};
