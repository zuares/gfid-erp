<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payment_methods', function (Blueprint $table) {
            $table->id();

            $table->string('code', 30)->unique(); // CASH, BANK, TEMPO, DP
            $table->string('name', 100); // Cash, Transfer Bank, Tempo Supplier
            $table->string('mode', 20); // cash | credit | hybrid

            // optional: untuk catatan ringan
            $table->string('description', 255)->nullable();

            // optional: jika butuh kontrol urutan tampil
            $table->unsignedSmallInteger('sort_order')->default(0);

            $table->boolean('is_active')->default(true);

            $table->timestamps();

            $table->index(['mode', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payment_methods');
    }
};
