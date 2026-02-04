<?php
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mp_reconciliations', function (Blueprint $table) {
            // pastikan doctrine/dbal terpasang kalau DB kamu butuh change()
            $table->unsignedBigInteger('shipment_id')->nullable()->change();

            // performa & integritas
            // $table->unique('mp_shipment_id'); // 1 MP shipment hanya 1 reconciliation
            // $table->index('shipment_id'); // cepat untuk lihat "batch ini berisi apa"
            $table->index('matched_at'); // filter matched/unmatched cepat
        });
    }

    public function down(): void
    {
        Schema::table('mp_reconciliations', function (Blueprint $table) {
            // $table->dropUnique(['mp_shipment_id']);
            // $table->dropIndex(['shipment_id']);
            $table->dropIndex(['matched_at']);

            $table->unsignedBigInteger('shipment_id')->nullable(false)->change();
        });
    }
};
