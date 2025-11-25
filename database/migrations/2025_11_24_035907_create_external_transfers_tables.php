<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        /**
         * HEADER: external_transfers
         * Dokumen pengiriman kain (LOT) ke gudang external/vendor.
         */
        Schema::create('external_transfers', function (Blueprint $table) {
            $table->id();
            $table->string('code')->unique();

            $table->date('date');
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');

            $table->string('status')->default('SENT'); // SENT → BATCHED → dll
            $table->timestamp('received_at')->nullable();

            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // FK opsional (boleh di-comment dulu kalau mau)
            $table->foreign('from_warehouse_id')
                ->references('id')->on('warehouses');

            $table->foreign('to_warehouse_id')
                ->references('id')->on('warehouses');

            $table->foreign('created_by')
                ->references('id')->on('users');
        });

        /**
         * DETAIL: external_transfer_lines
         * Baris LOT yang dikirim.
         */
        Schema::create('external_transfer_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('external_transfer_id');

            $table->unsignedBigInteger('lot_id');
            $table->unsignedBigInteger('item_id');
            $table->string('item_code');

            $table->decimal('qty', 14, 3);
            $table->string('unit', 16)->default('m');

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('external_transfer_id')
                ->references('id')->on('external_transfers')
                ->onDelete('cascade');

            $table->foreign('lot_id')->references('id')->on('lots');
            $table->foreign('item_id')->references('id')->on('items');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('external_transfer_lines');
        Schema::dropIfExists('external_transfers');
    }
};
