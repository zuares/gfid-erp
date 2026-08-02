<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipments')
            ->where('scan_mode', 'item_first')
            ->whereExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('shipment_order_scans')
                    ->whereColumn('shipment_order_scans.shipment_id', 'shipments.id');
            })
            ->update(['scan_mode' => 'order_first']);
    }

    public function down(): void
    {
        // Tidak dibalik otomatis karena tidak bisa membedakan data hasil backfill
        // dengan shipment baru yang memang dibuat menggunakan order_first.
    }
};
