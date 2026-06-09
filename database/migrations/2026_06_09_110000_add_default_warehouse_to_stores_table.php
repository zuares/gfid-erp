<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            if (! Schema::hasColumn('stores', 'default_warehouse_id')) {
                $table->foreignId('default_warehouse_id')
                    ->nullable()
                    ->after('channel_id')
                    ->constrained('warehouses')
                    ->nullOnDelete();
            }
        });
    }

    public function down(): void
    {
        Schema::table('stores', function (Blueprint $table) {
            $table->dropForeignIdFor(\App\Models\Warehouse::class, 'default_warehouse_id');
            $table->dropColumn('default_warehouse_id');
        });
    }
};
