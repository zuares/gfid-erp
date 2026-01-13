<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (!Schema::hasColumn('items', 'avg_daily_sales')) {
                $table->decimal('avg_daily_sales', 12, 4)->default(0);
            }
            if (!Schema::hasColumn('items', 'avg_daily_sales_window')) {
                $table->unsignedInteger('avg_daily_sales_window')->default(30);
            }
            if (!Schema::hasColumn('items', 'avg_daily_sales_updated_at')) {
                $table->dateTime('avg_daily_sales_updated_at')->nullable();
            }
        });
    }

    public function down(): void
    {
        Schema::table('items', function (Blueprint $table) {
            if (Schema::hasColumn('items', 'avg_daily_sales')) {
                $table->dropColumn('avg_daily_sales');
            }
            if (Schema::hasColumn('items', 'avg_daily_sales_window')) {
                $table->dropColumn('avg_daily_sales_window');
            }
            if (Schema::hasColumn('items', 'avg_daily_sales_updated_at')) {
                $table->dropColumn('avg_daily_sales_updated_at');
            }
        });
    }
};
