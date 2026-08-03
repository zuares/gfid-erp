<?php

namespace App\Services\Marketplace\Source;

interface MarketplaceSourceAdapterInterface
{
    public function channel(): string;

    /**
     * Ubah payload API menjadi bentuk yang sama dengan hasil import CSV.
     * Satu payload order dapat menghasilkan lebih dari satu shipment/package.
     *
     * @return array<int, array<string, mixed>>
     */
    public function normalize(array $payload, int $storeId, string $sourceType = 'api'): array;
}
