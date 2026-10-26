<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        if (DB::table('accounts')->where('code', '1308')->exists()) {
            return;
        }

        $now = now();

        $legacy = DB::table('accounts')
            ->where('code', '1307')
            ->where('name', 'Piutang Pinjaman Karyawan')
            ->first();

        if ($legacy
            && ! DB::table('journal_lines')->where('account_id', $legacy->id)->exists()
            && ! DB::table('employee_loans')->where('receivable_account_id', $legacy->id)->exists()) {
            DB::table('accounts')->where('id', $legacy->id)->update([
                'code' => '1308',
                'updated_at' => $now,
            ]);

            return;
        }

        DB::table('accounts')->insert([
            'code' => '1308',
            'name' => 'Piutang Pinjaman Karyawan',
            'type' => 'asset',
            'is_cash' => false,
            'is_active' => true,
            'created_at' => $now,
            'updated_at' => $now,
        ]);
    }

    public function down(): void
    {
        DB::table('accounts')
            ->where('code', '1308')
            ->where('name', 'Piutang Pinjaman Karyawan')
            ->where('type', 'asset')
            ->where('is_cash', false)
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('journal_lines')
                    ->whereColumn('journal_lines.account_id', 'accounts.id');
            })
            ->whereNotExists(function ($query) {
                $query->select(DB::raw(1))
                    ->from('employee_loans')
                    ->whereColumn('employee_loans.receivable_account_id', 'accounts.id');
            })
            ->delete();
    }
};
