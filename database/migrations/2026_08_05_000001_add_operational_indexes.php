<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $this->addIndex('marketplace_sync_logs', ['store_id', 'created_at'], 'marketplace_sync_logs_store_created_at_index');
        $this->addIndex('marketplace_sync_logs', ['action', 'created_at'], 'marketplace_sync_logs_action_created_at_index');
        $this->addIndex('shopee_api_logs', ['created_at'], 'shopee_api_logs_created_at_index');
        $this->addIndex('webhook_logs', ['provider', 'created_at'], 'webhook_logs_provider_created_at_index');

        $this->addIndex('order_fulfillment_lines', ['fulfillment_id'], 'order_fulfillment_lines_fulfillment_id_index');
        $this->addIndex('order_fulfillment_lines', ['marketplace_order_item_id'], 'order_fulfillment_lines_order_item_id_index');
        $this->addIndex('order_fulfillment_lines', ['item_id'], 'order_fulfillment_lines_item_id_index');
        $this->addIndex('order_fulfillment_lines', ['lot_id'], 'order_fulfillment_lines_lot_id_index');
        $this->addIndex('order_fulfillment_lines', ['split_parent_id'], 'order_fulfillment_lines_split_parent_id_index');
    }

    public function down(): void
    {
        $this->dropIndex('marketplace_sync_logs', 'marketplace_sync_logs_store_created_at_index');
        $this->dropIndex('marketplace_sync_logs', 'marketplace_sync_logs_action_created_at_index');
        $this->dropIndex('shopee_api_logs', 'shopee_api_logs_created_at_index');
        $this->dropIndex('webhook_logs', 'webhook_logs_provider_created_at_index');

        $this->dropIndex('order_fulfillment_lines', 'order_fulfillment_lines_fulfillment_id_index');
        $this->dropIndex('order_fulfillment_lines', 'order_fulfillment_lines_order_item_id_index');
        $this->dropIndex('order_fulfillment_lines', 'order_fulfillment_lines_item_id_index');
        $this->dropIndex('order_fulfillment_lines', 'order_fulfillment_lines_lot_id_index');
        $this->dropIndex('order_fulfillment_lines', 'order_fulfillment_lines_split_parent_id_index');
    }

    private function addIndex(string $table, array $columns, string $index): void
    {
        if (! Schema::hasTable($table) || Schema::hasIndex($table, $index)) {
            return;
        }

        Schema::table($table, function (Blueprint $blueprint) use ($columns, $index): void {
            $blueprint->index($columns, $index);
        });
    }

    private function dropIndex(string $table, string $index): void
    {
        if (Schema::hasTable($table) && Schema::hasIndex($table, $index)) {
            Schema::table($table, function (Blueprint $blueprint) use ($index): void {
                $blueprint->dropIndex($index);
            });
        }
    }
};
