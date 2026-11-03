<?php

namespace App\Console\Commands;

use App\Models\SewingRejectConversion;
use App\Models\StandaloneAccountingJournal;
use App\Services\Accounting\StandaloneProductionPostingService;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class PilotSewingRejectConversionMirror extends Command
{
    private const CONFIRMATION = 'PILOT-REJECT-CONVERSION';

    protected $signature = 'production:pilot-reject-conversion
        {conversionId? : ID Sewing Reject Conversion yang dipilih}
        {--apply : Jalankan mirror untuk conversion yang dipilih}
        {--confirm= : Konfirmasi wajib PILOT-REJECT-CONVERSION saat --apply}
        {--json : Cetak hasil dalam JSON}';

    protected $description = 'Readiness check dan mirror terkontrol untuk Sewing Reject Conversion.';

    public function handle(StandaloneProductionPostingService $posting): int
    {
        $apply = (bool) $this->option('apply');

        if ($apply && ! config('accounting.standalone_dual_posting_production_sewing_return_enabled', false)) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Feature flag mirror Sewing Return/Reject masih OFF.',
            ]);
        }

        if ($apply && $this->option('confirm') !== self::CONFIRMATION) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'reason' => 'Mirror membutuhkan --confirm='.self::CONFIRMATION.'. Tidak ada data yang diubah.',
            ]);
        }

        $conversionId = $this->argument('conversionId');
        if ($conversionId === null) {
            return $this->outputReport($this->candidateReport());
        }

        $conversion = SewingRejectConversion::query()->find((int) $conversionId);
        if (! $conversion) {
            return $this->failWithReport([
                'status' => 'BLOCKED',
                'conversion_id' => (int) $conversionId,
                'reason' => "Sewing Reject Conversion #{$conversionId} tidak ditemukan.",
            ]);
        }

        $report = $this->conversionReport((int) $conversionId, $conversion);
        if ($apply) {
            $report['mode'] = 'APPLY';
        }

        if (! $apply) {
            return $this->outputReport($report);
        }

        if ($report['status'] !== 'READY') {
            $report['status'] = 'BLOCKED';
            $report['reason'] = 'Precondition belum lulus; mirror tidak dijalankan.';

            return $this->failWithReport($report);
        }

        try {
            $journal = $posting->mirrorSewingRejectConversion((int) $conversionId);
            $report['status'] = 'APPLIED';
            $report['message'] = 'Mirror reject conversion berhasil. Legacy dan mutasi stok tidak diubah.';
            $report['mirror'] = $journal ? [
                'id' => (int) $journal->id,
                'source_type' => $journal->source_type,
                'source_id' => (int) $journal->source_id,
                'reference' => $journal->reference,
                'debit' => round((float) $journal->lines->sum('debit'), 2),
                'credit' => round((float) $journal->lines->sum('credit'), 2),
                'voided_at' => $journal->voided_at?->toISOString(),
            ] : null;
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
        $candidates = SewingRejectConversion::query()
            ->where('status', 'posted')
            ->orderByDesc('id')
            ->limit(10)
            ->get(['id', 'code', 'date', 'status', 'qty'])
            ->map(fn (SewingRejectConversion $conversion): array => [
                'id' => (int) $conversion->id,
                'code' => $conversion->code,
                'date' => $conversion->date?->toDateString(),
                'status' => $conversion->status,
                'qty' => (float) $conversion->qty,
                'active_standalone_journals' => StandaloneAccountingJournal::query()
                    ->where('source_id', $conversion->id)
                    ->where(function ($query): void {
                        $query
                            ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT)
                            ->orWhere('source_type', 'like', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT.'_repost_%');
                    })
                    ->whereNull('voided_at')
                    ->count(),
            ])->all();

        return [
            'status' => count($candidates) > 0 ? 'CANDIDATES_AVAILABLE' : 'NO_CANDIDATE',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_sewing_return_enabled', false),
            'candidates' => $candidates,
            'message' => count($candidates) > 0
                ? 'Pilih conversionId lalu jalankan readiness check sebelum --apply.'
                : 'Tidak ada Sewing Reject Conversion posted; tidak ada backfill dilakukan.',
        ];
    }

    /** @return array<string, mixed> */
    private function conversionReport(int $conversionId, SewingRejectConversion $conversion): array
    {
        $movements = DB::table('inventory_mutations as im')
            ->leftJoin('warehouses as w', 'w.id', '=', 'im.warehouse_id')
            ->where('im.source_type', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT)
            ->where('im.source_id', $conversionId)
            ->select('im.qty_change', 'im.total_cost', 'w.code as warehouse_code')
            ->get();

        $out = $movements->filter(fn ($row): bool => (float) $row->qty_change < 0);
        $in = $movements->filter(fn ($row): bool => (float) $row->qty_change > 0);
        $outValue = round((float) $out->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0))), 2);
        $inValue = round((float) $in->sum(fn ($row): float => abs((float) ($row->total_cost ?? 0))), 2);
        $standalone = StandaloneAccountingJournal::query()
            ->where('source_id', $conversionId)
            ->where(function ($query): void {
                $query
                    ->where('source_type', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT)
                    ->orWhere('source_type', 'like', StandaloneProductionPostingService::SOURCE_SEWING_REJECT_CONVERT.'_repost_%');
            })
            ->whereNull('voided_at')
            ->with('lines')
            ->orderBy('id')
            ->get();

        $ready = $conversion->status === 'posted'
            && $out->isNotEmpty()
            && $in->isNotEmpty()
            && $outValue > 0
            && abs($outValue - $inValue) <= 0.01;

        return [
            'status' => $ready ? 'READY' : 'REVIEW',
            'mode' => 'READ_ONLY',
            'feature_flag' => (bool) config('accounting.standalone_dual_posting_production_sewing_return_enabled', false),
            'conversion' => [
                'id' => $conversionId,
                'code' => $conversion->code,
                'date' => $conversion->date?->toDateString(),
                'status' => $conversion->status,
                'qty' => (float) $conversion->qty,
            ],
            'mapping' => [
                'debit' => '1-153',
                'credit' => '1-153',
                'purpose' => 'Perpindahan persediaan reject dari REJ-SEW ke WH-RTS',
            ],
            'mutations' => [
                'count' => $movements->count(),
                'out_value' => $outValue,
                'in_value' => $inValue,
                'warehouses' => $movements->pluck('warehouse_code')->filter()->unique()->values()->all(),
            ],
            'standalone' => $standalone->map(fn (StandaloneAccountingJournal $journal): array => [
                'id' => (int) $journal->id,
                'source_type' => $journal->source_type,
                'reference' => $journal->reference,
                'debit' => round((float) $journal->lines->sum('debit'), 2),
                'credit' => round((float) $journal->lines->sum('credit'), 2),
            ])->all(),
            'message' => $ready
                ? 'Mutasi conversion balance; siap di-mirror tanpa mengubah legacy/stok.'
                : 'Mutasi conversion belum lengkap atau tidak balance.',
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
