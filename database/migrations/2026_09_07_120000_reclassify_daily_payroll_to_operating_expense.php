<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        $expenseAccountId = DB::table('accounts')
            ->where('code', '6103')
            ->where('type', 'expense')
            ->value('id');
        $wipAccountId = DB::table('accounts')
            ->where('code', '1202')
            ->value('id');

        // Tidak ada yang direklasifikasi bila COA belum siap. Pada database
        // operasional akun ini sudah tersedia dari AccountSeeder.
        if (! $expenseAccountId || ! $wipAccountId) {
            return;
        }

        DB::transaction(function () use ($expenseAccountId, $wipAccountId) {
            $oldAccruals = DB::table('journals as j')
                ->join('piecework_payroll_periods as p', function ($join) {
                    $join->on('p.id', '=', 'j.source_id')
                        ->where('j.source_type', '=', 'piecework_payroll_period_accrual');
                })
                ->where('p.module', 'daily')
                ->whereNull('j.voided_at')
                ->whereExists(function ($query) use ($wipAccountId) {
                    $query->selectRaw('1')
                        ->from('journal_lines as jl')
                        ->whereColumn('jl.journal_id', 'j.id')
                        ->where('jl.account_id', $wipAccountId)
                        ->where('jl.debit', '>', 0);
                })
                ->select([
                    'j.id as journal_id',
                    'j.date',
                    'j.source_id as payroll_period_id',
                    'p.period_start',
                    'p.period_end',
                ])
                ->orderBy('j.id')
                ->get();

            foreach ($oldAccruals as $oldAccrual) {
                $amount = (float) DB::table('journal_lines')
                    ->where('journal_id', $oldAccrual->journal_id)
                    ->where('account_id', $wipAccountId)
                    ->selectRaw('COALESCE(SUM(debit - credit), 0) as amount')
                    ->value('amount');

                if ($amount <= 0) {
                    continue;
                }

                $alreadyReclassified = DB::table('journals')
                    ->where('source_type', 'daily_payroll_operating_expense_reclass')
                    ->where('source_id', $oldAccrual->payroll_period_id)
                    ->whereNull('voided_at')
                    ->exists();

                if ($alreadyReclassified) {
                    continue;
                }

                $now = now();
                $reclassJournalId = DB::table('journals')->insertGetId([
                    'date' => $oldAccrual->date,
                    'description' => 'RECLASS Payroll Harian (1202 WIP → 6103 Beban Operasional) '
                        .$oldAccrual->period_start.' s/d '.$oldAccrual->period_end,
                    'source_type' => 'daily_payroll_operating_expense_reclass',
                    'source_id' => $oldAccrual->payroll_period_id,
                    'posted_at' => $now,
                    'created_at' => $now,
                    'updated_at' => $now,
                ]);

                DB::table('journal_lines')->insert([
                    [
                        'journal_id' => $reclassJournalId,
                        'account_id' => $expenseAccountId,
                        'debit' => $amount,
                        'credit' => 0,
                    ],
                    [
                        'journal_id' => $reclassJournalId,
                        'account_id' => $wipAccountId,
                        'debit' => 0,
                        'credit' => $amount,
                    ],
                ]);
            }
        });
    }

    public function down(): void
    {
        // Jurnal koreksi adalah jejak audit dan tidak dihapus saat rollback.
    }
};
