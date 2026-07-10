<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->string('resolution_type', 30)->default('refund')->after('status');
            $table->string('replacement_status', 30)->nullable()->after('resolution_type');
            $table->timestamp('replacement_expected_at')->nullable()->after('replacement_status');
            $table->timestamp('replacement_received_at')->nullable()->after('replacement_expected_at');
            $table->unsignedBigInteger('replacement_receipt_id')->nullable()->after('replacement_received_at');
        });

        Schema::table('purchase_return_lines', function (Blueprint $table) {
            $table->unsignedBigInteger('replacement_item_id')->nullable()->after('allocated_qty');
            $table->decimal('replacement_qty_expected', 12, 4)->default(0)->after('replacement_item_id');
            $table->decimal('replacement_qty_received', 12, 4)->default(0)->after('replacement_qty_expected');
        });

        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->unsignedBigInteger('source_line_id')->nullable()->after('source_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('purchase_returns', function (Blueprint $table) {
            $table->dropColumn([
                'resolution_type',
                'replacement_status',
                'replacement_expected_at',
                'replacement_received_at',
                'replacement_receipt_id',
            ]);
        });

        Schema::table('purchase_return_lines', function (Blueprint $table) {
            $table->dropColumn([
                'replacement_item_id',
                'replacement_qty_expected',
                'replacement_qty_received',
            ]);
        });

        Schema::table('inventory_mutations', function (Blueprint $table) {
            $table->dropColumn('source_line_id');
        });
    }
};
