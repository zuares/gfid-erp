<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('standalone_production_account_mappings')
            ->where('source_type', 'sewing_reject_rework_ok')
            ->where('legacy_account_code', '1203')
            ->where('line_side', 'debit')
            ->update([
                'standalone_account_code' => '1-151-03',
                'stage' => 'sewing',
                'purpose' => 'Setor ulang reject masuk WIP Finishing',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('standalone_production_account_mappings')
            ->where('source_type', 'sewing_reject_rework_ok')
            ->where('legacy_account_code', '1203')
            ->where('line_side', 'debit')
            ->update([
                'standalone_account_code' => '1-152',
                'stage' => 'sewing',
                'purpose' => 'Rework reject menjadi barang jadi',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
