<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('journals')
            ->whereNull('voided_at')
            ->where('description', 'like', 'Reversal:%')
            ->whereNotIn('source_type', [
                'opening_balance_void',
                'opening_balance_batch_void',
            ])
            ->update([
                'voided_at' => DB::raw('COALESCE(posted_at, updated_at, created_at)'),
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('journals')
            ->whereNotNull('voided_at')
            ->where('description', 'like', 'Reversal:%')
            ->whereNotIn('source_type', [
                'opening_balance_void',
                'opening_balance_batch_void',
            ])
            ->update([
                'voided_at' => null,
                'updated_at' => now(),
            ]);
    }
};
