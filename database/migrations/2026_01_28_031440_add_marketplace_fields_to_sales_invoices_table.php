<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{

    private function sqliteIndexExists(string $table, string $index): bool
    {
        $indexes = DB::select("PRAGMA index_list('$table')");

        foreach ($indexes as $row) {
            if (($row->name ?? null) === $index) {
                return true;
            }
        }

        return false;
    }
    public function up(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if (! Schema::hasColumn('sales_invoices', 'channel')) {
                $table->string('channel', 30)->nullable()->index();
            }
            if (! Schema::hasColumn('sales_invoices', 'channel_order_no')) {
                $table->string('channel_order_no', 120)->nullable()->index();
            }
            if (! Schema::hasColumn('sales_invoices', 'paid_at')) {
                $table->dateTime('paid_at')->nullable()->index();
            }
            if (! Schema::hasColumn('sales_invoices', 'completed_at')) {
                $table->dateTime('completed_at')->nullable()->index();
            }
            if (! Schema::hasColumn('sales_invoices', 'marketplace_status')) {
                $table->string('marketplace_status', 30)->nullable()->index();
            }
            if (! Schema::hasColumn('sales_invoices', 'awb')) {
                $table->string('awb', 80)->nullable()->index();
            }

            if (! $this->sqliteIndexExists('sales_invoices', 'si_store_order_lookup')) {
                $table->index(['store_id', 'channel_order_no'], 'si_store_order_lookup');
            }
        });
    }

    public function down(): void
    {
        Schema::table('sales_invoices', function (Blueprint $table) {
            if ($this->sqliteIndexExists('sales_invoices', 'si_store_order_lookup')) {
                $table->dropIndex('si_store_order_lookup');
            }
            $table->dropColumn(['channel', 'channel_order_no', 'paid_at', 'completed_at', 'marketplace_status', 'awb']);
        });
    }
};
