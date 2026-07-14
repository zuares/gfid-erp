<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('product_name_sku_mappings', function (Blueprint $table) {
            $table->id();
            $table->string('item_name', 500);
            $table->string('variant_name', 500)->nullable();

            // sha1(lower(item_name)|lower(variant_name)) — untuk lookup & unique index
            $table->char('lookup_hash', 40);

            $table->string('channel_code', 50)->nullable();
            $table->string('marketplace_sku', 100);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['lookup_hash', 'channel_code'], 'pnsm_hash_channel_unique');
            $table->index('lookup_hash', 'pnsm_hash_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('product_name_sku_mappings');
    }
};
