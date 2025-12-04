<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_lines', function (Blueprint $table) {
            $table->id();

            $table->foreignId('shipment_id')
                ->constrained()
                ->cascadeOnDelete();

            $table->foreignId('item_id')->constrained();

            $table->integer('qty');

            // Optional: lock HPP saat itu (kalau nanti mau dipakai untuk analisa)
            $table->decimal('hpp_unit_snapshot', 18, 4)->default(0);

            // Kalau nanti dikaitkan ke modul Sales Invoice
            $table->foreignId('sales_invoice_line_id')
                ->nullable()
                ->constrained()
                ->nullOnDelete();

            $table->text('remarks')->nullable();

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_lines');
    }
};
