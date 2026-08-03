<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_orders') || ! Schema::hasTable('stores')) {
            return;
        }

        Schema::table('marketplace_orders', function (Blueprint $table) {
            // SQLite rebuilds the table for this operation; the existing order
            // rows are preserved by Laravel's schema grammar.
            $table->dropForeign(['store_id']);
            $table->foreign('store_id')
                ->references('id')
                ->on('stores')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_orders') || ! Schema::hasTable('marketplace_stores')) {
            return;
        }

        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropForeign(['store_id']);
            $table->foreign('store_id')
                ->references('id')
                ->on('marketplace_stores')
                ->cascadeOnDelete();
        });
    }
};
