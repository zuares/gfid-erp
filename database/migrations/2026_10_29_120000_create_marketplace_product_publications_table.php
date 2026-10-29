<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_product_publications', function (Blueprint $table) {
            $table->id();
            $table->foreignId('store_id')->constrained('stores')->cascadeOnDelete();
            $table->string('channel_code', 24);
            $table->uuid('idempotency_key');
            $table->string('status', 24)->default('pending');
            $table->string('request_hash', 64);
            $table->json('payload_json');
            $table->json('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->string('platform_product_id', 80)->nullable();
            $table->string('platform_request_id', 120)->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['store_id', 'idempotency_key'], 'mp_publications_store_idempotency_unique');
            $table->index(['store_id', 'status', 'created_at'], 'mp_publications_store_status_created_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_publications');
    }
};
