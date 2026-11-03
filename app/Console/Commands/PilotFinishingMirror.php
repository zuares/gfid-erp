<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PilotFinishingMirror extends Command
{
    private const CONFIRMATION = 'PILOT-FINISHING';

    private const REQUIRED_MAPPINGS = ['1201', '1202', '1203', '1204'];

    private const SOURCE_TYPES = [
        StandaloneProductionPostingService::SOURCE_FINISHING_JOB,
        StandaloneProductionPostingService::SOURCE_FINISHING_BOM,
    ];

    protected $signature = 'production:pilot-finishing
        {jobId? : ID Finishing Job yang dipilih sebagai pilot}
        {--apply : Jalankan mirror untuk satu Finishing Job yang dipilih}
        {--confirm= : Konfirmasi wajib PILOT-FINISHING saat --apply}
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Readiness check dan mirror terkontrol untuk satu Finishing Job pilot.';

    public function handle(StandaloneProductionPostingService $posting): int
    {
        $apply = (bool) $this->option('apply');

        if ($apply && ! config('accounting.standalone_dual_posting_production_finishing_enabled', false)) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Feature flag mirror Finishing masih OFF. Aktifkan hanya setelah approval pilot.',
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
            return $this->outputReport($this->candidateReport());
        }

        $job = DB::table('finishing_jobs')->where('id', (int) $jobId)->first();
        if (! $job) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'job_id' => (int) $jobId,
                'reason' => "Finishing Job #{$jobId} tidak ditemukan.",
            ]);
        }

        $report = $this->jobReport((int) $jobId, $job);
        if ($apply) {
            $report['mode'] = 'APPLY';
        }

        if (! $apply) {
            return $this->outputReport($report);
        }

        if ($report['status'] !== 'READY') {
            $report['status'] = 'BLOCKED';
            $report['reason'] = 'Precondition pilot belum lulus; mirror tidak dijalankan.';

            return $this->failWithReport($report);
        }

        try {
            $mirrors = $posting->mirrorFinishingJob((int) $jobId);
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

        return $this->outputReport($report);
    }

    /** @return array<string, mixed> */
    private function candidateReport(): array
    {
        $candidates = DB::table('finishing_jobs as jobs')
            ->where('jobs.status', 'posted')
            ->select(['jobs.id', 'jobs.code', 'jobs.date', 'jobs.status'])
            ->orderByDesc('jobs.id')
            ->limit(10)
            ->get()
            ->map(function ($job): array {
                return [
                    'id' => (int) $job->id,
                    'code' => $job->code,
                    'date' => (string) $job->date,
                    'status' => $job->status,
                    'active_legacy_journals' => Journal::query()
                        ->whereIn('source_type', self::SOURCE_TYPES)
                        ->where('source_id', $job->id)
                        ->whereNull('voided_at')
                        ->count(),
                ];
            })->all();

        return [
            'status' => count($candidates) > 0 ? 'CANDIDATES_AVAILABLE' : 'NO_CANDIDATE',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_finishing_enabled', false),
            'candidates' => $candidates,
            'message' => count($candidates) > 0
                ? 'Pilih jobId lalu jalankan readiness check sebelum --apply.'
                : 'Tidak ada Finishing Job untuk pilot; tidak ada backfill dilakukan.',
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

        $legacy = $this->journalSummary(Journal::class, $jobId);
        $standalone = $this->journalSummary(StandaloneAccountingJournal::class, $jobId);
        $mappingReady = collect($mapping)->every(fn (array $row): bool => $row['active']);
        $legacyReady = $legacy['active_count'] > 0 && $legacy['unbalanced_count'] === 0;
        $status = $mappingReady && $legacyReady ? 'READY' : 'REVIEW';

        return [
            'status' => $status,
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_finishing_enabled', false),
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
                : 'Periksa mapping dan jurnal legacy; belum ada posting baru.',
        ];
    }

    /** @return array<string, mixed> */
    private function journalSummary(string $model, int $jobId): array
    {
        $journals = $model::query()
            ->whereIn('source_type', self::SOURCE_TYPES)
            ->where('source_id', $jobId)
            ->with('lines')
            ->orderBy('id')
            ->get();

        $rows = $journals->map(function ($journal): array {
            $debit = round((float) $journal->lines->sum('debit'), 2);
            $credit = round((float) $journal->lines->sum('credit'), 2);

            return [
                'id' => (int) $journal->id,
                'source_type' => $journal->source_type,
                'source_id' => (int) $journal->source_id,
                'debit' => $debit,
                'credit' => $credit,
                'status' => $journal->voided_at ? 'VOID' : 'POSTED',
            ];
        })->all();

        return [
            'total_count' => count($rows),
            'active_count' => $journals->whereNull('voided_at')->count(),
            'voided_count' => $journals->whereNotNull('voided_at')->count(),
            'unbalanced_count' => collect($rows)->filter(fn (array $row): bool => abs($row['debit'] - $row['credit']) > 0.01)->count(),
            'journals' => $rows,
        ];
    }

    /** @param array<string, mixed> $report */
    private function outputReport(array $report): int
    {
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function failWithReport(array $report): int
    {
        $this->outputReport($report);

        return self::FAILURE;
    }
}
