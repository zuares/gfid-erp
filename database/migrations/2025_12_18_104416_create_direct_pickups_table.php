<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('direct_pickups', function (Blueprint $table) {
            $table->id();

            $table->string('code')->unique(); // DPR-YYYYMMDD-### (generate di service/controller)

            // waktu kejadian pickup (biar audit jamnya juga ada)
            $table->dateTime('picked_at');

            // gudang asal & tujuan (ideal: WIP-FIN -> WH-RTS)
            $table->foreignId('source_warehouse_id')->constrained('warehouses');
            $table->foreignId('destination_warehouse_id')->constrained('warehouses');

            // siapa yang melakukan pickup (admin/operating)
            $table->foreignId('picked_by_user_id')->constrained('users');

            // optional: kalau pickup ini terkait RTS Stock Request tertentu
            $table->foreignId('stock_request_id')->nullable()->constrained('stock_requests');

            // status dokumen
            $table->string('status')->default('posted'); // draft|posted|void

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->index(['picked_at', 'source_warehouse_id']);
            $table->index(['picked_at', 'destination_warehouse_id']);
            $table->index(['stock_request_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('direct_pickups');
    }
};
