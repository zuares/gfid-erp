<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_imported_ad_spends', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('channel', 20);
            $table->date('date');
            $table->decimal('spend', 18, 2)->default(0);
            $table->string('source_file')->nullable();
            $table->uuid('import_batch_id')->nullable();
            $table->json('raw_json')->nullable();
            $table->timestamps();

            // Re-importing a report replaces the imported daily snapshot. API
            // advertising facts remain in marketplace_ads_dailies and are
            // preferred by the dashboard when available.
            $table->unique(['store_id', 'channel', 'date'], 'mp_imported_ads_store_channel_date_unique');
            $table->index(['channel', 'date'], 'mp_imported_ads_channel_date_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_imported_ad_spends');
    }
};
