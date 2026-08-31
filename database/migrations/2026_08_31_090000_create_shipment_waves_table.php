<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (!Schema::hasColumn('shipments', 'dispatch_mode')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->string('dispatch_mode', 20)->default('single')->after('scan_mode')->index();
            });
        }

        if (!Schema::hasTable('shipment_waves')) {
            Schema::create('shipment_waves', function (Blueprint $table) {
                $table->id();
                $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
                $table->unsignedInteger('sequence');
                $table->string('code', 80);
                $table->string('label', 40)->nullable();
                $table->string('status', 20)->default('open'); // open | posted | cancelled
                $table->unsignedInteger('total_qty')->default(0);
                $table->timestamp('opened_at')->nullable();
                $table->timestamp('posted_at')->nullable();
                $table->foreignId('posted_by')->nullable()->constrained('users')->nullOnDelete();
                $table->timestamps();

                $table->unique(['shipment_id', 'sequence'], 'shipment_waves_shipment_sequence_unique');
                $table->index(['shipment_id', 'status'], 'shipment_waves_shipment_status_index');
            });
        }

        if (!Schema::hasColumn('shipment_lines', 'shipment_wave_id')) {
            Schema::table('shipment_lines', function (Blueprint $table) {
                $table->foreignId('shipment_wave_id')
                    ->nullable()
                    ->after('shipment_id')
                    ->constrained('shipment_waves')
                    ->nullOnDelete();
            });
        }

        if (!Schema::hasColumn('shipment_order_scans', 'shipment_wave_id')) {
            Schema::table('shipment_order_scans', function (Blueprint $table) {
                $table->foreignId('shipment_wave_id')
                    ->nullable()
                    ->after('shipment_id')
                    ->constrained('shipment_waves')
                    ->nullOnDelete();
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasColumn('shipment_order_scans', 'shipment_wave_id')) {
            Schema::table('shipment_order_scans', function (Blueprint $table) {
                $table->dropConstrainedForeignId('shipment_wave_id');
            });
        }

        if (Schema::hasColumn('shipment_lines', 'shipment_wave_id')) {
            Schema::table('shipment_lines', function (Blueprint $table) {
                $table->dropConstrainedForeignId('shipment_wave_id');
            });
        }

        Schema::dropIfExists('shipment_waves');

        if (Schema::hasColumn('shipments', 'dispatch_mode')) {
            Schema::table('shipments', function (Blueprint $table) {
                $table->dropIndex(['dispatch_mode']);
                $table->dropColumn('dispatch_mode');
            });
        }
    }
};
