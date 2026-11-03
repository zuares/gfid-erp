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

class PilotShipmentMirror extends Command
{
    private const CONFIRMATION = 'PILOT-SHIPMENT';

    private const SOURCE_TYPES = [
        StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS,
        StandaloneProductionPostingService::SOURCE_SHIPMENT_WAVE_COGS,
    ];

    protected $signature = 'production:pilot-shipment
        {shipmentId? : ID Shipment yang dipilih sebagai pilot}
        {--apply : Jalankan mirror untuk satu Shipment yang dipilih}
        {--confirm= : Konfirmasi wajib PILOT-SHIPMENT saat --apply}
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Readiness check dan mirror terkontrol HPP untuk satu Shipment pilot.';

    public function handle(StandaloneProductionPostingService $posting): int
    {
        $apply = (bool) $this->option('apply');

        if ($apply && ! config('accounting.standalone_dual_posting_production_shipment_enabled', false)) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Feature flag mirror Shipment masih OFF. Aktifkan hanya setelah approval pilot.',
            ]);
        }

        if ($apply && $this->option('confirm') !== self::CONFIRMATION) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Mirror pilot membutuhkan --confirm='.self::CONFIRMATION.'. Tidak ada data yang diubah.',
            ]);
        }

        $shipmentId = $this->argument('shipmentId');
        if ($shipmentId === null) {
            return $this->outputReport($this->candidateReport());
        }

        $shipment = DB::table('shipments')->where('id', (int) $shipmentId)->first();
        if (! $shipment) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'shipment_id' => (int) $shipmentId,
                'reason' => "Shipment #{$shipmentId} tidak ditemukan.",
            ]);
        }

        $report = $this->shipmentReport((int) $shipmentId, $shipment);
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
            $mirrors = $posting->mirrorShipment((int) $shipmentId);
            $report['status'] = 'APPLIED';
            $report['message'] = 'Mirror HPP Shipment berhasil. Akuntansi legacy tidak diubah.';
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
        $candidates = DB::table('shipments as shipments')
            ->where('shipments.status', 'posted')
            ->whereNotNull('shipments.posted_at')
            ->select(['shipments.id', 'shipments.code', 'shipments.date', 'shipments.status'])
            ->orderByDesc('shipments.id')
            ->limit(10)
            ->get()
            ->map(function ($shipment): array {
                $activeLegacy = Journal::query()
                    ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
                    ->where('source_id', $shipment->id)
                    ->whereNull('voided_at')
                    ->count();

                $waveIds = DB::table('shipment_waves')
                    ->where('shipment_id', $shipment->id)
                    ->pluck('id');
                if ($waveIds->isNotEmpty()) {
                    $activeLegacy += Journal::query()
                        ->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_WAVE_COGS)
                        ->whereIn('source_id', $waveIds->all())
                        ->whereNull('voided_at')
                        ->count();
                }

                return [
                    'id' => (int) $shipment->id,
                    'code' => $shipment->code,
                    'date' => (string) $shipment->date,
                    'status' => $shipment->status,
                    'active_legacy_journals' => $activeLegacy,
                ];
            })->all();

        return [
            'status' => count($candidates) > 0 ? 'CANDIDATES_AVAILABLE' : 'NO_CANDIDATE',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_shipment_enabled', false),
            'candidates' => $candidates,
            'message' => count($candidates) > 0
                ? 'Pilih shipmentId lalu jalankan readiness check sebelum --apply.'
                : 'Tidak ada Shipment posted untuk pilot; tidak ada backfill dilakukan.',
        ];
    }

    /** @return array<string, mixed> */
    private function shipmentReport(int $shipmentId, object $shipment): array
    {
        $legacy = $this->journalSummary($shipmentId);
        $accountCodes = collect($legacy['journals'])
            ->flatMap(fn (array $journal) => $journal['account_codes'])
            ->unique()
            ->values();

        $mapping = $accountCodes->mapWithKeys(function (string $legacyCode): array {
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

        $standalone = $this->standaloneSummary($shipmentId);
        $mappingReady = collect($mapping)->every(fn (array $row): bool => $row['active']);
        $legacyReady = $legacy['active_count'] > 0
            && $legacy['unbalanced_count'] === 0;

        return [
            'status' => $mappingReady && $legacyReady ? 'READY' : 'REVIEW',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_shipment_enabled', false),
            'shipment' => [
                'id' => $shipmentId,
                'code' => $shipment->code,
                'date' => (string) $shipment->date,
                'status' => $shipment->status,
            ],
            'mapping' => $mapping,
            'legacy' => $legacy,
            'standalone' => $standalone,
            'message' => $mappingReady && $legacyReady
                ? 'Precondition lulus. --apply tetap membutuhkan feature flag dan konfirmasi eksplisit.'
                : 'Periksa mapping dan jurnal HPP legacy; belum ada posting baru.',
        ];
    }

    /** @return array<string, mixed> */
    private function journalSummary(int $shipmentId): array
    {
        $waveIds = DB::table('shipment_waves')
            ->where('shipment_id', $shipmentId)
            ->pluck('id');

        $journals = Journal::query()
            ->where(function ($query) use ($shipmentId, $waveIds): void {
                $query->where(function ($q) use ($shipmentId): void {
                    $q->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
                        ->where('source_id', $shipmentId);
                });
                if ($waveIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($waveIds): void {
                        $q->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_WAVE_COGS)
                            ->whereIn('source_id', $waveIds->all());
                    });
                }
            })
            ->with(['lines.account'])
            ->orderBy('id')
            ->get();

        $rows = $journals->map(function (Journal $journal): array {
            return [
                'id' => (int) $journal->id,
                'source_type' => $journal->source_type,
                'source_id' => (int) $journal->source_id,
                'debit' => round((float) $journal->lines->sum('debit'), 2),
                'credit' => round((float) $journal->lines->sum('credit'), 2),
                'account_codes' => $journal->lines->map(fn ($line): string => (string) $line->account?->code)
                    ->filter()
                    ->unique()
                    ->values()
                    ->all(),
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

    /** @return array<string, mixed> */
    private function standaloneSummary(int $shipmentId): array
    {
        $waveIds = DB::table('shipment_waves')
            ->where('shipment_id', $shipmentId)
            ->pluck('id');

        $journals = StandaloneAccountingJournal::query()
            ->where(function ($query) use ($shipmentId, $waveIds): void {
                $query->where(function ($q) use ($shipmentId): void {
                    $q->where('source_id', $shipmentId)
                        ->where(function ($source) {
                            $source->where('source_type', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS)
                                ->orWhere('source_type', 'like', StandaloneProductionPostingService::SOURCE_SHIPMENT_COGS.'_repost_%');
                        });
                });
                if ($waveIds->isNotEmpty()) {
                    $query->orWhere(function ($q) use ($waveIds): void {
                        $q->whereIn('source_type', [StandaloneProductionPostingService::SOURCE_SHIPMENT_WAVE_COGS])
                            ->whereIn('source_id', $waveIds->all());
                    });
                }
            })
            ->with('lines')
            ->orderBy('id')
            ->get();

        $rows = $journals->map(fn (StandaloneAccountingJournal $journal): array => [
            'id' => (int) $journal->id,
            'source_type' => $journal->source_type,
            'source_id' => (int) $journal->source_id,
            'debit' => round((float) $journal->lines->sum('debit'), 2),
            'credit' => round((float) $journal->lines->sum('credit'), 2),
            'status' => $journal->voided_at ? 'VOID' : 'POSTED',
        ])->all();

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
        $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function failWithReport(array $report): int
    {
        $this->outputReport($report);

        return self::FAILURE;
    }
}
