<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('order_fulfillments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('marketplace_order_id')
                ->constrained('marketplace_orders')
                ->cascadeOnDelete();

            $table->foreignId('warehouse_id')
                ->nullable()
                ->constrained('warehouses')
                ->nullOnDelete();

            /**
             * Status workflow:
             *  draft          — baru dibuat, belum ada review
             *  pending_review — siap direview owner (semua line sudah ter-resolve)
             *  confirmed      — owner sudah klik konfirmasi, stok sudah dipotong
             *  cancelled      — dibatalkan
             */
            $table->string('status')->default('draft')->index();

            $table->text('notes')->nullable();

            // Audit konfirmasi
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('confirmed_at')->nullable();

            $table->timestamps();

            $table->unique('marketplace_order_id'); // 1 fulfillment per order
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_fulfillments');
    }
};
