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
        Schema::create('marketplace_ads_campaign_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('campaign_id')->constrained('marketplace_ad_campaigns')->cascadeOnDelete();
            $table->string('channel_item_id', 80)->index();
            $table->string('product_name', 255)->nullable();
            $table->string('status', 50)->nullable();
            $table->timestamps();
            
            $table->unique(['campaign_id', 'channel_item_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_ads_campaign_items');
    }
};
