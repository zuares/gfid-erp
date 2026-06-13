<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('fulfillment_audit_logs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_fulfillment_id')
                  ->constrained('order_fulfillments')
                  ->cascadeOnDelete();
            $table->foreignId('order_fulfillment_line_id')
                  ->nullable()
                  ->constrained('order_fulfillment_lines')
                  ->nullOnDelete();
            $table->foreignId('user_id')
                  ->nullable()
                  ->constrained('users')
                  ->nullOnDelete();
            $table->string('action', 64); // e.g. split, restore_split, confirm, toggle_picked, etc.
            $table->json('meta')->nullable();
            $table->timestamp('created_at')->useCurrent();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('fulfillment_audit_logs');
    }
};
