<?php

// database/migrations/2025_12_04_000000_create_item_barcodes_table.php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('item_barcodes', function (Blueprint $table) {
            $table->id();
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            $table->string('barcode'); // isi persis yang di-scan
            $table->string('type', 30)->default('main');
            $table->string('notes')->nullable();

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            // Satu barcode tidak boleh dipakai banyak item
            $table->unique('barcode');

            // Untuk debug/maintenance
            $table->index('item_id');
            $table->index(['type', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('item_barcodes');
    }
};
