<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_order_scans', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained()->cascadeOnDelete();
            $table->string('order_no', 200);
            $table->string('status', 20)->default('pending');
            $table->string('source', 30)->default('manual_scan');
            $table->json('raw_payload')->nullable();
            $table->timestamp('confirmed_at')->nullable();
            $table->foreignId('confirmed_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['shipment_id', 'order_no'], 'shipment_order_scans_unique');
            $table->index(['shipment_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_order_scans');
    }
};
