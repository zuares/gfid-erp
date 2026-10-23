<?php

namespace App\Services\Marketplace\Finance;

use App\Models\MarketplaceFinanceSyncRun;

class MarketplaceFinanceSyncHealthService
{
    public const STALE_AFTER_MINUTES = 30;

    /** @return array<string, mixed> */
    public function orderSync(): array
    {
        $run = MarketplaceFinanceSyncRun::query()
            ->where('trigger', 'order_schedule')
            ->latest('id')
            ->first();

        return $this->health($run);
    }

    /** @return array<string, mixed> */
    public function health(?MarketplaceFinanceSyncRun $run): array
    {
        if (! $run) {
            return [
                'run' => null,
                'state' => 'missing',
                'label' => 'Belum ada audit otomatis',
                'message' => 'Sinkronisasi order-only belum memiliki riwayat terjadwal.',
                'store_name' => (string) config('marketplace.finance_order_sync_store_name', 'Greatfit.id'),
                'last_at' => null,
                'age_minutes' => null,
                'summary' => $this->emptySummary(),
                'audit' => [],
            ];
        }

        $audit = $this->decodeAudit($run->output);
        $summary = $this->summary($audit);
        $lastAt = $run->finished_at ?? $run->started_at ?? $run->created_at;
        $ageMinutes = $lastAt
            ? max(0, (int) floor($lastAt->diffInSeconds(now(), false) / 60))
            : null;
        $stale = $ageMinutes === null || $ageMinutes > self::STALE_AFTER_MINUTES;

        [$state, $label, $message] = match ($run->status) {
            'processing' => $stale
                ? ['stale', 'Sinkronisasi tersendat', 'Run masih berstatus diproses setelah melewati batas 30 menit.']
                : ['running', 'Sinkronisasi berjalan', 'Finance sedang memperbarui ledger order-only.'],
            'success' => $stale
                ? ['stale', 'Sinkronisasi terlambat', 'Belum ada run order-only yang selesai dalam 30 menit terakhir.']
                : ['healthy', 'Sinkronisasi sehat', 'Run order-only terakhir selesai tanpa error.'],
            'error' => ['error', 'Sinkronisasi gagal', 'Run order-only terakhir gagal; periksa audit sebelum mengandalkan data terbaru.'],
            default => ['error', 'Status tidak dikenal', 'Status audit run tidak dikenali dan perlu diperiksa.'],
        };

        return [
            'run' => $run,
            'state' => $state,
            'label' => $label,
            'message' => $message,
            'store_name' => (string) ($audit['store_name'] ?? config('marketplace.finance_order_sync_store_name', 'Greatfit.id')),
            'last_at' => $lastAt,
            'age_minutes' => $ageMinutes,
            'summary' => $summary,
            'audit' => $audit,
        ];
    }

    /** @return array<string, int> */
    private function summary(array $audit): array
    {
        $summary = $audit['apply'] ?? $audit['preflight'] ?? [];
        if (! is_array($summary)) {
            return $this->emptySummary();
        }

        return [
            'scanned' => (int) ($summary['scanned'] ?? 0),
            'transactions_created' => (int) ($summary['transactions_created'] ?? 0),
            'updated' => (int) ($summary['updated'] ?? 0),
            'components_created' => (int) ($summary['components_created'] ?? 0),
            'unmatched' => (int) ($summary['unmatched'] ?? 0),
            'duplicates' => (int) ($summary['duplicates'] ?? 0),
            'errors' => (int) ($summary['errors'] ?? 0),
        ];
    }

    /** @return array<string, int> */
    private function emptySummary(): array
    {
        return [
            'scanned' => 0,
            'transactions_created' => 0,
            'updated' => 0,
            'components_created' => 0,
            'unmatched' => 0,
            'duplicates' => 0,
            'errors' => 0,
        ];
    }

    /** @return array<string, mixed> */
    private function decodeAudit(mixed $output): array
    {
        if (is_array($output)) {
            return $output;
        }
        if (! is_string($output) || trim($output) === '') {
            return [];
        }

        $decoded = json_decode($output, true);

        return is_array($decoded) ? $decoded : [];
    }
}
