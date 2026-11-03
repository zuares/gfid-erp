<?php

namespace App\Services\Accounting;

use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Models\StandaloneOpeningMigration;
use App\Models\StandaloneOpeningMigrationCorrection;
use App\Models\SystemSetting;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StandaloneOpeningMigrationService
{
    /** Build from posted ledger lines only; never read or mutate operational balances. */
    public function preview(string $asOf, ?array $mapping = null, bool $lock = false): array
    {
        $from = SystemSetting::cutoffDateString();
        if (($from && $asOf < $from) || $asOf > now()->toDateString()) {
            throw ValidationException::withMessages(['as_of' => 'Tanggal saldo akhir harus berada antara cutoff sumber dan hari ini.']);
        }

        $query = DB::table('journal_lines as l')
            ->join('journals as j', 'j.id', '=', 'l.journal_id')
            ->join('accounts as a', 'a.id', '=', 'l.account_id')
            ->whereNotNull('j.posted_at')->whereNull('j.voided_at')
            ->where(fn ($q) => $q->whereNull('j.source_type')->orWhereNotIn('j.source_type', ['opening_balance_void', 'opening_balance_batch_void']))
            ->when($from, fn ($q) => $q->whereDate('j.date', '>=', $from))
            ->whereDate('j.date', '<=', $asOf)
            ->orderBy('l.id')
            ->select('l.id', 'l.journal_id', 'l.account_id', 'l.debit', 'l.credit', 'j.date', 'j.source_type', 'a.code', 'a.name', 'a.type', 'a.is_active');
        if ($lock) {
            $query->lockForUpdate();
        }
        $entries = $query->get();
        $accounts = StandaloneAccountingAccount::where('is_active', true)->orderBy('code')->get()->keyBy('id');
        $defaults = StandaloneAccountingAccountMapping::where('is_active', true)->pluck('standalone_account_code', 'legacy_account_code');
        $accountIds = $accounts->pluck('id', 'code');
        $rows = [];
        $mapped = [];
        $issues = [];
        $warnings = [];
        $resolvedMapping = [];
        $debit = $credit = $mappedDebit = $mappedCredit = 0;

        foreach ($entries->groupBy('account_id') as $legacyId => $lines) {
            $account = $lines->first();
            $net = $lines->sum(fn ($line) => $this->cents($line->debit) - $this->cents($line->credit));
            if ($net === 0) {
                continue;
            }
            $targetId = $mapping === null
                ? (int) ($accountIds[$defaults[$account->code] ?? ''] ?? 0)
                : (int) ($mapping[$legacyId] ?? 0);
            $target = $accounts->get($targetId);
            $resolvedMapping[$legacyId] = $targetId;
            $row = [
                'legacy_id' => $legacyId, 'code' => $account->code, 'name' => $account->name,
                'type' => $account->type, 'net' => $net, 'debit' => max(0, $net), 'credit' => max(0, -$net),
                'target_id' => $targetId, 'target_code' => $target?->code, 'target_name' => $target?->name,
            ];
            $rows[] = $row;
            $debit += $row['debit'];
            $credit += $row['credit'];
            if (! $account->is_active) {
                $warnings[] = "Akun lama {$account->code} nonaktif tetapi masih bersaldo; tetap disertakan.";
            }
            if ($account->type === 'asset' && $net < 0) {
                $warnings[] = "Akun aset {$account->code} bersaldo kredit. Periksa apakah kontra-aset atau selisih yang perlu dikoreksi.";
            }
            if (! $target) {
                $issues[] = "Pilih akun baru aktif untuk {$account->code} — {$account->name}.";

                continue;
            }
            if ($target->type !== $account->type) {
                $issues[] = "Mapping {$account->code} harus ke jenis {$account->type}. Reklasifikasi lintas jenis dicatat lewat koreksi terpisah.";

                continue;
            }
            $mappedDebit += $row['debit'];
            $mappedCredit += $row['credit'];
            $mapped[$targetId] ??= ['account_id' => $targetId, 'code' => $target->code, 'name' => $target->name, 'debit' => 0, 'credit' => 0];
            $mapped[$targetId]['debit'] += $row['debit'];
            $mapped[$targetId]['credit'] += $row['credit'];
        }
        usort($rows, fn ($a, $b) => strcmp($a['code'], $b['code']));
        ksort($mapped);
        ksort($resolvedMapping);

        // Existing pre-cutover postings (including a net-zero batch) cannot be added twice.
        $existingQuery = StandaloneAccountingJournal::whereNotNull('posted_at')->whereNull('voided_at')
            ->where(fn ($q) => $q->whereDate('date', '<=', $asOf)
                ->orWhere('source_type', 'like', 'opening_balance%')
                ->orWhere('source_type', 'opening_migration')
                ->orWhere('reference', 'OB-UMKM-DUMMY-001'))
            ->orderBy('id');
        if ($lock) {
            $existingQuery->lockForUpdate();
        }
        $existing = $existingQuery->with('lines.account')->get();
        $comparison = $mapped;
        foreach ($comparison as &$line) {
            $line['existing_net'] = 0;
        }
        unset($line);
        foreach ($existing as $journal) {
            foreach ($journal->lines as $line) {
                $id = (int) $line->account_id;
                $comparison[$id] ??= ['account_id' => $id, 'code' => $line->account?->code, 'name' => $line->account?->name, 'debit' => 0, 'credit' => 0, 'existing_net' => 0];
                $comparison[$id]['existing_net'] += $this->cents($line->debit) - $this->cents($line->credit);
            }
        }
        ksort($comparison);
        if ($existing->isNotEmpty()) {
            $issues[] = 'Ada jurnal aktif Akuntansi Baru sampai tanggal saldo akhir atau opening lain. Rekonsiliasi jurnal tersebut dahulu; migrasi tidak menambah atau menimpa saldo yang sudah ada.';
        }
        if ($rows === []) {
            $issues[] = 'Tidak ada saldo sumber untuk dimigrasikan.';
        }
        if ($debit !== $credit) {
            $issues[] = 'Saldo sumber tidak seimbang. Selisih tidak akan otomatis dimasukkan ke Modal Pemilik.';
        }
        if (count($mapped) < 2 || $mappedDebit === 0) {
            $issues[] = 'Minimal dua akun tujuan dengan saldo bernilai diperlukan.';
        }

        return [
            'as_of' => $asOf, 'source_from' => $from,
            'mirror_from' => Carbon::parse($asOf)->addDay()->toDateString(),
            'source_hash' => hash('sha256', $entries->toJson()),
            'source_journal_ids' => $entries->pluck('journal_id')->unique()->values()->all(),
            'rows' => $rows, 'mapping' => $resolvedMapping, 'lines' => array_values($mapped),
            'comparison' => array_values($comparison), 'existing_ids' => $existing->pluck('id')->all(),
            'debit' => $debit, 'credit' => $credit, 'mapped_debit' => $mappedDebit, 'mapped_credit' => $mappedCredit,
            'issues' => $issues, 'warnings' => $warnings,
        ];
    }

    public function fingerprint(array $snapshot): string
    {
        return hash('sha256', json_encode($snapshot, JSON_THROW_ON_ERROR));
    }

    public function post(array $preview, string $token, int $userId): StandaloneOpeningMigration
    {
        return DB::transaction(function () use ($preview, $token, $userId) {
            $migration = StandaloneOpeningMigration::lockForUpdate()->findOrFail(1);
            if ($migration->posted_at) {
                if ($migration->request_token === $token) {
                    return $migration;
                }
                throw ValidationException::withMessages(['migration' => 'Opening Migrasi sudah diposting. Gunakan koreksi terpisah; opening tidak boleh diposting ulang.']);
            }
            $current = $this->preview($preview['as_of'], $preview['mapping'], true);
            if ($this->fingerprint($current) !== $this->fingerprint($preview)) {
                throw ValidationException::withMessages(['migration' => 'Saldo, cutoff, atau akun berubah setelah preview. Buat preview baru sebelum posting.']);
            }
            if ($current['issues']) {
                throw ValidationException::withMessages(['migration' => $current['issues']]);
            }

            $journal = StandaloneAccountingJournal::create([
                'date' => $current['as_of'], 'reference' => 'SA-OPENING-MIGRATION-001',
                'source_type' => 'opening_migration', 'source_id' => 1,
                'description' => 'Opening Migrasi per '.$current['as_of'].'; sumber sejak '.($current['source_from'] ?? 'awal riwayat'),
                'posted_at' => now(),
            ]);
            $journal->lines()->createMany(array_map(fn ($line) => [
                'account_id' => $line['account_id'], 'debit' => $this->amount($line['debit']), 'credit' => $this->amount($line['credit']),
            ], $current['lines']));
            $migration->fill([
                'as_of' => $current['as_of'], 'source_from' => $current['source_from'],
                'request_token' => $token, 'snapshot' => $current, 'journal_id' => $journal->id,
                'posted_by' => $userId, 'posted_at' => now(),
            ])->save();

            return $migration;
        });
    }

    public function correct(array $data, int $userId): StandaloneAccountingJournal
    {
        return DB::transaction(function () use ($data, $userId) {
            $migration = StandaloneOpeningMigration::lockForUpdate()->findOrFail(1);
            if (! $migration->posted_at || $migration->journal?->voided_at) {
                throw ValidationException::withMessages(['correction' => 'Opening Migrasi aktif diperlukan sebelum membuat koreksi.']);
            }
            if ($data['date'] < $migration->as_of->toDateString() || $data['date'] > now()->toDateString()) {
                throw ValidationException::withMessages(['date' => 'Tanggal koreksi harus antara tanggal opening dan hari ini.']);
            }
            $reason = trim($data['reason']);
            if ($reason === '') {
                throw ValidationException::withMessages(['reason' => 'Penjelasan dan dasar koreksi wajib diisi.']);
            }
            $lines = [];
            $debit = $credit = 0;
            foreach ($data['lines'] as $line) {
                $d = $this->cents($line['debit'] ?? 0);
                $c = $this->cents($line['credit'] ?? 0);
                if ($d === 0 && $c === 0) {
                    continue;
                }
                if ($d < 0 || $c < 0 || ($d && $c) || ! StandaloneAccountingAccount::whereKey($line['account_id'] ?? 0)->where('is_active', true)->exists()) {
                    throw ValidationException::withMessages(['lines' => 'Pilih akun aktif dan isi debit atau kredit pada tiap baris koreksi.']);
                }
                $lines[] = ['account_id' => (int) $line['account_id'], 'debit' => $this->amount($d), 'credit' => $this->amount($c)];
                $debit += $d;
                $credit += $c;
            }
            if (count($lines) < 2 || $debit === 0 || $debit !== $credit) {
                throw ValidationException::withMessages(['lines' => 'Koreksi harus memiliki minimal dua baris dan total debit sama dengan kredit.']);
            }
            $existing = StandaloneOpeningMigrationCorrection::where('request_token', $data['request_token'])->first();
            if ($existing) {
                return $existing->journal;
            }
            $journal = StandaloneAccountingJournal::create([
                'date' => $data['date'], 'reference' => 'SA-MIG-CORR-'.$data['request_token'],
                'source_type' => 'opening_migration_correction',
                'description' => 'Koreksi Opening Migrasi #1: '.mb_substr($reason, 0, 220),
                'posted_at' => now(),
            ]);
            $journal->lines()->createMany($lines);
            $correction = StandaloneOpeningMigrationCorrection::create([
                'opening_migration_id' => 1, 'request_token' => $data['request_token'],
                'journal_id' => $journal->id, 'posted_by' => $userId, 'reason' => $reason,
            ]);
            $journal->update(['source_id' => $correction->id]);

            return $journal;
        });
    }

    private function cents(mixed $amount): int
    {
        return (int) round((float) $amount * 100);
    }

    private function amount(int $cents): string
    {
        return number_format($cents / 100, 2, '.', '');
    }
}
