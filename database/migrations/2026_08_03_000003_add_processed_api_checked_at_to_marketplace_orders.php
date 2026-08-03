<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_orders')
            && ! Schema::hasColumn('marketplace_orders', 'processed_api_checked_at')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->timestamp('processed_api_checked_at')->nullable()->index();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_orders')
            && Schema::hasColumn('marketplace_orders', 'processed_api_checked_at')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                $table->dropColumn('processed_api_checked_at');
            });
        }
    }
};
