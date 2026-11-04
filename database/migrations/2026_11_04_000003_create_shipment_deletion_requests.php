<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipment_deletion_requests', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')
                ->constrained('shipments')
                ->cascadeOnDelete();
            $table->string('target_type', 20); // order | item
            $table->foreignId('shipment_order_scan_id')
                ->nullable()
                ->constrained('shipment_order_scans')
                ->nullOnDelete();
            $table->foreignId('shipment_line_id')
                ->nullable()
                ->constrained('shipment_lines')
                ->nullOnDelete();
            $table->foreignId('requested_by')
                ->constrained('users')
                ->restrictOnDelete();
            $table->foreignId('reviewed_by')
                ->nullable()
                ->constrained('users')
                ->nullOnDelete();
            $table->string('status', 20)->default('pending'); // pending | approved | rejected
            $table->string('target_label', 200)->nullable();
            $table->text('reason')->nullable();
            $table->text('review_note')->nullable();
            $table->timestamp('reviewed_at')->nullable();
            $table->timestamps();

            $table->index(['status', 'created_at'], 'shipment_deletion_status_created_idx');
            $table->index(['shipment_id', 'target_type', 'status'], 'shipment_deletion_target_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_deletion_requests');
    }
};
