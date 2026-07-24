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
        Schema::create('marketplace_ads_hourly_performances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->foreignId('campaign_id')->nullable()->constrained('marketplace_ad_campaigns')->nullOnDelete();
            $table->string('channel_campaign_id', 80)->nullable()->index();
            $table->date('performance_date');
            $table->integer('performance_hour');
            $table->bigInteger('impression')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->decimal('ctr', 10, 6)->nullable();
            $table->decimal('expense', 15, 2)->default(0);
            $table->decimal('broad_gmv', 15, 2)->default(0);
            $table->integer('broad_order')->default(0);
            $table->decimal('broad_order_amount', 15, 2)->default(0);
            $table->decimal('broad_roi', 10, 4)->nullable();
            $table->decimal('broad_cir', 10, 4)->nullable();
            $table->decimal('conversion_rate', 10, 6)->nullable();
            $table->decimal('cpc', 10, 4)->nullable();
            $table->integer('direct_order')->default(0);
            $table->decimal('direct_order_amount', 15, 2)->default(0);
            $table->decimal('direct_gmv', 15, 2)->default(0);
            $table->decimal('direct_roi', 10, 4)->nullable();
            $table->decimal('direct_cir', 10, 4)->nullable();
            $table->decimal('direct_conversion_rate', 10, 6)->nullable();
            $table->decimal('cost_per_direct_conversion', 10, 4)->nullable();
            $table->json('raw_response')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            $table->unique(
                ['store_id', 'channel_campaign_id', 'performance_date', 'performance_hour'],
                'uniq_mp_ads_hourly'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('marketplace_ads_hourly_performances');
    }
};
