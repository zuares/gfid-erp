<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('marketplace_product_change_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('marketplace_product_id')
                ->constrained('marketplace_products')
                ->cascadeOnDelete();
            $table->string('action', 40);
            $table->string('status', 16)->default('success');
            $table->json('before_json')->nullable();
            $table->json('after_json')->nullable();
            $table->json('response_json')->nullable();
            $table->text('error_message')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->index(['marketplace_product_id', 'created_at'], 'mp_change_logs_product_created_index');
            $table->index(['marketplace_product_id', 'action'], 'mp_change_logs_product_action_index');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('marketplace_product_change_logs');
    }
};
