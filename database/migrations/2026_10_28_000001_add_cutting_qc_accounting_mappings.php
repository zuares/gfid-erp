<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $now = now();

        foreach ($this->mappings() as $mapping) {
            DB::table('standalone_production_account_mappings')->updateOrInsert(
                [
                    'source_type' => $mapping['source_type'],
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
            ->whereIn('source_type', [
                'cutting_qc_adjust',
                'cutting_qc_bundle_void',
                'cutting_qc_bundle_repost',
            ])
            ->delete();
    }

    /** @return array<int, array<string, string>> */
    private function mappings(): array
    {
        $rows = [];
        foreach (['cutting_qc_adjust', 'cutting_qc_bundle_void', 'cutting_qc_bundle_repost'] as $sourceType) {
            foreach ([
                ['1202', 'debit', '1-151-01', 'WIP cutting bertambah/kembali'],
                ['1202', 'credit', '1-151-01', 'WIP cutting berkurang/direversal'],
                ['1204', 'debit', '1-153', 'Reject cutting bertambah/kembali'],
                ['1204', 'credit', '1-153', 'Reject cutting berkurang/direversal'],
                ['2102', 'debit', '2-130', 'Akrual biaya produksi dibalik'],
                ['2102', 'credit', '2-130', 'Akrual biaya produksi ditambahkan'],
            ] as [$legacyCode, $side, $standaloneCode, $purpose]) {
                $rows[] = [
                    'source_type' => $sourceType,
                    'legacy_account_code' => $legacyCode,
                    'line_side' => $side,
                    'standalone_account_code' => $standaloneCode,
                    'purpose' => $purpose,
                ];
            }
        }

        return $rows;
    }
};
