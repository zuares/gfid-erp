<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('standalone_accounting_accounts')
            ->where('code', '6-200')
            ->update([
                'code' => '6-210',
                'name' => 'Other Expense / Beban Lain-lain',
                'description' => 'Beban non-operasional atau beban lain yang tidak termasuk beban operasional rutin dan belum memiliki akun khusus.',
                'type' => 'expense',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('standalone_accounting_accounts')
            ->where('code', '6-210')
            ->update([
                'code' => '6-200',
                'name' => 'Other Operating Expense / Beban Operasional Lainnya',
                'description' => 'Beban operasional yang belum memiliki akun khusus; perlu ditinjau berkala untuk reklasifikasi.',
                'type' => 'expense',
                'is_active' => true,
                'updated_at' => now(),
            ]);
    }
};
