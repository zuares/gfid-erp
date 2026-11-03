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
                'legacy_account_code' => '1204',
                'line_side' => 'debit',
                'standalone_account_code' => '1-153',
                'purpose' => 'Reject sebelum jahit masuk persediaan reject di REJ-CUT',
            ],
            [
                'legacy_account_code' => '1202',
                'line_side' => 'credit',
                'standalone_account_code' => '1-151-01',
                'purpose' => 'Reject sebelum jahit keluar dari WIP-CUT',
            ],
        ] as $mapping) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => 'sewing_pickup_pre_sewing_reject',
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
            ->where('source_type', 'sewing_pickup_pre_sewing_reject')
            ->delete();
    }
};
