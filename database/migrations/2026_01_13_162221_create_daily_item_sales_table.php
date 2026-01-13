<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('daily_item_sales', function (Blueprint $table) {
            $table->id();

            // tanggal penjualan (mengikuti shipments.date)
            $table->date('date');

            // relasi item
            $table->unsignedBigInteger('item_id');

            // qty terjual di hari tsb
            $table->decimal('qty_sold', 14, 4)->default(0);

            // nilai penjualan berbasis HPP (qty * items.hpp)
            // opsional tapi berguna untuk value-based analytics
            $table->decimal('value_sold', 16, 2)->default(0);

            $table->timestamps();

            // ===== Indexes =====
            // unik per item per hari (penting untuk UPSERT)
            $table->unique(['date', 'item_id']);

            // performance
            $table->index('item_id');
            $table->index('date');

            // FK (optional, boleh kamu skip kalau SQLite sering rewel)
            $table->foreign('item_id')
                ->references('id')
                ->on('items')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('daily_item_sales');
    }
};
