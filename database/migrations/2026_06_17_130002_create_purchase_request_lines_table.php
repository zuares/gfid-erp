<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 5C — Purchase Request
 * Tabel baris / lines dari purchase_requests.
 * Additive: hanya create table.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_request_lines')) {
            return; // idempoten
        }

        Schema::create('purchase_request_lines', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('purchase_request_id');
            $table->unsignedBigInteger('item_id')->nullable();
            $table->decimal('qty', 18, 2);
            $table->decimal('unit_price', 18, 2)->nullable();
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->foreign('purchase_request_id')
                ->references('id')->on('purchase_requests')
                ->cascadeOnDelete();

            $table->foreign('item_id')
                ->references('id')->on('items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_request_lines');
    }
};
