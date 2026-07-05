<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasTable('shipment_return_order_scans')) {
            Schema::create('shipment_return_order_scans', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_return_id')
                    ->constrained()
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->string('order_number', 100);
                $table->unsignedBigInteger('matched_order_id')->nullable();
                $table->string('match_status', 30)->default('pending');
                $table->string('source', 30)->default('scanner');
                $table->json('raw_payload')->nullable();
                $table->timestamp('matched_at')->nullable();
                $table->foreignId('matched_by')
                    ->nullable()
                    ->constrained('users')
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->timestamps();

                $table->unique(['shipment_return_id', 'order_number'], 'sr_order_scans_return_order_unique');
                $table->index(['match_status', 'order_number'], 'sr_order_scans_status_order_idx');
            });
        } else {
            Schema::table('shipment_return_order_scans', function (Blueprint $table) {
                if (!Schema::hasColumn('shipment_return_order_scans', 'order_number')) {
                    $table->string('order_number', 100)->nullable()->after('shipment_return_id');
                }
                if (!Schema::hasColumn('shipment_return_order_scans', 'matched_order_id')) {
                    $table->unsignedBigInteger('matched_order_id')->nullable();
                }
                if (!Schema::hasColumn('shipment_return_order_scans', 'match_status')) {
                    $table->string('match_status', 30)->default('pending');
                }
                if (!Schema::hasColumn('shipment_return_order_scans', 'source')) {
                    $table->string('source', 30)->default('scanner');
                }
                if (!Schema::hasColumn('shipment_return_order_scans', 'matched_at')) {
                    $table->timestamp('matched_at')->nullable();
                }
                if (!Schema::hasColumn('shipment_return_order_scans', 'matched_by')) {
                    $table->unsignedBigInteger('matched_by')->nullable();
                }
            });
        }

        if (!Schema::hasTable('shipment_return_order_scan_items')) {
            Schema::create('shipment_return_order_scan_items', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_return_order_scan_id')
                    ->constrained()
                    ->cascadeOnDelete()
                    ->cascadeOnUpdate();
                $table->foreignId('item_id')
                    ->constrained()
                    ->cascadeOnUpdate();
                $table->foreignId('shipment_return_line_id')
                    ->nullable()
                    ->constrained()
                    ->nullOnDelete()
                    ->cascadeOnUpdate();
                $table->unsignedBigInteger('matched_order_item_id')->nullable();
                $table->integer('qty_scanned')->default(0);
                $table->integer('qty_expected')->nullable();
                $table->string('match_status', 30)->default('pending');
                $table->json('raw_payload')->nullable();
                $table->timestamps();

                $table->unique(['shipment_return_order_scan_id', 'item_id'], 'sr_order_scan_items_scan_item_unique');
                $table->index(['item_id', 'match_status'], 'sr_order_scan_items_item_status_idx');
            });
        } else {
            Schema::table('shipment_return_order_scan_items', function (Blueprint $table) {
                if (!Schema::hasColumn('shipment_return_order_scan_items', 'shipment_return_line_id')) {
                    $table->unsignedBigInteger('shipment_return_line_id')->nullable();
                }
                if (!Schema::hasColumn('shipment_return_order_scan_items', 'matched_order_item_id')) {
                    $table->unsignedBigInteger('matched_order_item_id')->nullable();
                }
                if (!Schema::hasColumn('shipment_return_order_scan_items', 'qty_scanned')) {
                    $table->integer('qty_scanned')->default(0);
                }
                if (!Schema::hasColumn('shipment_return_order_scan_items', 'qty_expected')) {
                    $table->integer('qty_expected')->nullable();
                }
                if (!Schema::hasColumn('shipment_return_order_scan_items', 'match_status')) {
                    $table->string('match_status', 30)->default('pending');
                }
                if (!Schema::hasColumn('shipment_return_order_scan_items', 'raw_payload')) {
                    $table->json('raw_payload')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        Schema::dropIfExists('shipment_return_order_scan_items');
        Schema::dropIfExists('shipment_return_order_scans');
    }
};
