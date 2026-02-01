<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('mp_reconciliations', function (Blueprint $table) {
            $table->id();

            $table->unsignedBigInteger('mp_shipment_id')->unique();
            $table->unsignedBigInteger('shipment_id')->index(); // operasional shipments(id)

            $table->string('match_key', 32)->default('manual'); // tracking|date_qty|date_qty_sku|manual
            $table->unsignedTinyInteger('match_confidence')->default(0); // 0..100

            $table->unsignedBigInteger('matched_by')->nullable()->index(); // users(id) if manual
            $table->dateTime('matched_at')->nullable();

            $table->text('notes')->nullable();

            $table->timestamps();

            $table->foreign('mp_shipment_id')
                ->references('id')->on('mp_shipments')
                ->onDelete('cascade');

            $table->foreign('shipment_id')
                ->references('id')->on('shipments')
                ->onDelete('cascade');

            // optional: enforce 1 operasional shipment can only link to one mp_shipment
            // uncomment if that rule is valid for your ops flow:
            // $table->unique(['shipment_id'], 'mp_reconciliations_unique_shipment');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('mp_reconciliations');
    }
};
