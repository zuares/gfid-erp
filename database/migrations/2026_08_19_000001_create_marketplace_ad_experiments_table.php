<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ad_experiments', function (Blueprint $table) {
            $table->id();

            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->uuid('change_event_id')->index();
            $table->string('channel_campaign_id', 80)->nullable();
            $table->string('channel_item_id', 80)->nullable();
            $table->foreignId('internal_item_id')->nullable()->constrained('items')->nullOnDelete();

            $table->string('change_type', 32);
            $table->decimal('old_price', 15, 2)->nullable();
            $table->decimal('new_price', 15, 2)->nullable();
            $table->decimal('old_target_roas', 10, 4)->nullable();
            $table->decimal('new_target_roas', 10, 4)->nullable();
            $table->timestamp('changed_at');
            $table->date('effective_date');

            $table->string('lifecycle_status', 32)->default('LEARNING');
            $table->string('verdict', 32)->nullable();
            $table->string('profit_basis', 16)->default('incomplete');
            $table->string('source_granularity', 16)->nullable();
            $table->string('mapping_status', 32)->default('missing_mapping');
            $table->boolean('confounded')->default(false);
            $table->json('data_quality_flags')->nullable();
            $table->json('conflict_reason')->nullable();
            $table->json('calculation_snapshot')->nullable();

            $table->unsignedTinyInteger('baseline_days')->default(7);
            $table->unsignedTinyInteger('observation_days')->default(7);
            $table->string('calculation_version', 32)->default('phase1-v1');
            $table->timestamp('evaluated_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->unsignedBigInteger('created_by')->nullable()->index();
            $table->timestamps();

            $table->index(['store_id', 'effective_date'], 'idx_ads_experiments_store_date');
            $table->index(['store_id', 'lifecycle_status'], 'idx_ads_experiments_store_status');
            $table->index(['store_id', 'channel_campaign_id', 'channel_item_id'], 'idx_ads_experiments_scope');
            $table->index(['change_type', 'effective_date'], 'idx_ads_experiments_type_date');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ad_experiments');
    }
};
