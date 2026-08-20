<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_ads_campaign_schedules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('channel_campaign_id', 100);
            $table->string('action', 20); // pause|resume|budget
            $table->dateTime('scheduled_at');
            $table->string('status', 20)->default('pending'); // pending|queued|running|completed|failed|cancelled
            $table->dateTime('executed_at')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('meta')->nullable();
            $table->timestamps();

            $table->index(['status', 'scheduled_at']);
            $table->index(['store_id', 'channel_campaign_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_ads_campaign_schedules');
    }
};
