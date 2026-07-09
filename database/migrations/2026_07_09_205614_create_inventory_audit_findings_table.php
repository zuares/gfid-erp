<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('inventory_audit_findings', function (Blueprint $table) {
            $table->id();
            $table->string('category', 50)->index();
            $table->string('severity', 20)->default('medium');
            $table->string('title');
            $table->text('message');
            
            $table->unsignedBigInteger('item_id')->nullable()->index();
            $table->unsignedBigInteger('shipment_id')->nullable()->index();
            $table->unsignedBigInteger('fulfillment_id')->nullable()->index();
            
            $table->json('payload')->nullable();
            $table->timestamp('detected_at')->nullable();
            $table->timestamp('resolved_at')->nullable()->index();
            
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('inventory_audit_findings');
    }
};
