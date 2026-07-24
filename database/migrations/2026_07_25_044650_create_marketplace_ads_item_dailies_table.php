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
        Schema::create('marketplace_ads_item_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained()->cascadeOnDelete();
            $table->string('channel_campaign_id')->index();
            $table->string('channel_item_id')->index();
            $table->date('date');
            
            // Metrics
            $table->integer('impressions')->default(0);
            $table->integer('clicks')->default(0);
            $table->decimal('expense', 15, 2)->default(0);
            $table->integer('broad_order')->default(0);
            $table->decimal('broad_gmv', 15, 2)->default(0);
            $table->integer('direct_order')->default(0);
            $table->decimal('direct_gmv', 15, 2)->default(0);
            $table->decimal('cpc', 15, 4)->nullable();
            
            $table->json('raw_json')->nullable();
            $table->timestamps();
            
            $table->unique(['store_id', 'channel_campaign_id', 'channel_item_id', 'date'], 'uniq_mp_ads_item_daily');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_ads_item_dailies');
    }
};
