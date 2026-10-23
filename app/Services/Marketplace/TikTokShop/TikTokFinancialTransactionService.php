<?php

namespace App\Services\Marketplace\TikTokShop;

use App\Models\MarketplaceTikTokFinancialTransaction;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Support\Arr;
use RuntimeException;

class TikTokFinancialTransactionService
{
    private const TIKTOK_CODES = ['tiktok', 'ttk', 'tkt', 'tt'];

    private const TYPES = [
        'WITHDRAW' => ['label' => 'Penarikan ke Bank', 'flow' => 'MONEY_OUT', 'badge' => 'primary'],
        'SETTLE' => ['label' => 'Settlement ke Seller', 'flow' => 'MONEY_IN', 'badge' => 'success'],
        'TRANSFER' => ['label' => 'Transfer / Penyesuaian Platform', 'flow' => 'UNKNOWN', 'badge' => 'warning'],
        'REVERSE' => ['label' => 'Pembalikan Penarikan', 'flow' => 'MONEY_IN', 'badge' => 'info'],
        'PAYMENT' => ['label' => 'Pembayaran', 'flow' => 'UNKNOWN', 'badge' => 'secondary'],
        'REFUND' => ['label' => 'Refund', 'flow' => 'MONEY_OUT', 'badge' => 'danger'],
    ];

    public function __construct(
        protected MarketplaceApiGateway $gateway,
    ) {}

    /** @return array<int, array{code:string,label:string,flow:string,badge:string}> */
    public function getTransactionTypes(): array
    {
        return collect(self::TYPES)
            ->map(fn (array $meta, string $code): array => [
                'code' => $code,
                'label' => $meta['label'],
                'flow' => $meta['flow'],
                'badge' => $meta['badge'],
            ])
            ->values()
            ->all();
    }

    /**
     * Fetch one TikTok API page and upsert the normalized rows idempotently.
     * TikTok pagination is cursor based; the opaque cursor is returned intact.
     *
     * @return array<string, mixed>
     */
    public function fetchTransactions(Store $store, Carbon $from, Carbon $to, array $options = []): array
    {
        $this->assertTikTok($store);

        $types = $this->normalizeTypes($options['types'] ?? []);
        $pageSize = min(100, max(1, (int) ($options['page_size'] ?? 100)));
        $pageToken = trim((string) ($options['page_token'] ?? ''));
        $statusFilter = strtoupper(trim((string) ($options['status'] ?? '')));

        $response = $this->gateway->getSellerFinancialTransactions(
            $store,
            $from->timestamp,
            $to->timestamp,
            $types,
            $pageToken,
            $pageSize,
        );
        $this->assertSuccessfulResponse($response);

        $rows = $this->extractRows($response);
        $items = [];
        foreach ($rows as $row) {
            $item = $this->normalizeTransactionRow($row, $store);
            if ($statusFilter !== '' && $item['status'] !== $statusFilter) {
                continue;
            }

            $this->persist($item);
            $items[] = $item;
        }

        $nextPageToken = (string) (
            data_get($response, 'data.next_page_token')
            ?? data_get($response, 'response.next_page_token')
            ?? data_get($response, 'next_page_token')
            ?? ''
        );

        return [
            'items' => $items,
            'summary' => $this->summary($items),
            'pagination' => [
                'page_size' => $pageSize,
                'has_more' => $nextPageToken !== '',
                'next_page_token' => $nextPageToken,
                'source_total_count' => (int) (
                    data_get($response, 'data.total_count')
                    ?? data_get($response, 'response.total_count')
                    ?? data_get($response, 'total_count')
                    ?? count($items)
                ),
            ],
            'raw_response' => Arr::except($response, ['_meta']),
        ];
    }

    /** @return array<string, mixed> */
    public function fetchAllTransactions(Store $store, Carbon $from, Carbon $to, array $options = []): array
    {
        $items = [];
        $pageToken = '';
        $pages = 0;
        $lastPage = [];

        do {
            $pages++;
            $page = $this->fetchTransactions($store, $from, $to, array_merge($options, [
                'page_size' => 100,
                'page_token' => $pageToken,
            ]));
            $items = array_merge($items, $page['items']);
            $lastPage = $page;
            $next = (string) data_get($page, 'pagination.next_page_token', '');
            if ($next === '' || $next === $pageToken || $pages >= 100) {
                break;
            }
            $pageToken = $next;
        } while (true);

        return [
            'items' => $items,
            'summary' => $this->summary($items),
            'pagination' => [
                'page_size' => count($items),
                'has_more' => false,
                'next_page_token' => '',
                'pages_fetched' => $pages,
                'source_total_count' => data_get($lastPage, 'pagination.source_total_count', count($items)),
            ],
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeTransactionRow(array $row, Store $store): array
    {
        $id = trim((string) ($row['id'] ?? $row['transaction_id'] ?? $row['withdraw_id'] ?? ''));
        if ($id === '') {
            throw new RuntimeException('TikTok financial transaction tidak memiliki ID.');
        }

        $type = strtoupper(trim((string) ($row['type'] ?? $row['transaction_type'] ?? 'UNKNOWN')));
        $meta = self::TYPES[$type] ?? [
            'label' => ucwords(strtolower(str_replace('_', ' ', $type))),
            'flow' => 'UNKNOWN',
            'badge' => 'secondary',
        ];
        $rawAmount = $row['amount'] ?? $row['transaction_amount'] ?? 0;
        $signedRawAmount = $this->numericAmount($rawAmount);
        $flow = $meta['flow'];
        if ($flow === 'UNKNOWN' && ! array_key_exists($type, self::TYPES) && $signedRawAmount !== null) {
            $flow = $signedRawAmount < 0 ? 'MONEY_OUT' : ($signedRawAmount > 0 ? 'MONEY_IN' : 'UNKNOWN');
        }
        $amount = abs($signedRawAmount ?? 0.0);
        $createdAt = $this->timestamp($row['create_time'] ?? $row['created_at'] ?? null);
        $currency = strtoupper(trim((string) ($row['currency'] ?? 'IDR'))) ?: 'IDR';
        $status = strtoupper(trim((string) ($row['status'] ?? 'UNKNOWN'))) ?: 'UNKNOWN';

        return [
            'transaction_id' => $id,
            'store_id' => $store->id,
            'store_name' => $store->name,
            'create_time' => $createdAt?->timestamp,
            'date_formatted' => $createdAt?->format('Y-m-d H:i:s') ?? '—',
            'date_human' => $createdAt?->translatedFormat('d M Y, H:i') ?? '—',
            'amount' => round($amount, 2),
            'signed_amount' => $flow === 'MONEY_OUT' ? -$amount : ($flow === 'MONEY_IN' ? $amount : $signedRawAmount),
            'formatted_amount' => $this->formatAmount($amount, $currency),
            'signed_formatted_amount' => $this->formatSignedAmount($flow, $amount, $currency),
            'currency' => $currency,
            'money_flow' => $flow,
            'transaction_type' => $type,
            'transaction_type_label' => $meta['label'],
            'badge' => $meta['badge'],
            'status' => $status,
            'reason' => trim((string) ($row['reason'] ?? $row['description'] ?? '')) ?: null,
            'raw_payload' => $row,
        ];
    }

    /** @param array<int, array<string, mixed>> $items */
    private function summary(array $items): array
    {
        $breakdown = [];
        $moneyIn = 0.0;
        $moneyOut = 0.0;
        $unknown = 0;

        foreach ($items as $item) {
            $flow = $item['money_flow'];
            $amount = (float) $item['amount'];
            if ($flow === 'MONEY_IN') {
                $moneyIn += $amount;
            } elseif ($flow === 'MONEY_OUT') {
                $moneyOut += $amount;
            } else {
                $unknown++;
            }

            $type = $item['transaction_type'];
            $breakdown[$type] ??= [
                'type' => $type,
                'label' => $item['transaction_type_label'],
                'count' => 0,
                'total_amount' => 0.0,
                'badge' => $item['badge'],
            ];
            $breakdown[$type]['count']++;
            $breakdown[$type]['total_amount'] += $amount;
        }

        return [
            'total_count' => count($items),
            'total_money_in' => round($moneyIn, 2),
            'total_money_out' => round($moneyOut, 2),
            'net_amount' => round($moneyIn - $moneyOut, 2),
            'unknown_flow_count' => $unknown,
            'type_breakdown' => array_values($breakdown),
        ];
    }

    /** @return array<int, string> */
    private function normalizeTypes(mixed $types): array
    {
        if (is_string($types)) {
            $types = explode(',', $types);
        }
        $types = array_values(array_unique(array_filter(array_map(
            static fn (mixed $type): string => strtoupper(trim((string) $type)),
            is_array($types) ? $types : [],
        ))));
        $types = array_values(array_intersect($types, array_keys(self::TYPES)));

        return $types !== [] ? $types : array_keys(self::TYPES);
    }

    /** @return array<int, array<string, mixed>> */
    private function extractRows(array $response): array
    {
        foreach ([
            data_get($response, 'data.withdrawals'),
            data_get($response, 'response.withdrawals'),
            data_get($response, 'withdrawals'),
        ] as $rows) {
            if (is_array($rows)) {
                return array_values(array_filter($rows, 'is_array'));
            }
        }

        return [];
    }

    private function persist(array $item): void
    {
        MarketplaceTikTokFinancialTransaction::updateOrCreate(
            [
                'store_id' => $item['store_id'],
                'external_transaction_id' => $item['transaction_id'],
            ],
            [
                'transaction_type' => $item['transaction_type'],
                'amount' => $item['amount'],
                'currency' => $item['currency'],
                'money_flow' => $item['money_flow'],
                'status' => $item['status'],
                'transaction_created_at' => $item['create_time']
                    ? Carbon::createFromTimestamp($item['create_time'], config('app.timezone'))
                    : null,
                'source_payload' => $item['raw_payload'],
                'synced_at' => now(),
            ],
        );
    }

    private function assertTikTok(Store $store): void
    {
        $code = strtolower((string) ($store->channel?->code ?? ''));
        if (! in_array($code, self::TIKTOK_CODES, true)) {
            throw new RuntimeException("Toko #{$store->id} ({$store->name}) bukan channel TikTok Shop.");
        }
    }

    private function assertSuccessfulResponse(array $response): void
    {
        if (! empty($response['error']) || (isset($response['code']) && (int) $response['code'] !== 0)) {
            throw new RuntimeException('TikTok Finance API Error: '.(string) ($response['message'] ?? $response['error'] ?? 'Unknown error'));
        }
    }

    private function numericAmount(mixed $value): ?float
    {
        if ($value === null || $value === '') {
            return null;
        }
        $normalized = str_replace([',', ' '], '', (string) $value);

        return is_numeric($normalized) ? (float) $normalized : null;
    }

    private function timestamp(mixed $value): ?Carbon
    {
        $numeric = $this->numericAmount($value);
        if ($numeric === null || $numeric <= 0) {
            return null;
        }

        return Carbon::createFromTimestamp((int) $numeric, config('app.timezone'));
    }

    private function formatAmount(float $amount, string $currency): string
    {
        return $currency.' '.number_format($amount, 2, ',', '.');
    }

    private function formatSignedAmount(string $flow, float $amount, string $currency): string
    {
        if ($flow === 'MONEY_IN') {
            return '+ '.$this->formatAmount($amount, $currency);
        }
        if ($flow === 'MONEY_OUT') {
            return '- '.$this->formatAmount($amount, $currency);
        }

        return $this->formatAmount($amount, $currency);
    }
}
