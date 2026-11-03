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
                'legacy_account_code' => '1201',
                'line_side' => 'debit',
                'standalone_account_code' => '1-150',
                'purpose' => 'Kelengkapan sewing_supply reject susulan kembali ke persediaan bahan/material',
            ],
            [
                'legacy_account_code' => '1204',
                'line_side' => 'credit',
                'standalone_account_code' => '1-153',
                'purpose' => 'Mengurangi komponen sewing_supply dari persediaan reject di REJ-CUT',
            ],
        ] as $mapping) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => 'sewing_pickup_reject_supply',
                    'legacy_account_code' => $mapping['legacy_account_code'],
                    'line_side' => $mapping['line_side'],
                ],
                [
                    'standalone_account_code' => $mapping['standalone_account_code'],
                    'stage' => 'sewing',
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
            ->where('source_type', 'sewing_pickup_reject_supply')
            ->delete();
    }
};
