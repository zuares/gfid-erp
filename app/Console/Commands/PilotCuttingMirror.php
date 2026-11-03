<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Console\Command;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PilotCuttingMirror extends Command
{
    private const CONFIRMATION = 'PILOT-CUTTING';

    /** @var array<int, string> */
    private const REQUIRED_MAPPINGS = ['1201', '1202', '1204', '2102'];

    protected $signature = 'production:pilot-cutting
        {jobId? : ID Cutting Job yang dipilih sebagai pilot}
        {--apply : Jalankan mirror untuk satu Cutting Job yang dipilih}
        {--confirm= : Konfirmasi wajib PILOT-CUTTING saat --apply}
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Readiness check dan mirror terkontrol untuk satu Cutting Job pilot.';

    public function handle(StandaloneProductionPostingService $posting): int
    {
        $apply = (bool) $this->option('apply');

        if ($apply && ! config('accounting.standalone_dual_posting_production_cutting_enabled', false)) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Feature flag mirror Cutting masih OFF. Aktifkan hanya pada environment pilot setelah approval.',
            ]);
        }

        if ($apply && $this->option('confirm') !== self::CONFIRMATION) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Mirror pilot membutuhkan --confirm='.self::CONFIRMATION.'. Tidak ada data yang diubah.',
            ]);
        }

        $jobId = $this->argument('jobId');
        if ($jobId === null) {
            $report = $this->candidateReport();

            if ($this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $this->renderCandidates($report);
            }

            return self::SUCCESS;
        }

        $job = DB::table('cutting_jobs')->where('id', (int) $jobId)->first();
        if (! $job) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'job_id' => (int) $jobId,
                'reason' => "Cutting Job #{$jobId} tidak ditemukan.",
            ]);
        }

        $report = $this->jobReport((int) $jobId, $job);

        if ($apply) {
            $report['mode'] = 'APPLY';
        }

        if (! $apply) {
            if ($this->option('json')) {
                $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
            } else {
                $this->renderJob($report);
            }

            return self::SUCCESS;
        }

        if ($report['status'] !== 'READY') {
            $report['status'] = 'BLOCKED';
            $report['reason'] = 'Precondition pilot belum lulus; mirror tidak dijalankan.';

            return $this->failWithReport($report);
        }

        try {
            $mirrors = $posting->mirrorCuttingJob((int) $jobId);
            $wipMirror = $posting->mirrorCuttingWip((int) $jobId);
            if ($wipMirror) {
                $mirrors[] = $wipMirror;
            }

            $report['status'] = 'APPLIED';
            $report['message'] = 'Mirror pilot berhasil. Akuntansi legacy tidak diubah.';
            $report['mirrors'] = collect($mirrors)->map(fn (StandaloneAccountingJournal $journal): array => [
                'id' => (int) $journal->id,
                'source_type' => $journal->source_type,
                'source_id' => (int) $journal->source_id,
                'reference' => $journal->reference,
                'debit' => round((float) $journal->lines->sum('debit'), 2),
                'credit' => round((float) $journal->lines->sum('credit'), 2),
                'voided_at' => $journal->voided_at?->toISOString(),
            ])->values()->all();
            $report['mirror_count'] = count($report['mirrors']);
        } catch (ValidationException $exception) {
            $report['status'] = 'BLOCKED';
            $report['reason'] = $exception->getMessage();

            return $this->failWithReport($report);
        }

        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->renderJob($report);
            $this->info('Mirror pilot berhasil. Akuntansi legacy tidak diubah.');
        }

        return self::SUCCESS;
    }

    /** @return array<string, mixed> */
    private function candidateReport(): array
    {
        $candidates = DB::table('cutting_jobs as jobs')
            ->leftJoin('journals as journals', function ($join): void {
                $join->on('journals.source_id', '=', 'jobs.id')
                    ->where('journals.source_type', '=', 'cutting_job')
                    ->whereNull('journals.voided_at');
            })
            ->whereIn('jobs.status', ['cut', 'posted'])
            ->select([
                'jobs.id',
                'jobs.code',
                'jobs.date',
                'jobs.status',
                DB::raw('COUNT(journals.id) as active_legacy_journals'),
            ])
            ->groupBy('jobs.id', 'jobs.code', 'jobs.date', 'jobs.status')
            ->orderByDesc('jobs.id')
            ->limit(10)
            ->get()
            ->map(fn ($job): array => [
                'id' => (int) $job->id,
                'code' => $job->code,
                'date' => (string) $job->date,
                'status' => $job->status,
                'active_legacy_journals' => (int) $job->active_legacy_journals,
            ])->all();

        return [
            'status' => count($candidates) > 0 ? 'CANDIDATES_AVAILABLE' : 'NO_CANDIDATE',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_cutting_enabled', false),
            'candidates' => $candidates,
            'message' => count($candidates) > 0
                ? 'Pilih satu jobId lalu jalankan readiness check sebelum --apply.'
                : 'Tidak ada Cutting Job berstatus cut/posted untuk pilot; tidak ada backfill dilakukan.',
        ];
    }

    /** @return array<string, mixed> */
    private function jobReport(int $jobId, object $job): array
    {
        $mapping = collect(self::REQUIRED_MAPPINGS)->mapWithKeys(function (string $legacyCode): array {
            $row = StandaloneAccountingAccountMapping::query()
                ->where('legacy_account_code', $legacyCode)
                ->where('is_active', true)
                ->first();
            $account = $row
                ? StandaloneAccountingAccount::query()
                    ->where('code', $row->standalone_account_code)
                    ->where('is_active', true)
                    ->first()
                : null;

            return [$legacyCode => [
                'mapped_to' => $row?->standalone_account_code,
                'active' => (bool) $account,
                'purpose' => $row?->purpose,
            ]];
        })->all();

        $legacy = $this->journalSummary(Journal::query(), $jobId, [
            'cutting_job',
            'cutting_job_wage',
            'cutting_wip',
        ]);
        $standalone = $this->journalSummary(StandaloneAccountingJournal::query(), $jobId, [
            'cutting_job',
            'cutting_job_wage',
            'cutting_wip',
        ]);

        $mappingReady = collect($mapping)->every(fn (array $row): bool => $row['active']);
        $legacyReady = $legacy['active_count'] > 0 && $legacy['unbalanced_count'] === 0;
        $status = $mappingReady && $legacyReady ? 'READY' : 'REVIEW';

        return [
            'status' => $status,
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_cutting_enabled', false),
            'job' => [
                'id' => $jobId,
                'code' => $job->code,
                'date' => (string) $job->date,
                'status' => $job->status,
            ],
            'mapping' => $mapping,
            'legacy' => $legacy,
            'standalone' => $standalone,
            'message' => $status === 'READY'
                ? 'Precondition lulus. --apply tetap membutuhkan feature flag dan konfirmasi eksplisit.'
                : 'Perbaiki mapping atau jurnal legacy yang tidak balance sebelum pilot.',
        ];
    }

    /**
     * @param  array<int, string>  $sourceTypes
     * @return array<string, mixed>
     */
    private function journalSummary(Builder $query, int $jobId, array $sourceTypes): array
    {
        $journals = $query
            ->where('source_id', $jobId)
            ->where(function (Builder $query) use ($sourceTypes): void {
                $query->whereIn('source_type', $sourceTypes)
                    ->orWhere(function (Builder $query) use ($sourceTypes): void {
                        foreach ($sourceTypes as $index => $sourceType) {
                            $method = $index === 0 ? 'where' : 'orWhere';
                            $query->{$method}('source_type', 'like', $sourceType.'_repost_%');
                        }
                    });
            })
            ->with('lines')
            ->orderBy('id')
            ->get();

        $active = $journals->whereNull('voided_at');

        return [
            'total_count' => $journals->count(),
            'active_count' => $active->count(),
            'voided_count' => $journals->whereNotNull('voided_at')->count(),
            'unbalanced_count' => $active->filter(function ($journal): bool {
                return abs((float) $journal->lines->sum('debit') - (float) $journal->lines->sum('credit')) > 0.01;
            })->count(),
            'journals' => $journals->map(fn ($journal): array => [
                'id' => (int) $journal->id,
                'source_type' => $journal->source_type,
                'source_id' => (int) $journal->source_id,
                'reference' => $journal->reference ?? null,
                'debit' => round((float) $journal->lines->sum('debit'), 2),
                'credit' => round((float) $journal->lines->sum('credit'), 2),
                'status' => $journal->voided_at ? 'VOIDED' : 'POSTED',
            ])->values()->all(),
        ];
    }

    /** @param array<string, mixed> $report */
    private function failWithReport(array $report): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->error((string) ($report['reason'] ?? 'Pilot diblokir.'));
        }

        return self::FAILURE;
    }

    /** @param array<string, mixed> $report */
    private function renderJob(array $report): void
    {
        $job = $report['job'] ?? null;
        if ($job) {
            $this->info("Cutting pilot #{$job['id']} ({$job['code']}) — {$report['status']}");
            $this->line('Feature flag: '.($report['feature_flag'] ? 'ON' : 'OFF'));
            $this->line('Legacy aktif: '.($report['legacy']['active_count'] ?? 0).' | Standalone aktif: '.($report['standalone']['active_count'] ?? 0));
            $this->line('Mapping aktif: '.collect($report['mapping'] ?? [])->filter(fn (array $row): bool => $row['active'])->count().'/'.count($report['mapping'] ?? []));
            $this->line((string) ($report['message'] ?? ''));
        }
    }

    /** @param array<string, mixed> $report */
    private function renderCandidates(array $report): void
    {
        $this->info('Cutting pilot readiness — READ ONLY');
        $this->line($report['message']);
        if ($report['candidates']) {
            $this->table(['ID', 'Code', 'Tanggal', 'Status', 'Legacy aktif'], collect($report['candidates'])->map(fn (array $row): array => [
                $row['id'],
                $row['code'],
                $row['date'],
                $row['status'],
                $row['active_legacy_journals'],
            ])->all());
        }
    }
}
