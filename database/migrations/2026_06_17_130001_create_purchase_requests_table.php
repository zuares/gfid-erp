<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Tahap 5C — Purchase Request
 * Tabel utama purchase_requests (header).
 * Additive: hanya create table, tidak mengubah tabel lain.
 */
return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('purchase_requests')) {
            return; // idempoten
        }

        Schema::create('purchase_requests', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();
            $table->date('date');
            $table->unsignedBigInteger('supplier_id')->nullable();
            $table->unsignedBigInteger('requested_by');
            $table->unsignedBigInteger('approved_by')->nullable();
            $table->unsignedBigInteger('rejected_by')->nullable();
            $table->string('status', 20)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->foreign('supplier_id')
                ->references('id')->on('suppliers')
                ->nullOnDelete();

            $table->foreign('requested_by')
                ->references('id')->on('users');

            $table->foreign('approved_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->foreign('rejected_by')
                ->references('id')->on('users')
                ->nullOnDelete();

            $table->index('status');
            $table->index('date');
            $table->index('requested_by');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_requests');
    }
};
