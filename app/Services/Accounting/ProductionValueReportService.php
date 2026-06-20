<?php

namespace App\Services\Accounting;

use App\Models\Journal;
use App\Models\SewingPickup;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;

class ProductionValueReportService
{
    private const MAIN_ACCOUNT_CODES = ['1201', '1202', '1203', '1204', '5101', '6101'];
    private const EXCLUDED_JOURNAL_SOURCES = ['opening_balance_void', 'opening_balance_batch_void'];

    public function mainAccountBalances(string $asOf): Collection
    {
        return DB::table('accounts as a')
            ->leftJoin('journal_lines as jl', 'jl.account_id', '=', 'a.id')
            ->leftJoin('journals as j', function ($join) use ($asOf) {
                $join->on('j.id', '=', 'jl.journal_id')
                    ->whereNull('j.voided_at')
                    ->whereNotIn('j.source_type', self::EXCLUDED_JOURNAL_SOURCES)
                    ->whereDate('j.date', '<=', $asOf);
            })
            ->whereIn('a.code', self::MAIN_ACCOUNT_CODES)
            ->groupBy('a.id', 'a.code', 'a.name', 'a.type')
            ->orderBy('a.code')
            ->selectRaw('a.code, a.name, a.type,
                COALESCE(SUM(CASE WHEN j.id IS NULL THEN 0 ELSE jl.debit END),0) as debit,
                COALESCE(SUM(CASE WHEN j.id IS NULL THEN 0 ELSE jl.credit END),0) as credit')
            ->get()
            ->map(function ($row) {
                $balance = round((float) $row->debit - (float) $row->credit, 2);

                return (object) [
                    'code' => $row->code,
                    'name' => $row->name,
                    'type' => $row->type,
                    'debit' => (float) $row->debit,
                    'credit' => (float) $row->credit,
                    'balance' => $balance,
                    'normal_balance' => $balance >= 0 ? 'debit' : 'credit',
                    'is_unusual' => $this->isUnusualBalance((string) $row->code, $balance),
                ];
            });
    }

    public function productionCards(string $from, string $to): Collection
    {
        $cards = collect([
            [
                'key' => 'cutting_job',
                'label' => 'Bahan Baku Masuk Cutting',
                'source_type' => JournalService::SRC_CUTTING_JOB,
                'journal_source_type' => JournalService::SRC_CUTTING_JOB,
                'amount' => $this->mutationAmount(JournalService::SRC_CUTTING_JOB, $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_CUTTING_JOB, $from, $to),
                'hint' => 'RM keluar menjadi WIP cutting.',
            ],
            [
                'key' => 'cutting_wip',
                'label' => 'Nilai WIP Cutting',
                'source_type' => JournalService::SRC_CUTTING_WIP,
                'journal_source_type' => JournalService::SRC_CUTTING_WIP,
                'amount' => $this->mutationAmount(JournalService::SRC_CUTTING_WIP, $from, $to, 'in')
                    + $this->mutationAmount('cutting_reject', $from, $to, 'in'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_CUTTING_WIP, $from, $to),
                'hint' => 'Hasil QC cutting masuk WIP-CUT.',
            ],
            [
                'key' => 'sewing_pickup',
                'label' => 'Nilai Ambil Jahit',
                'source_type' => JournalService::SRC_SEWING_PICKUP,
                'journal_source_type' => JournalService::SRC_SEWING_PICKUP,
                'amount' => $this->mutationAmount(JournalService::SRC_SEWING_PICKUP, $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_SEWING_PICKUP, $from, $to),
                'hint' => 'WIP-CUT berpindah ke WIP-SEW.',
            ],
            [
                'key' => 'sewing_pickup_supply',
                'label' => 'Nilai Kelengkapan Jahit',
                'source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY,
                'journal_source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY,
                'amount' => $this->mutationAmount(JournalService::SRC_SEWING_PICKUP_SUPPLY, $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_SEWING_PICKUP_SUPPLY, $from, $to),
                'hint' => 'Rib, label, karet, dan kelengkapan jahit keluar dari RM menjadi WIP.',
            ],
            [
                'key' => 'sewing_pickup_supply_followup',
                'label' => 'Kelengkapan Jahit Menyusul',
                'source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
                'journal_source_type' => JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP,
                'amount' => $this->mutationAmount(JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP, $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_SEWING_PICKUP_SUPPLY_FOLLOWUP, $from, $to),
                'hint' => 'Material fisik yang dikoreksi melalui adjustment lalu diserahkan ke pickup.',
            ],
            [
                'key' => 'sewing_return_ok',
                'label' => 'Nilai Setoran Jahit',
                'source_type' => JournalService::SRC_SEWING_RETURN_OK,
                'journal_source_type' => JournalService::SRC_SEWING_RETURN_OK,
                'amount' => $this->mutationAmount(JournalService::SRC_SEWING_RETURN_OK, $from, $to, 'in'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_SEWING_RETURN_OK, $from, $to),
                'hint' => 'WIP-SEW berpindah ke WIP-FIN. Nilai kartu memakai cost keluar WIP-SEW.',
            ],
            [
                'key' => 'sewing_return_reject',
                'label' => 'Nilai Reject Jahit',
                'source_type' => JournalService::SRC_SEWING_RETURN_REJECT,
                'journal_source_type' => JournalService::SRC_SEWING_RETURN_REJECT,
                'amount' => $this->mutationAmount(JournalService::SRC_SEWING_RETURN_REJECT, $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_SEWING_RETURN_REJECT, $from, $to),
                'hint' => 'Reject jahit berpindah dari WIP ke persediaan barang cacat.',
            ],
            [
                'key' => 'sewing_rework_ok',
                'label' => 'Nilai Rework Jahit',
                'source_type' => JournalService::SRC_SEWING_REWORK_OK,
                'journal_source_type' => JournalService::SRC_SEWING_REWORK_OK,
                'amount' => $this->mutationAmount(JournalService::SRC_SEWING_REWORK_OK, $from, $to, 'in'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_SEWING_REWORK_OK, $from, $to),
                'hint' => 'Barang cacat yang selesai rework kembali menjadi WIP.',
            ],
            [
                'key' => 'finishing_job',
                'label' => 'Nilai Finishing Jadi',
                'source_type' => \App\Models\FinishingJob::class,
                'journal_source_type' => JournalService::SRC_FINISHING_JOB,
                'amount' => $this->mutationAmount(\App\Models\FinishingJob::class, $from, $to, 'out') + $this->finishingBomAmountByJobDate($from, $to),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_FINISHING_JOB, $from, $to),
                'hint' => 'WIP-FIN menjadi barang jadi atau barang cacat.',
            ],
            [
                'key' => 'finishing_bom',
                'label' => 'Nilai BOM Finishing',
                'source_type' => JournalService::SRC_FINISHING_BOM,
                'journal_source_type' => JournalService::SRC_FINISHING_BOM,
                'amount' => $this->mutationAmount(JournalService::SRC_FINISHING_BOM, $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount(JournalService::SRC_FINISHING_BOM, $from, $to),
                'hint' => 'Supplies finishing keluar dari RM.',
            ],
            [
                'key' => 'shipment_hpp',
                'label' => 'Nilai HPP Shipment',
                'source_type' => 'shipment',
                'journal_source_type' => 'shipment_cogs',
                'amount' => $this->mutationAmount('shipment', $from, $to, 'out'),
                'journal_amount' => $this->journalOneSideAmount('shipment_cogs', $from, $to),
                'hint' => 'Barang jadi keluar untuk penjualan.',
            ],
        ]);

        return $cards->map(fn($card) => (object) array_merge($card, [
            'diff' => round((float) $card['amount'] - (float) $card['journal_amount'], 2),
        ]));
    }

    public function stockJournalAudit(string $from, string $to): Collection
    {
        $stockSources = collect([
            (object) [
                'key' => 'grn_inv',
                'label' => 'GRN Inventory',
                'source_type' => JournalService::SRC_PURCHASE_RECEIPT,
                'journal_source_type' => JournalService::SRC_GRN_ACCRUAL_INV,
                'amount' => $this->mutationAmount(JournalService::SRC_PURCHASE_RECEIPT, $from, $to, 'in'),
            ],
            (object) [
                'key' => 'purchase_return_inv',
                'label' => 'Retur Pembelian Inventory',
                'source_type' => 'purchase_return',
                'journal_source_type' => JournalService::SRC_PURCHASE_RETURN_INV,
                'amount' => $this->mutationAmount('purchase_return', $from, $to, 'out'),
            ],
        ])->merge($this->productionCards($from, $to));

        return $stockSources->map(function ($card) use ($from, $to) {
            $mutationIn = $this->mutationAmount($card->source_type, $from, $to, 'in');
            $mutationOut = $this->mutationAmount($card->source_type, $from, $to, 'out');
            if ($card->key === 'finishing_job') {
                $mutationOut = round($mutationOut + $this->finishingBomAmountByJobDate($from, $to), 2);
            }
            $journalDebit = $this->journalSideAmount($card->journal_source_type, $from, $to, 'debit');
            $journalCredit = $this->journalSideAmount($card->journal_source_type, $from, $to, 'credit');

            $basisMutation = (float) $card->amount;
            $basisJournal = min($journalDebit, $journalCredit);

            return (object) [
                'key' => $card->key,
                'label' => $card->label,
                'source_type' => $card->source_type,
                'journal_source_type' => $card->journal_source_type,
                'mutation_in' => $mutationIn,
                'mutation_out' => $mutationOut,
                'journal_debit' => $journalDebit,
                'journal_credit' => $journalCredit,
                'basis_mutation' => $basisMutation,
                'basis_journal' => $basisJournal,
                'diff' => round($basisMutation - $basisJournal, 2),
            ];
        });
    }

    public function profitLossSnapshot(string $from, string $to): object
    {
        $balances = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->join('accounts as a', 'a.id', '=', 'jl.account_id')
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED_JOURNAL_SOURCES)
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->groupBy('a.code', 'a.name')
            ->selectRaw('a.code, a.name, SUM(jl.debit) debit, SUM(jl.credit) credit')
            ->get();

        $revenue = (float) $balances
            ->filter(fn($row) => str_starts_with((string) $row->code, '4'))
            ->sum(fn($row) => (float) $row->credit - (float) $row->debit);

        $cogs = (float) $balances
            ->filter(fn($row) => str_starts_with((string) $row->code, '5'))
            ->sum(fn($row) => (float) $row->debit - (float) $row->credit);

        $expenses = (float) $balances
            ->filter(fn($row) => str_starts_with((string) $row->code, '6'))
            ->sum(fn($row) => (float) $row->debit - (float) $row->credit);

        return (object) [
            'revenue' => round($revenue, 2),
            'cogs' => round($cogs, 2),
            'expenses' => round($expenses, 2),
            'gross_profit' => round($revenue - $cogs, 2),
            'net_profit' => round($revenue - $cogs - $expenses, 2),
        ];
    }

    protected function mutationAmount(string $sourceType, string $from, string $to, string $direction): float
    {
        $query = DB::table('inventory_mutations')
            ->where('source_type', $sourceType)
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to);

        if ($direction === 'in') {
            $query->where('qty_change', '>', 0);
        } elseif ($direction === 'out') {
            $query->where('qty_change', '<', 0);
        }

        return round((float) $query->sum(DB::raw('ABS(total_cost)')), 2);
    }

    protected function journalOneSideAmount(string $sourceType, string $from, string $to): float
    {
        $debit = $this->journalSideAmount($sourceType, $from, $to, 'debit');
        $credit = $this->journalSideAmount($sourceType, $from, $to, 'credit');

        return round(min($debit, $credit), 2);
    }

    protected function journalSideAmount(string $sourceType, string $from, string $to, string $side): float
    {
        return round((float) DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->where('j.source_type', $sourceType)
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED_JOURNAL_SOURCES)
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->sum("jl.{$side}"), 2);
    }

    protected function finishingBomAmountByJobDate(string $from, string $to): float
    {
        return round((float) DB::table('inventory_mutations as im')
            ->join('finishing_job_lines as fjl', 'fjl.id', '=', 'im.source_id')
            ->join('finishing_jobs as fj', 'fj.id', '=', 'fjl.finishing_job_id')
            ->where('im.source_type', JournalService::SRC_FINISHING_BOM)
            ->where('im.qty_change', '<', 0)
            ->whereDate('fj.date', '>=', $from)
            ->whereDate('fj.date', '<=', $to)
            ->sum(DB::raw('ABS(im.total_cost)')), 2);
    }

    protected function isUnusualBalance(string $code, float $balance): bool
    {
        if (abs($balance) < 0.01) {
            return false;
        }

        if (str_starts_with($code, '1') || str_starts_with($code, '5') || str_starts_with($code, '6')) {
            return $balance < 0;
        }

        return $balance > 0;
    }
}
