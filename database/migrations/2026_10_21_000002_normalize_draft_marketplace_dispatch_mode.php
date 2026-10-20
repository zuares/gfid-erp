<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Draft marketplace shipments were unintentionally created as daily
     * shipments while the create form had no wave selector. Keep explicitly
     * posted daily shipments intact, but return unfinished drafts to the
     * single-posting flow.
     */
    public function up(): void
    {
        if (!Schema::hasTable('shipments')
            || !Schema::hasTable('shipment_waves')
            || !Schema::hasColumn('shipments', 'dispatch_mode')) {
            return;
        }

        DB::table('shipments')
            ->where('shipment_type', 'marketplace')
            ->where('status', 'draft')
            ->where('dispatch_mode', 'daily')
            ->whereNotIn('id', DB::table('shipment_waves')
                ->where('status', 'posted')
                ->select('shipment_id'))
            ->update(['dispatch_mode' => 'single']);
    }

    public function down(): void
    {
        // The previous dispatch mode was an accidental default and cannot be
        // restored safely after operators continue working on these drafts.
    }
};
