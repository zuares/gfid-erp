<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('shipment_lines')
            ->whereNull('shipment_order_scan_id')
            ->orderBy('id')
            ->get(['id', 'shipment_id', 'created_at'])
            ->each(function ($line): void {
                $scan = DB::table('shipment_order_scans')
                    ->where('shipment_id', $line->shipment_id)
                    ->where('created_at', '<=', $line->created_at)
                    ->orderByDesc('created_at')
                    ->orderByDesc('id')
                    ->first(['id']);

                if ($scan) {
                    DB::table('shipment_lines')
                        ->where('id', $line->id)
                        ->update(['shipment_order_scan_id' => $scan->id]);
                }
            });
    }

    public function down(): void
    {
        // Relasi hasil backfill tidak diberi marker terpisah; jangan menghapus
        // relasi order baru jika migration di-rollback.
    }
};
