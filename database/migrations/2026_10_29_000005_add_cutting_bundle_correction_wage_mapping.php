<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        foreach ([
            ['6120', 'debit', '6-140', 'Beban koreksi/spoilage produksi'],
            ['1202', 'credit', '1-151-01', 'WIP cutting dikurangi saat reversal upah'],
        ] as [$legacyCode, $lineSide, $standaloneCode, $purpose]) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => 'cutting_bundle_correction_wage_void',
                    'legacy_account_code' => $legacyCode,
                    'line_side' => $lineSide,
                ],
                [
                    'standalone_account_code' => $standaloneCode,
                    'stage' => 'cutting',
                    'purpose' => $purpose,
                    'is_active' => true,
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_production_account_mappings')
            ->where('source_type', 'cutting_bundle_correction_wage_void')
            ->delete();
    }
};
