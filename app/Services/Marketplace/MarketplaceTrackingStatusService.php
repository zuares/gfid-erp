<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;

class MarketplaceTrackingStatusService
{
    private const FAILED_STATUSES = [
        'FAILED_DELIVERY',
        'DELIVERY_FAILED',
        'LOGISTICS_DELIVERY_FAILED',
        'UNDELIVERED',
        'RETURN_TO_SELLER',
        'RETURNED_TO_SELLER',
    ];

    public function isFailedPackageStatus(?string $status): bool
    {
        return in_array(strtoupper(trim((string) $status)), self::FAILED_STATUSES, true);
    }

    /**
     * Simpan ringkasan tracking terakhir dan tandai kegagalan pengiriman
     * hanya bila Shopee mengirim status/teks gagal yang eksplisit.
     *
     * @param array<int,array<string,mixed>> $trackingInfo
     * @return array{failed:bool,status:?string,description:?string}
     */
    public function record(MarketplaceOrder $order, array $trackingInfo): array
    {
        $state = $this->summarize($trackingInfo);

        $order->update([
            'delivery_failed' => $state['failed'],
            'delivery_failed_at' => $state['failed'] ? ($order->delivery_failed_at ?: now()) : null,
            'tracking_status' => $state['status'],
            'tracking_description' => $state['description'],
            'tracking_checked_at' => now(),
        ]);

        return $state;
    }

    /**
     * Ringkas seluruh riwayat tracking. Event gagal dapat terjadi sebelum
     * event retur berikutnya, jadi tidak cukup hanya membaca event terakhir.
     *
     * @param array<int,array<string,mixed>> $trackingInfo
     * @return array{failed:bool,status:?string,description:?string}
     */
    public function summarize(array $trackingInfo): array
    {
        $latest = $this->latestEvent($trackingInfo);
        $failure = $this->latestFailedEvent($trackingInfo);
        $display = $failure ?: $latest;
        $status = strtoupper(trim((string) ($display['logistics_status'] ?? $display['status'] ?? '')));
        $description = trim((string) ($display['description'] ?? $display['status_description'] ?? ''));

        return [
            'failed' => $failure !== null,
            'status' => $status !== '' ? $status : null,
            'description' => $description !== '' ? $description : null,
        ];
    }

    /** @param array<int,array<string,mixed>> $trackingInfo */
    private function latestEvent(array $trackingInfo): array
    {
        foreach (array_reverse($trackingInfo) as $event) {
            if (is_array($event)) {
                return $event;
            }
        }

        return [];
    }

    /** @param array<int,array<string,mixed>> $trackingInfo */
    private function latestFailedEvent(array $trackingInfo): ?array
    {
        foreach (array_reverse($trackingInfo) as $event) {
            if (! is_array($event)) {
                continue;
            }

            $status = strtoupper(trim((string) ($event['logistics_status'] ?? $event['status'] ?? '')));
            $description = trim((string) ($event['description'] ?? $event['status_description'] ?? ''));
            if ($this->isFailedDelivery($status, $description)) {
                return $event;
            }
        }

        return null;
    }

    private function isFailedDelivery(string $status, string $description): bool
    {
        if ($this->isFailedPackageStatus($status)) {
            return true;
        }

        $description = mb_strtolower($description);

        return str_contains($description, 'gagal dikirim')
            || str_contains($description, 'gagal pengiriman')
            || str_contains($description, 'gagal antar')
            || str_contains($description, 'delivery failed')
            || str_contains($description, 'undelivered');
    }
}
