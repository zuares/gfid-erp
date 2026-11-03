<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            ['1201', 'debit', '1-150', 'Material dikembalikan ke persediaan bahan baku dari koreksi bundle'],
            ['1202', 'credit', '1-151-01', 'WIP Cutting dikurangi karena material bundle dikembalikan'],
        ] as [$legacyCode, $lineSide, $standaloneCode, $purpose]) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => 'cutting_bundle_material_return',
                    'legacy_account_code' => $legacyCode,
                    'line_side' => $lineSide,
                ],
                [
                    'standalone_account_code' => $standaloneCode,
                    'stage' => 'cutting',
                    'purpose' => $purpose,
                    'is_active' => true,
                    'created_at' => $now,
                    'updated_at' => $now,
                ],
            );
        }
    }

    public function down(): void
    {
        DB::table('standalone_production_account_mappings')
            ->where('source_type', 'cutting_bundle_material_return')
            ->delete();
    }
};
