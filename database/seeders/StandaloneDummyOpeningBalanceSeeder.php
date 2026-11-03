<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

/**
 * Optional demo data after migrations; never called by DatabaseSeeder.
 * Run locally: php artisan db:seed --class=StandaloneDummyOpeningBalanceSeeder
 */
class StandaloneDummyOpeningBalanceSeeder extends Seeder
{
    private const REFERENCE = 'OB-UMKM-DUMMY-001';

    public function run(): void
    {
        if (! app()->environment(['local', 'testing'])) {
            throw new \RuntimeException('Saldo awal dummy hanya boleh dibuat di environment local atau testing.');
        }

        if (DB::table('standalone_accounting_journals')->where('reference', self::REFERENCE)->exists()) {
            return;
        }

        $openingLines = [
            ['code' => '1-110', 'debit' => 25_000_000, 'credit' => 0],
            ['code' => '1-120', 'debit' => 2_500_000, 'credit' => 0],
            ['code' => '1-130', 'debit' => 8_000_000, 'credit' => 0],
            ['code' => '1-150', 'debit' => 35_000_000, 'credit' => 0],
            ['code' => '1-160', 'debit' => 1_500_000, 'credit' => 0],
            ['code' => '1-170', 'debit' => 1_000_000, 'credit' => 0],
            ['code' => '1-330', 'debit' => 25_000_000, 'credit' => 0],
            ['code' => '1-331', 'debit' => 0, 'credit' => 5_000_000],
            ['code' => '2-110', 'debit' => 0, 'credit' => 20_000_000],
            ['code' => '2-120', 'debit' => 0, 'credit' => 3_000_000],
            ['code' => '2-130', 'debit' => 0, 'credit' => 2_000_000],
            ['code' => '3-110', 'debit' => 0, 'credit' => 60_000_000],
            ['code' => '3-130', 'debit' => 0, 'credit' => 8_000_000],
        ];

        $accountIds = DB::table('standalone_accounting_accounts')
            ->whereIn('code', collect($openingLines)->pluck('code'))
            ->pluck('id', 'code');

        $missingCodes = collect($openingLines)
            ->pluck('code')
            ->reject(fn (string $code) => $accountIds->has($code));

        if ($missingCodes->isNotEmpty()) {
            throw new \RuntimeException(
                'Akun saldo awal dummy belum tersedia: '.$missingCodes->implode(', ')
            );
        }

        DB::transaction(function () use ($openingLines, $accountIds): void {
            $now = now();
            $journalId = DB::table('standalone_accounting_journals')->insertGetId([
                'date' => '2026-01-01',
                'reference' => self::REFERENCE,
                'description' => 'Saldo awal dummy skala UMKM — ganti dengan data riil sebelum digunakan sebagai laporan resmi.',
                'posted_at' => $now,
                'voided_at' => null,
                'created_at' => $now,
                'updated_at' => $now,
            ]);

            DB::table('standalone_accounting_journal_lines')->insert(
                collect($openingLines)
                    ->map(fn (array $line) => [
                        'journal_id' => $journalId,
                        'account_id' => $accountIds[$line['code']],
                        'debit' => $line['debit'],
                        'credit' => $line['credit'],
                        'created_at' => $now,
                        'updated_at' => $now,
                    ])
                    ->all(),
            );
        });
    }
}
