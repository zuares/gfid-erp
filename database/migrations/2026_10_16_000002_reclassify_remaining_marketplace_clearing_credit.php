<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    private const CLEARING_CODE = '1302';
    private const PENDING_CODE = '2301';
    private const RECLASS_SOURCE = 'marketplace_clearing_residual_reclass';

    public function up(): void
    {
        if (! Schema::hasTable('accounts') || ! Schema::hasTable('journals') || ! Schema::hasTable('journal_lines')) {
            return;
        }

        $clearingId = DB::table('accounts')->where('code', self::CLEARING_CODE)->value('id');
        $pendingId = DB::table('accounts')->where('code', self::PENDING_CODE)->value('id');
        if (! $clearingId || ! $pendingId) {
            return;
        }

        if (DB::table('journals')->where('source_type', self::RECLASS_SOURCE)->exists()) {
            return;
        }

        $balance = (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $clearingId)
            ->whereNull('j.voided_at')
            ->selectRaw('COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
            ->value('balance');
        $required = round(max(-$balance, 0), 2);
        if ($required <= 0) {
            return;
        }

        // The first migration handled legacy payout rows. This pass covers
        // older marketplace settlement/import journal sources that also
        // credited 1302 but are not linked to marketplace_payouts.
        $available = (float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('jl.account_id', $clearingId)
            ->where('jl.credit', '>', 0)
            ->whereNull('j.voided_at')
            ->selectRaw('COALESCE(SUM(jl.credit), 0) as total')
            ->value('total');
        $amount = round(min($required, $available), 2);
        if ($amount <= 0) {
            return;
        }

        $now = now();
        $journalId = DB::table('journals')->insertGetId([
            'date' => $now->toDateString(),
            'description' => 'Reclassifikasi sisa saldo clearing marketplace yang belum matched',
            'source_type' => self::RECLASS_SOURCE,
            'source_id' => null,
            'posted_at' => $now,
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('journal_lines')->insert([
            [
                'journal_id' => $journalId,
                'account_id' => $clearingId,
                'debit' => $amount,
                'credit' => 0,
                'created_at' => $now,
                'updated_at' => $now,
            ],
            [
                'journal_id' => $journalId,
                'account_id' => $pendingId,
                'debit' => 0,
                'credit' => $amount,
                'created_at' => $now,
                'updated_at' => $now,
            ],
        ]);
    }

    public function down(): void
    {
        // Preserve accounting history if the migration is rolled back.
    }
};
