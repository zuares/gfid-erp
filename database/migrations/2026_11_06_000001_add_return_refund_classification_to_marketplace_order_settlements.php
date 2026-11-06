<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (! Schema::hasTable('marketplace_order_settlements')) {
            return;
        }

        Schema::table('marketplace_order_settlements', function (Blueprint $table): void {
            if (! Schema::hasColumn('marketplace_order_settlements', 'return_refund_status')) {
                $table->string('return_refund_status', 32)->default('none')->index();
            }
            if (! Schema::hasColumn('marketplace_order_settlements', 'refund_source')) {
                $table->string('refund_source', 120)->nullable();
            }
            if (! Schema::hasColumn('marketplace_order_settlements', 'reversal_amount')) {
                $table->decimal('reversal_amount', 15, 2)->default(0);
            }
            if (! Schema::hasColumn('marketplace_order_settlements', 'reversal_source')) {
                $table->string('reversal_source', 120)->nullable();
            }
            if (! Schema::hasColumn('marketplace_order_settlements', 'refund_detected_at')) {
                $table->timestamp('refund_detected_at')->nullable();
            }
            if (! Schema::hasColumn('marketplace_order_settlements', 'source_transaction_id')) {
                $table->string('source_transaction_id', 120)->nullable()->index();
            }
        });
    }

    public function down(): void
    {
        if (! Schema::hasTable('marketplace_order_settlements')) {
            return;
        }

        Schema::table('marketplace_order_settlements', function (Blueprint $table): void {
            foreach ([
                'return_refund_status',
                'refund_source',
                'reversal_amount',
                'reversal_source',
                'refund_detected_at',
                'source_transaction_id',
            ] as $column) {
                if (Schema::hasColumn('marketplace_order_settlements', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
