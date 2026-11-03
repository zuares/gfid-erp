<?php

namespace App\Console\Commands;

use App\Models\Journal;
use App\Models\SewingPickupLine;
use App\Models\StandaloneAccountingAccount;
use App\Models\StandaloneAccountingAccountMapping;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PilotSewingPickupMirror extends Command
{
    private const CONFIRMATION = 'PILOT-SEWING-PICKUP';

    private const REQUIRED_MAPPINGS = ['1201', '1202', '2102'];

    protected $signature = 'production:pilot-sewing-pickup
        {pickupId? : ID Sewing Pickup yang dipilih sebagai pilot}
        {--apply : Jalankan mirror untuk satu Sewing Pickup yang dipilih}
        {--confirm= : Konfirmasi wajib PILOT-SEWING-PICKUP saat --apply}
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Readiness check dan mirror terkontrol untuk satu Sewing Pickup pilot.';

    public function handle(StandaloneProductionPostingService $posting): int
    {
        $apply = (bool) $this->option('apply');

        if ($apply && ! config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false)) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Feature flag mirror Sewing Pickup masih OFF. Aktifkan hanya setelah approval pilot.',
            ]);
        }

        if ($apply && $this->option('confirm') !== self::CONFIRMATION) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Mirror pilot membutuhkan --confirm='.self::CONFIRMATION.'. Tidak ada data yang diubah.',
            ]);
        }

        $pickupId = $this->argument('pickupId');
        if ($pickupId === null) {
            return $this->outputReport($this->candidateReport());
        }

        $pickup = DB::table('sewing_pickups')->where('id', (int) $pickupId)->first();
        if (! $pickup) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'pickup_id' => (int) $pickupId,
                'reason' => "Sewing Pickup #{$pickupId} tidak ditemukan.",
            ]);
        }

        $report = $this->pickupReport((int) $pickupId, $pickup);
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
            $mirrors = $posting->mirrorSewingPickup((int) $pickupId);
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
        $candidates = DB::table('sewing_pickups as pickups')
            ->whereIn('pickups.status', ['draft', 'posted', 'closed'])
            ->select(['pickups.id', 'pickups.code', 'pickups.date', 'pickups.status'])
            ->orderByDesc('pickups.id')
            ->limit(10)
            ->get()
            ->map(function ($pickup): array {
                $lineIds = SewingPickupLine::query()
                    ->where('sewing_pickup_id', $pickup->id)
                    ->pluck('id');
                $activeCount = Journal::query()
                    ->whereNull('voided_at')
                    ->where(function ($query) use ($pickup, $lineIds): void {
                        $query->where(function ($q) use ($pickup): void {
                            $q->whereIn('source_type', [
                                StandaloneProductionPostingService::SOURCE_SEWING_PICKUP,
                                StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_SUPPLY,
                            ])->where('source_id', $pickup->id);
                        });
                        if ($lineIds->isNotEmpty()) {
                            $query->orWhere(function ($q) use ($lineIds): void {
                                $q->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE)
                                    ->whereIn('source_id', $lineIds->all());
                            });
                        }
                    })
                    ->count();

                return [
                    'id' => (int) $pickup->id,
                    'code' => $pickup->code,
                    'date' => (string) $pickup->date,
                    'status' => $pickup->status,
                    'active_legacy_journals' => $activeCount,
                ];
            })->all();

        return [
            'status' => count($candidates) > 0 ? 'CANDIDATES_AVAILABLE' : 'NO_CANDIDATE',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false),
            'candidates' => $candidates,
            'message' => count($candidates) > 0
                ? 'Pilih pickupId lalu jalankan readiness check sebelum --apply.'
                : 'Tidak ada Sewing Pickup untuk pilot; tidak ada backfill dilakukan.',
        ];
    }

    /** @return array<string, mixed> */
    private function pickupReport(int $pickupId, object $pickup): array
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

        $lineIds = SewingPickupLine::query()
            ->where('sewing_pickup_id', $pickupId)
            ->pluck('id');
        $legacy = $this->journalSummary(Journal::query(), $pickupId, $lineIds);
        $standalone = $this->journalSummary(StandaloneAccountingJournal::query(), $pickupId, $lineIds);

        $mappingReady = collect($mapping)->every(fn (array $row): bool => $row['active']);
        $legacyReady = $legacy['active_count'] > 0 && $legacy['unbalanced_count'] === 0;

        return [
            'status' => $mappingReady && $legacyReady ? 'READY' : 'REVIEW',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_sewing_pickup_enabled', false),
            'pickup' => [
                'id' => $pickupId,
                'code' => $pickup->code,
                'date' => (string) $pickup->date,
                'status' => $pickup->status,
            ],
            'mapping' => $mapping,
            'legacy' => $legacy,
            'standalone' => $standalone,
            'message' => 'Readiness check saja; belum ada backfill atau posting baru.',
        ];
    }

    /** @return array<string, mixed> */
    private function journalSummary($query, int $pickupId, $lineIds): array
    {
        $journals = $query->whereNull('voided_at')
            ->where(function ($q) use ($pickupId, $lineIds): void {
                $q->where(function ($q2) use ($pickupId): void {
                    $q2->whereIn('source_type', [
                        StandaloneProductionPostingService::SOURCE_SEWING_PICKUP,
                        StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_SUPPLY,
                    ])->where('source_id', $pickupId);
                });
                if ($lineIds->isNotEmpty()) {
                    $q->orWhere(function ($q2) use ($lineIds): void {
                        $q2->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_PICKUP_WAGE)
                            ->whereIn('source_id', $lineIds->all());
                    });
                }
            })
            ->with('lines')
            ->orderBy('id')
            ->get();

        $items = $journals->map(function ($journal): array {
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
        })->values()->all();

        return [
            'total_count' => count($items),
            'active_count' => count($items),
            'voided_count' => 0,
            'unbalanced_count' => count(array_filter($items, fn (array $item): bool => abs($item['debit'] - $item['credit']) > 0.01)),
            'journals' => $items,
        ];
    }

    /** @param array<string, mixed> $report */
    private function outputReport(array $report): int
    {
        if ($this->option('json')) {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        } else {
            $this->line(json_encode($report, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        }

        return self::SUCCESS;
    }

    /** @param array<string, mixed> $report */
    private function failWithReport(array $report): int
    {
        $this->outputReport($report);

        return self::FAILURE;
    }
}
