<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (
            ! Schema::hasTable('marketplace_orders')
            || ! Schema::hasColumn('marketplace_orders', 'order_status')
            || ! Schema::hasColumn('marketplace_orders', 'order_date')
            || Schema::hasIndex('marketplace_orders', 'idx_mo_dashboard_status_date')
        ) {
            return;
        }

        Schema::table('marketplace_orders', function (Blueprint $table): void {
            $table->index(
                ['order_status', 'order_date'],
                'idx_mo_dashboard_status_date',
            );
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_orders') || ! Schema::hasIndex('marketplace_orders', 'idx_mo_dashboard_status_date')) {
            return;
        }

        Schema::table('marketplace_orders', function (Blueprint $table): void {
            $table->dropIndex('idx_mo_dashboard_status_date');
        });
    }
};
