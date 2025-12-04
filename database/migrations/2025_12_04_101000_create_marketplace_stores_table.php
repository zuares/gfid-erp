<?php

// database/migrations/2025_12_04_101000_create_marketplace_stores_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_stores', function (Blueprint $table) {
            $table->id();
            $table->foreignId('channel_id')
                ->constrained('marketplace_channels')
                ->cascadeOnDelete();

            $table->string('external_store_id', 100)->nullable(); // id dari platform
            $table->string('name', 150);
            $table->string('short_code', 30)->nullable(); // SHP-MAIN
            $table->foreignId('default_warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index('short_code');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_stores');
    }
};
