<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * PR-D: Additive — tambah converted_to_po_id + converted_at ke purchase_requests.
 * Nullable FK ke purchase_orders (nullOnDelete) + nullable timestamp.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (!Schema::hasColumn('purchase_requests', 'converted_to_po_id')) {
                $table->unsignedBigInteger('converted_to_po_id')
                    ->nullable()
                    ->after('rejected_by');

                $table->foreign('converted_to_po_id')
                    ->references('id')
                    ->on('purchase_orders')
                    ->nullOnDelete();
            }

            if (!Schema::hasColumn('purchase_requests', 'converted_at')) {
                $table->timestamp('converted_at')
                    ->nullable()
                    ->after('converted_to_po_id');
            }
        });
    }

    public function down(): void
    {
        Schema::table('purchase_requests', function (Blueprint $table) {
            if (Schema::hasColumn('purchase_requests', 'converted_at')) {
                $table->dropColumn('converted_at');
            }
            if (Schema::hasColumn('purchase_requests', 'converted_to_po_id')) {
                $table->dropForeign(['converted_to_po_id']);
                $table->dropColumn('converted_to_po_id');
            }
        });
    }
};
