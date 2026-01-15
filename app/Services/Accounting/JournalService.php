<?php

namespace App\Services\Accounting;

use App\Models\Journal;
use App\Models\JournalLine;
use Illuminate\Support\Facades\DB;

class JournalService
{
    /**
     * Post journal + lines (balanced)
     *
     * $lines format:
     * [
     *   ['account_id' => 1, 'debit' => 10000, 'credit' => 0],
     *   ['account_id' => 2, 'debit' => 0, 'credit' => 10000],
     * ]
     */
    public function post(
        string $date,
        string $sourceType,
        ?int $sourceId,
        string $description,
        array $lines
    ): Journal {
        return DB::transaction(function () use ($date, $sourceType, $sourceId, $description, $lines) {
            if (count($lines) < 2) {
                throw new \RuntimeException('Journal lines minimal 2 baris.');
            }

            $totalDebit = 0.0;
            $totalCredit = 0.0;

            foreach ($lines as $i => $line) {
                if (!isset($line['account_id'])) {
                    throw new \RuntimeException("Line #{$i}: account_id wajib.");
                }
                $debit = (float) ($line['debit'] ?? 0);
                $credit = (float) ($line['credit'] ?? 0);

                if ($debit < 0 || $credit < 0) {
                    throw new \RuntimeException("Line #{$i}: debit/credit tidak boleh negatif.");
                }
                if (($debit > 0 && $credit > 0) || ($debit == 0 && $credit == 0)) {
                    throw new \RuntimeException("Line #{$i}: isi salah satu, debit atau credit.");
                }

                $totalDebit += $debit;
                $totalCredit += $credit;
            }

            // toleransi kecil untuk numeric sqlite
            if (abs($totalDebit - $totalCredit) > 0.0001) {
                throw new \RuntimeException('Journal tidak balance. Total debit harus sama dengan total credit.');
            }

            $journal = Journal::create([
                'date' => $date,
                'description' => $description,
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'posted_at' => now(),
            ]);

            foreach ($lines as $line) {
                JournalLine::create([
                    'journal_id' => $journal->id,
                    'account_id' => (int) $line['account_id'],
                    'debit' => (float) ($line['debit'] ?? 0),
                    'credit' => (float) ($line['credit'] ?? 0),
                ]);
            }

            return $journal;
        });
    }

    /**
     * Void journal (soft)
     * (tidak membuat reversal otomatis — kalau mau, nanti kita tambahkan)
     */
    public function void(Journal $journal): Journal
    {
        if ($journal->voided_at) {
            return $journal;
        }

        $journal->update([
            'voided_at' => now(),
        ]);

        return $journal;
    }
}
