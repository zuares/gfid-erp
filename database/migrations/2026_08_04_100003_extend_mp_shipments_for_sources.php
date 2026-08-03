<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('mp_shipments', function (Blueprint $table) {
            if (! Schema::hasColumn('mp_shipments', 'marketplace_order_id')) {
                $table->foreignId('marketplace_order_id')->nullable()->after('store_id')->constrained('marketplace_orders')->nullOnDelete();
            }
            if (! Schema::hasColumn('mp_shipments', 'source_type')) {
                $table->string('source_type', 20)->default('import')->after('imported_at');
            }
            if (! Schema::hasColumn('mp_shipments', 'source_updated_at')) {
                $table->dateTime('source_updated_at')->nullable()->after('source_type');
            }
        });

        // Store harus menjadi bagian dari identitas shipment agar order ID yang
        // sama pada dua toko tidak saling menimpa. Gagal dengan jelas jika data
        // lama sudah memiliki konflik, bukan diam-diam melewati constraint baru.
        $duplicates = DB::table('mp_shipments')
            ->select('store_id', 'channel', 'platform_order_id', 'platform_shipment_id')
            ->groupBy('store_id', 'channel', 'platform_order_id', 'platform_shipment_id')
            ->havingRaw('COUNT(*) > 1')
            ->limit(1)
            ->first();

        if ($duplicates) {
            throw new RuntimeException(
                'Migration dibatalkan: ditemukan shipment duplikat untuk kombinasi store/channel/order/package. '
                . 'Bersihkan data lama secara manual sebelum migrate.'
            );
        }

        Schema::table('mp_shipments', function (Blueprint $table) {
            $table->dropUnique('mp_shipments_unique_order_pkg');
            $table->unique(
                ['store_id', 'channel', 'platform_order_id', 'platform_shipment_id'],
                'mp_shipments_store_order_pkg_unique'
            );
        });
    }

    public function down(): void
    {
        Schema::table('mp_shipments', function (Blueprint $table) {
            try { $table->dropUnique('mp_shipments_store_order_pkg_unique'); } catch (Throwable) {}
            try { $table->unique(['channel', 'platform_order_id', 'platform_shipment_id'], 'mp_shipments_unique_order_pkg'); } catch (Throwable) {}
            try { $table->dropForeign(['marketplace_order_id']); } catch (Throwable) {}
            foreach (['marketplace_order_id', 'source_type', 'source_updated_at'] as $column) {
                if (Schema::hasColumn('mp_shipments', $column)) {
                    $table->dropColumn($column);
                }
            }
        });
    }
};
