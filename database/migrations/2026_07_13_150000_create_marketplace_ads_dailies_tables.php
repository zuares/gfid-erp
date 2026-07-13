<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Performa iklan harian per toko (dari get_all_cpc_ads_daily_performance)
        Schema::create('marketplace_ads_dailies', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->date('date');
            $table->unsignedBigInteger('impressions')->default(0);
            $table->unsignedBigInteger('clicks')->default(0);
            $table->decimal('ctr', 8, 4)->nullable();
            $table->decimal('spend', 15, 2)->default(0);
            $table->unsignedInteger('orders')->default(0);
            $table->decimal('gmv', 15, 2)->default(0);
            $table->decimal('roas', 10, 4)->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'date']);
            $table->index('date');
        });

        // Riwayat saldo iklan (snapshot tiap sync — untuk pantau burn rate)
        Schema::create('marketplace_ads_balance_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->decimal('balance', 15, 2);
            $table->timestamps();

            $table->index(['store_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ads_balance_logs');
        Schema::dropIfExists('marketplace_ads_dailies');
    }
};
