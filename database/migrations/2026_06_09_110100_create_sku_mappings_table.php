<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sku_mappings', function (Blueprint $table) {
            $table->id();

            // SKU dari marketplace (model_sku atau item_sku dari Shopee)
            $table->string('marketplace_sku')->index();

            // Channel: shopee, tiktok, tokopedia, dll (nullable = berlaku semua channel)
            $table->string('channel_code')->nullable()->index();

            // Item internal yang dipetakan
            $table->foreignId('item_id')
                ->constrained('items')
                ->cascadeOnDelete();

            $table->string('notes')->nullable();
            $table->timestamps();

            // Satu marketplace_sku + channel = satu mapping
            $table->unique(['marketplace_sku', 'channel_code']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sku_mappings');
    }
};
