<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ad_campaigns', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')
                ->nullable()
                ->constrained('stores')
                ->nullOnDelete();

            // Identitas campaign dari API
            $table->string('channel_campaign_id', 80)->index();
            $table->string('campaign_name', 255)->nullable();
            $table->string('campaign_type', 40)->nullable(); // SEARCH_ADS / DISCOVERY_ADS / SHOP_SEARCH_ADS
            $table->string('status', 40)->nullable();        // ONGOING / SUSPENDED / ENDED

            // Periode report
            $table->date('report_date_from');
            $table->date('report_date_to');

            // ── Metrics ───────────────────────────────────────────────────────
            $table->decimal('spend', 15, 2)->default(0);          // biaya iklan
            $table->bigInteger('impressions')->default(0);
            $table->bigInteger('clicks')->default(0);
            $table->decimal('ctr', 10, 6)->nullable();            // click-through rate (0..1)
            $table->integer('orders')->default(0);                 // konversi / order
            $table->integer('items_sold')->default(0);
            $table->decimal('gmv', 15, 2)->default(0);            // attributed sales
            $table->decimal('direct_gmv', 15, 2)->default(0);     // direct attributed sales
            $table->decimal('roas', 10, 4)->nullable();
            $table->decimal('direct_roas', 10, 4)->nullable();
            $table->decimal('cpc', 10, 4)->nullable();            // cost per click
            $table->decimal('cvr', 10, 6)->nullable();            // conversion rate (0..1)

            // Break-even ACOS manual (bisa di-override user, default null)
            $table->decimal('break_even_acos', 8, 4)->nullable()
                ->comment('Null = hitung otomatis dari HPP. Set manual jika mau override.');

            // Meta
            $table->json('raw_json')->nullable();
            $table->timestamp('synced_at')->nullable();
            $table->timestamps();

            // Anti duplikat: 1 campaign, 1 periode
            $table->unique(
                ['store_id', 'channel_campaign_id', 'report_date_from', 'report_date_to'],
                'uniq_mp_ad_campaigns_period'
            );

            $table->index(['store_id', 'report_date_from', 'report_date_to'], 'idx_mp_ad_campaigns_store_period');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ad_campaigns');
    }
};
