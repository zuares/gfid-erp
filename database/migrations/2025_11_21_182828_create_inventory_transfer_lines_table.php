<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inventory_transfer_lines', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('inventory_transfer_id');
            $table->unsignedBigInteger('item_id');

            $table->decimal('qty', 15, 2);
            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('inventory_transfer_id')
                ->references('id')->on('inventory_transfers')
                ->onDelete('cascade');

            $table->foreign('item_id')
                ->references('id')->on('items')
                ->onDelete('restrict');
        });

        // OPTIONAL (kalau mau benar-benar pindah ke multi-item):
        // kamu boleh HANYA lakukan ini di environment dev
        // atau setelah migrate:fresh.
        /*
    Schema::table('inventory_transfers', function (Blueprint $table) {
    if (Schema::hasColumn('inventory_transfers', 'item_id')) {
    $table->dropForeign(['item_id']);
    $table->dropColumn('item_id');
    }
    if (Schema::hasColumn('inventory_transfers', 'qty')) {
    $table->dropColumn('qty');
    }
    });
     */
    }

    public function down(): void
    {
        Schema::dropIfExists('inventory_transfer_lines');

        // OPTIONAL rollback kolom lama, kalau tadi kamu drop
        /*
    Schema::table('inventory_transfers', function (Blueprint $table) {
    $table->unsignedBigInteger('item_id')->nullable();
    $table->decimal('qty', 15, 3)->default(0);
    });
     */
    }
};
