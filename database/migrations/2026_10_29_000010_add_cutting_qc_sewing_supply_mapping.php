<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ([
            [
                'legacy_account_code' => '1202',
                'line_side' => 'debit',
                'standalone_account_code' => '1-151-01',
                'purpose' => 'BOM kelengkapan jahit menambah WIP Cutting saat QC',
            ],
            [
                'legacy_account_code' => '1201',
                'line_side' => 'credit',
                'standalone_account_code' => '1-150',
                'purpose' => 'BOM kelengkapan jahit keluar dari persediaan bahan saat QC',
            ],
        ] as $mapping) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => 'cutting_qc_sewing_supply',
                    'legacy_account_code' => $mapping['legacy_account_code'],
                    'line_side' => $mapping['line_side'],
                ],
                [
                    'standalone_account_code' => $mapping['standalone_account_code'],
                    'stage' => 'cutting',
                    'purpose' => $mapping['purpose'],
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
            ->where('source_type', 'cutting_qc_sewing_supply')
            ->delete();
    }
};
