<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach (['debit', 'credit'] as $lineSide) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => 'cutting_bundle_correction_wip_void',
                    'legacy_account_code' => '1202',
                    'line_side' => $lineSide,
                ],
                [
                    'standalone_account_code' => '1-151-01',
                    'stage' => 'cutting',
                    'is_active' => true,
                    'purpose' => 'Reversal audit saldo WIP-CUT tertinggal dari koreksi bundle',
                    'updated_at' => now(),
                    'created_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_production_account_mappings')
            ->where('source_type', 'cutting_bundle_correction_wip_void')
            ->where('legacy_account_code', '1202')
            ->delete();
    }
};
