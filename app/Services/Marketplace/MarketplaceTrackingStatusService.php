<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceOrder;

class MarketplaceTrackingStatusService
{
    private const FAILED_STATUSES = [
        'FAILED_DELIVERY',
        'DELIVERY_FAILED',
        'UNDELIVERED',
        'RETURN_TO_SELLER',
        'RETURNED_TO_SELLER',
    ];

    /**
     * Simpan ringkasan tracking terakhir dan tandai kegagalan pengiriman
     * hanya bila Shopee mengirim status/teks gagal yang eksplisit.
     *
     * @param array<int,array<string,mixed>> $trackingInfo
     * @return array{failed:bool,status:?string,description:?string}
     */
    public function record(MarketplaceOrder $order, array $trackingInfo): array
    {
        $latest = $this->latestEvent($trackingInfo);
        $status = strtoupper(trim((string) ($latest['logistics_status'] ?? $latest['status'] ?? '')));
        $description = trim((string) ($latest['description'] ?? $latest['status_description'] ?? ''));
        $failed = $this->isFailedDelivery($status, $description);

        $order->update([
            'delivery_failed' => $failed,
            'delivery_failed_at' => $failed ? ($order->delivery_failed_at ?: now()) : null,
            'tracking_status' => $status !== '' ? $status : null,
            'tracking_description' => $description !== '' ? $description : null,
            'tracking_checked_at' => now(),
        ]);

        return [
            'failed' => $failed,
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

    private function isFailedDelivery(string $status, string $description): bool
    {
        if (in_array($status, self::FAILED_STATUSES, true)) {
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
