<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        if (Schema::hasTable('marketplace_orders')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                // Canonical event dates for the active marketplace pipeline.
                foreach (['paid_at', 'shipped_at', 'delivered_at'] as $column) {
                    if (! Schema::hasColumn('marketplace_orders', $column)) {
                        $table->dateTime($column)->nullable()->index();
                    }
                }

                if (! Schema::hasColumn('marketplace_orders', 'financial_data_status')) {
                    $table->string('financial_data_status', 30)
                        ->default('unknown')
                        ->index(); // unknown | incomplete | ready | not_applicable
                }

                if (! Schema::hasColumn('marketplace_orders', 'financial_issue_reason')) {
                    $table->string('financial_issue_reason', 60)->nullable();
                }

                if (! Schema::hasColumn('marketplace_orders', 'financial_checked_at')) {
                    $table->dateTime('financial_checked_at')->nullable();
                }
            });
        }

        if (Schema::hasTable('marketplace_order_settlements')) {
            Schema::table('marketplace_order_settlements', function (Blueprint $table) {
                if (! Schema::hasColumn('marketplace_order_settlements', 'data_status')) {
                    $table->string('data_status', 30)
                        ->default('unknown')
                        ->index(); // unknown | incomplete | complete
                }

                if (! Schema::hasColumn('marketplace_order_settlements', 'data_quality_flags')) {
                    $table->json('data_quality_flags')->nullable();
                }

                if (! Schema::hasColumn('marketplace_order_settlements', 'data_checked_at')) {
                    $table->dateTime('data_checked_at')->nullable();
                }
            });
        }
    }

    public function down(): void
    {
        if (Schema::hasTable('marketplace_order_settlements')) {
            Schema::table('marketplace_order_settlements', function (Blueprint $table) {
                foreach (['data_status', 'data_quality_flags', 'data_checked_at'] as $column) {
                    if (Schema::hasColumn('marketplace_order_settlements', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }

        if (Schema::hasTable('marketplace_orders')) {
            Schema::table('marketplace_orders', function (Blueprint $table) {
                foreach ([
                    'paid_at', 'shipped_at', 'delivered_at',
                    'financial_data_status', 'financial_issue_reason', 'financial_checked_at',
                ] as $column) {
                    if (Schema::hasColumn('marketplace_orders', $column)) {
                        $table->dropColumn($column);
                    }
                }
            });
        }
    }
};
