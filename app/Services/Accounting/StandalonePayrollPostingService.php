<?php

namespace App\Services\Accounting;

use App\Models\Journal;
use App\Models\PieceworkPayrollPeriod;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StandalonePayrollPostingService
{
    public const ACCRUAL = 'piecework_payroll_period_accrual';

    public const PAYMENT = 'piecework_payroll_period_payment';

    public const RECLASS = 'daily_payroll_operating_expense_reclass';

    public const SOURCES = [self::ACCRUAL, self::PAYMENT, self::RECLASS];

    public function __construct(private StandaloneProductionPostingService $production) {}

    public static function productionSources(string $module): array
    {
        return match ($module) {
            'cutting' => [JournalService::SRC_CUTTING_JOB_WAGE, JournalService::SRC_CUTTING_WIP],
            'sewing' => [JournalService::SRC_SEWING_PICKUP_WAGE, JournalService::SRC_SEWING_RETURN_OK, JournalService::SRC_SEWING_REWORK_OK],
            default => [],
        };
    }

    /** Read-only, shared by the preview, UI and transactional posting. No opening entries. */
    public function plan(PieceworkPayrollPeriod $period, bool $lock = false): Collection
    {
        if (! in_array($period->module, ['cutting', 'sewing', 'daily'], true)) {
            $this->fail('Modul payroll belum didukung Akuntansi Baru.');
        }
        $own = Journal::query()->whereIn('source_type', self::SOURCES)
            ->where('source_id', $period->id)->whereNull('voided_at')
            ->with('lines.account')->orderBy('id')->when($lock, fn ($q) => $q->lockForUpdate())->get();
        if ($own->groupBy('source_type')->contains(fn ($rows) => $rows->count() > 1)) {
            $this->fail("Jurnal payroll aktif periode #{$period->id} ganda.");
        }
        if ($period->paid_at && ! $own->contains('source_type', self::PAYMENT)) {
            $this->fail("Periode #{$period->id} sudah dibayar tetapi jurnal pembayaran tidak ditemukan.");
        }
        foreach (['accrual_journal_id' => self::ACCRUAL, 'payment_journal_id' => self::PAYMENT] as $field => $source) {
            if ($period->$field && ! $own->contains(fn ($j) => $j->id == $period->$field && $j->source_type === $source)) {
                $this->fail("Penanda {$field} periode #{$period->id} tidak sesuai jurnal aktif.");
            }
        }
        $production = Journal::query()->whereIn('source_type', self::productionSources($period->module))
            ->whereNull('voided_at')
            ->whereDate('date', '>=', $period->period_start->toDateString())
            ->whereDate('date', '<=', $period->period_end->toDateString())
            ->whereHas('lines', fn ($q) => $q->where('credit', '>', 0)
                ->whereHas('account', fn ($q) => $q->where('code', JournalService::CODE_PAYROLL_PAYABLE)))
            ->with('lines.account')->orderBy('id')->when($lock, fn ($q) => $q->lockForUpdate())->get();
        if ($production->groupBy(fn ($j) => $j->source_type.':'.$j->source_id)->contains(fn ($rows) => $rows->count() > 1)) {
            $this->fail('Terdapat sumber upah produksi aktif ganda; rekonsiliasi dahulu.');
        }
        if ($period->isFinalized() && $own->isEmpty() && $production->isEmpty()) {
            $this->fail("Periode FINAL #{$period->id} tidak memiliki jurnal akrual atau sumber upah produksi.");
        }

        return $production->map(fn ($j) => $this->production->preparePayrollSource($j))
            ->concat($own->map(fn ($j) => $this->preparePayrollJournal($j, $period)))->map(function (array $entry) {
                $legacy = $entry['legacy'];
                $lines = collect($entry['lines']);
                $debit = round((float) $lines->sum('debit'), 2);
                $credit = round((float) $lines->sum('credit'), 2);
                if (! $legacy->posted_at || $debit <= 0 || abs($debit - $credit) > 0.009) {
                    $this->fail("Jurnal #{$legacy->id} belum posted atau tidak balance.");
                }
                if ($entry['existing']) {
                    $existing = $entry['existing'];
                    if ((! $entry['production'] && ((int) $existing->source_id !== (int) $legacy->source_id
                        || ! in_array($existing->source_type, [$legacy->source_type, $legacy->source_type.'_repost_'.$legacy->id], true)))
                        || $existing->voided_at || ! $existing->posted_at
                        || $existing->date->toDateString() !== $legacy->date->toDateString()
                        || $this->totals($existing->lines) !== $this->totals($lines)) {
                        $this->fail("Jurnal baru #{$existing->id} berbeda dari sumber #{$legacy->id}; rekonsiliasi dahulu.");
                    }
                } elseif (StandaloneAccountingJournal::whereNull('voided_at')->whereNotNull('posted_at')
                    ->where('source_type', 'like', 'opening%')->whereDate('date', '>=', $legacy->date->toDateString())->exists()) {
                    $this->fail("Jurnal #{$legacy->id} berada sebelum/pada saldo awal yang sudah diposting; cegah pencatatan ganda.");
                }

                return $entry;
            })->values();
    }

    public function sync(PieceworkPayrollPeriod $period, bool $requireReconciled = false): void
    {
        if (! config('accounting.standalone_dual_posting_payroll_enabled', true)) {
            return;
        }
        DB::transaction(function () use ($period, $requireReconciled) {
            $period = PieceworkPayrollPeriod::whereKey($period->id)->lockForUpdate()->firstOrFail();
            if (! $period->isFinalized()) {
                $this->fail('Hanya payroll FINAL yang dapat diposting ke Akuntansi Baru.');
            }
            $plan = $this->plan($period, true);
            $reconciliation = $this->reconciliation($period, $plan);
            if ($requireReconciled && ($reconciliation['difference'] !== 0.0 || $reconciliation['payment_difference'] !== 0.0)) {
                $this->fail('Akrual upah tidak sesuai total payroll. Rekonsiliasi sumber produksi sebelum membayar.');
            }
            foreach ($plan as $entry) {
                if ($entry['existing']) {
                    continue;
                }
                $legacy = $entry['legacy'];
                if ($entry['production']) {
                    $this->production->mirrorPayrollSource($legacy);

                    continue;
                }
                $sourceType = $legacy->source_type;
                if (StandaloneAccountingJournal::where('source_type', $sourceType)->where('source_id', $period->id)->exists()) {
                    $sourceType .= '_repost_'.$legacy->id;
                }
                $journal = StandaloneAccountingJournal::create([
                    'date' => $legacy->date, 'reference' => $this->reference($legacy),
                    'source_type' => $sourceType, 'source_id' => $period->id,
                    'description' => mb_substr('Payroll #'.$period->id.' · Jurnal lama #'.$legacy->id.' · '.$legacy->description, 0, 255),
                    'posted_at' => now(),
                ]);
                $journal->lines()->createMany($entry['lines']);
            }
            // Verify the actual output, including a production mirror reused by another period.
            $this->plan($period, true);
        });
    }

    /** Called from the legacy journal void transaction, even if dual posting is disabled. */
    public function voidLegacyJournal(Journal $legacy): void
    {
        if (! in_array($legacy->source_type, self::SOURCES, true)) {
            return;
        }
        $journal = StandaloneAccountingJournal::where('reference', $this->reference($legacy))
            ->whereNull('voided_at')->with('lines')->lockForUpdate()->first();
        if (! $journal) {
            return;
        }
        $journal->update(['voided_at' => now()]);
        $reversal = StandaloneAccountingJournal::create([
            'date' => $journal->date, 'reference' => $journal->reference.'-REV',
            'source_type' => 'payroll_reversal', 'source_id' => $journal->id,
            'description' => mb_substr('Reversal: '.$journal->description, 0, 255),
            'posted_at' => now(), 'voided_at' => now(),
        ]);
        $reversal->lines()->createMany($journal->lines->map(fn ($line) => [
            'account_id' => $line->account_id, 'debit' => $line->credit, 'credit' => $line->debit,
        ])->all());
    }

    public function status(PieceworkPayrollPeriod $period): array
    {
        $journals = StandaloneAccountingJournal::where('source_id', $period->id)
            ->where('reference', 'like', 'SA-PAYROLL-J%')->orderByDesc('id')->get();
        $result = ['label' => 'Belum diposting', 'error' => null, 'journals' => $journals,
            'production_count' => 0, 'difference' => 0, 'recovery' => 0, 'gross' => 0, 'cash' => 0, 'loans' => 0, 'savings' => 0,
            'source_alerts' => []];
        $payment = Journal::where('source_type', self::PAYMENT)->where('source_id', $period->id)
            ->whereNull('voided_at')->with('lines.account')->first();
        foreach ($payment?->lines ?? [] as $line) {
            $code = $line->account?->code;
            $result['gross'] += $code === '2102' ? (float) $line->debit : 0;
            $result['cash'] += $line->account?->is_cash ? (float) $line->credit : 0;
            $result['loans'] += $line->account?->type === 'asset' && ! $line->account?->is_cash ? (float) $line->credit : 0;
            $result['savings'] += $code === '2104' ? (float) $line->credit : 0;
        }
        if (! $period->isFinalized()) {
            $result['label'] = $journals->contains(fn ($j) => $j->voided_at !== null) ? 'Dibatalkan' : 'Belum diposting';

            return $result;
        }

        $result['source_alerts'] = $this->sourceLifecycleAlerts($period);
        try {
            $plan = $this->plan($period);
            $result['production_count'] = $plan->where('production', true)->whereNotNull('existing')->count();
            $result['label'] = $plan->isNotEmpty() && $plan->every(fn ($e) => $e['existing']) ? 'Sudah diposting' : 'Belum lengkap';
            $result['recovery'] = $this->recoveryAmount($period);
            $result['difference'] = round($this->reconciliation($period, $plan)['difference'] + $result['recovery'], 2);
            $paymentDifference = $this->reconciliation($period, $plan)['payment_difference'];
            if ($result['difference'] !== 0.0 || $paymentDifference !== 0.0) {
                $result['label'] .= ' · Selisih perlu diperiksa';
                $result['error'] = 'Akrual sumber dan koreksi tagihan dikurangi total payroll: Rp '.number_format($result['difference'], 2, ',', '.').'; selisih pembayaran: Rp '.number_format($paymentDifference, 2, ',', '.').'. Angka historis tidak diubah otomatis.';
            }
        } catch (ValidationException $e) {
            $result['label'] = 'Perlu diperiksa';
            $result['error'] = $e->getMessage();
        }

        return $result;
    }

    /**
     * Payroll yang sudah dibayar tidak boleh diam-diam berubah maknanya saat
     * sumber produksi dibuat belakangan atau di-VOID. Ini hanya indikator audit;
     * tidak membuat koreksi atau mengubah jurnal sumber.
     *
     * @return array<int, array{type:string,journal_id:int,source_type:string,source_id:int,amount:float,reason:?string}>
     */
    private function sourceLifecycleAlerts(PieceworkPayrollPeriod $period): array
    {
        if (! $period->paid_at || $period->module === 'daily') {
            return [];
        }

        $sources = Journal::query()
            ->whereIn('source_type', self::productionSources((string) $period->module))
            ->whereDate('date', '>=', $period->period_start->toDateString())
            ->whereDate('date', '<=', $period->period_end->toDateString())
            ->whereHas('lines', fn ($query) => $query
                ->where('credit', '>', 0)
                ->whereHas('account', fn ($account) => $account->where('code', JournalService::CODE_PAYROLL_PAYABLE)))
            ->with(['lines.account'])
            ->where(function ($query) use ($period): void {
                $query->where('created_at', '>', $period->paid_at)
                    ->orWhere('voided_at', '>', $period->paid_at);
            })
            ->orderBy('id')
            ->get();

        $voidReasons = DB::table('sewing_pickup_lines')
            ->whereIn('id', $sources->where('source_type', JournalService::SRC_SEWING_PICKUP_WAGE)->pluck('source_id'))
            ->pluck('void_reason', 'id');

        $alerts = [];
        foreach ($sources as $source) {
            $amount = round((float) $source->lines
                ->filter(fn ($line) => $line->account?->code === JournalService::CODE_PAYROLL_PAYABLE)
                ->sum('credit'), 2);

            if ($source->created_at?->gt($period->paid_at)) {
                $alerts[] = [
                    'type' => 'created_after_payment',
                    'journal_id' => (int) $source->id,
                    'source_type' => (string) $source->source_type,
                    'source_id' => (int) $source->source_id,
                    'amount' => $amount,
                    'reason' => null,
                ];
            }

            if ($source->voided_at?->gt($period->paid_at)) {
                $alerts[] = [
                    'type' => 'voided_after_payment',
                    'journal_id' => (int) $source->id,
                    'source_type' => (string) $source->source_type,
                    'source_id' => (int) $source->source_id,
                    'amount' => $amount,
                    'reason' => $voidReasons->get($source->source_id),
                ];
            }
        }

        return $alerts;
    }

    private function recoveryAmount(PieceworkPayrollPeriod $period): float
    {
        return round((float) Journal::query()
            ->where('source_type', JournalService::SRC_PAYROLL_OVERPAYMENT_RECOVERY)
            ->where('source_id', $period->id)
            ->whereNull('voided_at')
            ->with('lines.account')
            ->get()
            ->flatMap(fn ($journal) => $journal->lines)
            ->filter(fn ($line) => $line->account?->code === JournalService::CODE_PAYROLL_PAYABLE)
            ->sum(fn ($line) => (float) $line->credit - (float) $line->debit), 2);
    }

    public function reconciliation(PieceworkPayrollPeriod $period, Collection $plan): array
    {
        $accrued = 0.0;
        $paid = 0.0;
        foreach ($plan as $entry) {
            foreach ($entry['legacy']->lines as $line) {
                if ($line->account?->code !== JournalService::CODE_PAYROLL_PAYABLE) {
                    continue;
                }
                if ($entry['legacy']->source_type === self::PAYMENT) {
                    $paid += (float) $line->debit - (float) $line->credit;
                } else {
                    $accrued += (float) $line->credit - (float) $line->debit;
                }
            }
        }

        return ['accrued' => round($accrued, 2), 'paid' => round($paid, 2),
            'difference' => round($accrued - (float) $period->total_amount, 2),
            'payment_difference' => $period->paid_at ? round($paid - (float) $period->total_amount, 2) : 0.0];
    }

    private function preparePayrollJournal(Journal $journal, PieceworkPayrollPeriod $period): array
    {
        $existing = StandaloneAccountingJournal::where('reference', $this->reference($journal))->with('lines')->first();
        $active = StandaloneAccountingJournal::where('source_id', $period->id)
            ->where(fn ($q) => $q->where('source_type', $journal->source_type)
                ->orWhere('source_type', 'like', $journal->source_type.'_repost_%'))
            ->whereNull('voided_at')->get();
        if ($active->contains(fn ($mirror) => $mirror->id !== $existing?->id)) {
            $this->fail("Sumber payroll #{$journal->id} sudah memiliki jurnal aktif lain. Rekonsiliasi agar tidak diposting ganda.");
        }

        return ['legacy' => $journal, 'lines' => $this->mapLines($journal, $period),
            'existing' => $existing, 'production' => false];
    }

    private function mapLines(Journal $journal, PieceworkPayrollPeriod $period): array
    {
        return $journal->lines->map(function ($line) use ($period) {
            $legacy = $line->account;
            $mapping = StandaloneAccountingAccountMapping::where('legacy_account_code', $legacy?->code)
                ->where('is_active', true)->first();
            if (! $legacy || ! $mapping) {
                $this->fail('Mapping akun payroll '.($legacy?->code ?? '#'.$line->account_id).' belum tersedia/aktif.');
            }
            $code = $mapping->standalone_account_code;
            if ($legacy->code === '1202' && $period->module !== 'daily') {
                $code = config('accounting.standalone_payroll_wip_accounts.'.$period->module);
            }
            $account = StandaloneAccountingAccount::where('code', $code)->where('is_active', true)->first();
            if (! $account || $account->type !== $legacy->type) {
                $this->fail("Akun baru {$code} untuk {$legacy->code} belum aktif atau tipenya tidak sesuai.");
            }
            $debit = round((float) $line->debit, 2);
            $credit = round((float) $line->credit, 2);
            if ($debit < 0 || $credit < 0 || ($debit > 0) === ($credit > 0)) {
                $this->fail("Baris jurnal payroll #{$line->journal_id} tidak valid.");
            }

            return ['account_id' => $account->id, 'debit' => $debit, 'credit' => $credit];
        })->all();
    }

    private function totals(Collection $lines): array
    {
        return $lines->groupBy('account_id')->map(fn ($rows) => [
            (int) round((float) $rows->sum('debit') * 100), (int) round((float) $rows->sum('credit') * 100),
        ])->sortKeys()->all();
    }

    private function reference(Journal $journal): string
    {
        return 'SA-PAYROLL-J'.$journal->id;
    }

    private function fail(string $message): never
    {
        throw ValidationException::withMessages(['standalone_accounting' => $message]);
    }
}
