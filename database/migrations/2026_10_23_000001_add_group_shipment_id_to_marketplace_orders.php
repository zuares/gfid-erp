<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->string('group_shipment_id', 100)->nullable()->after('booking_sn');
            $table->index(['store_id', 'group_shipment_id'], 'marketplace_orders_store_group_idx');
        });

        DB::table('marketplace_orders')
            ->select(['id', 'raw_json'])
            ->whereNull('group_shipment_id')
            ->orderBy('id')
            ->chunkById(500, function ($orders): void {
                foreach ($orders as $order) {
                    $raw = json_decode((string) $order->raw_json, true);
                    if (!is_array($raw)) {
                        continue;
                    }

                    $groupId = collect($raw['package_list'] ?? [])
                        ->map(fn ($package) => trim((string) ($package['group_shipment_id'] ?? '')))
                        ->first(fn ($value) => $value !== '');

                    if ($groupId) {
                        DB::table('marketplace_orders')
                            ->where('id', $order->id)
                            ->update(['group_shipment_id' => $groupId]);
                    }
                }
            });
    }

    public function down(): void
    {
        Schema::table('marketplace_orders', function (Blueprint $table) {
            $table->dropIndex('marketplace_orders_store_group_idx');
            $table->dropColumn('group_shipment_id');
        });
    }
};
