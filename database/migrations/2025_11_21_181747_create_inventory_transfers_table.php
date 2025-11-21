<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfers', function (Blueprint $table) {
            $table->id();

            // kode dokumen (ex: TRF-20251122-001)
            $table->string('code')->unique();

            // tanggal transfer
            $table->date('date');

            // gudang asal & tujuan
            $table->unsignedBigInteger('from_warehouse_id');
            $table->unsignedBigInteger('to_warehouse_id');

            // item yang dipindahkan
            $table->unsignedBigInteger('item_id');

            // qty (boleh decimal 2 angka di belakang, sesuai satuan material/pcs/kg)
            $table->decimal('qty', 15, 2);

            // catatan optional
            $table->text('notes')->nullable();

            // user yang membuat dokumen transfer ini
            $table->unsignedBigInteger('created_by')->nullable();

            $table->timestamps();

            // Foreign keys
            $table->foreign('from_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('to_warehouse_id')->references('id')->on('warehouses')->onDelete('restrict');
            $table->foreign('item_id')->references('id')->on('items')->onDelete('restrict');
            $table->foreign('created_by')->references('id')->on('users')->onDelete('set null');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfers');
    }
};
