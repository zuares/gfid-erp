<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('journals', function (Blueprint $table) {
            $table->id();

            // tanggal jurnal (tanggal kejadian akuntansi)
            $table->date('date')->index();

            // deskripsi bebas
            $table->string('description')->nullable();

            // asal jurnal (cash_expense, shopee_payout, shipment, dll)
            $table->string('source_type', 50)->index();
            $table->unsignedBigInteger('source_id')->nullable();

            // kapan jurnal dianggap final
            $table->timestamp('posted_at')->nullable();

            $table->timestamps();

            $table->index(['source_type', 'source_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('journals');
    }
};
