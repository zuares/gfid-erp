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

    /** @return array<int, array{code:string,label:string,description:string}> */
    public function getDataSources(): array
    {
        return [
            [
                'code' => 'statements',
                'label' => 'Detail settlement statement',
                'description' => 'ORDER, RESERVE, dan adjustment dengan rincian revenue, fee, pajak, dan ongkir',
            ],
            [
                'code' => 'account',
                'label' => 'Mutasi akun seller',
                'description' => 'WITHDRAW, SETTLE, TRANSFER, REVERSE, PAYMENT, dan REFUND',
            ],
        ];
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

    /**
     * Read the normalized transaction listing from the local database.
     *
     * API calls belong to the sync job; page requests must stay read-only and
     * remain available when TikTok is slow, rate-limited, or temporarily down.
     *
     * @return array<string, mixed>
     */
    public function storedTransactions(Store $store, Carbon $from, Carbon $to, array $options = []): array
    {
        $this->assertTikTok($store);

        $source = strtolower(trim((string) ($options['source'] ?? 'statements')));
        $sourceValue = match ($source) {
            'account' => 'withdrawals',
            'statements' => 'statement_transactions',
            default => throw new RuntimeException('Sumber data TikTok tidak valid.'),
        };
        $types = $this->normalizeTypes($options['types'] ?? []);
        $statusFilter = strtoupper(trim((string) ($options['status'] ?? '')));
        $pageSize = min(100, max(1, (int) ($options['page_size'] ?? 100)));
        $page = max(1, (int) ($options['page'] ?? 1));

        $baseQuery = MarketplaceTikTokFinancialTransaction::query()
            ->where('store_id', $store->id)
            ->where('source', $sourceValue)
            ->whereBetween('transaction_created_at', [$from, $to]);
        $query = (clone $baseQuery)
            ->when($statusFilter !== '', fn ($query) => $query->where('status', $statusFilter))
            ->when($source === 'account', fn ($query) => $query->whereIn('transaction_type', $types));

        $summary = $this->storedSummary(clone $query);
        $lastSyncedAt = (clone $baseQuery)->max('synced_at');

        if (($options['all'] ?? false) === true) {
            $rows = $query
                ->orderByDesc('transaction_created_at')
                ->orderByDesc('id')
                ->get();

            $items = $rows->map(fn (MarketplaceTikTokFinancialTransaction $row): array => $this->presentStoredRow($row, $store->name))->all();

            return [
                'items' => $items,
                'summary' => $summary,
                'pagination' => [
                    'page_size' => count($items),
                    'current_page' => 1,
                    'last_page' => 1,
                    'total' => $summary['total_count'],
                    'has_more' => false,
                ],
                'last_synced_at' => $this->formatSyncTime($lastSyncedAt),
            ];
        }

        $paginator = $query
            ->orderByDesc('transaction_created_at')
            ->orderByDesc('id')
            ->paginate($pageSize, ['*'], 'page', $page);

        return [
            'items' => collect($paginator->items())
                ->map(fn (MarketplaceTikTokFinancialTransaction $row): array => $this->presentStoredRow($row, $store->name))
                ->all(),
            'summary' => $summary,
            'pagination' => [
                'page_size' => $paginator->perPage(),
                'current_page' => $paginator->currentPage(),
                'last_page' => $paginator->lastPage(),
                'total' => $paginator->total(),
                'has_more' => $paginator->hasMorePages(),
            ],
            'last_synced_at' => $this->formatSyncTime($lastSyncedAt),
        ];
    }

    /**
     * Sync both local listing sources. This is called by the queue job and
     * scheduler command, never by the listing request.
     *
     * @return array<string, mixed>
     */
    public function syncStoredTransactions(Store $store, Carbon $from, Carbon $to, string $source = 'all'): array
    {
        $source = strtolower(trim($source));
        if (! in_array($source, ['account', 'statements', 'all'], true)) {
            throw new RuntimeException('Sumber sync TikTok tidak valid.');
        }

        $result = [
            'account' => ['count' => 0],
            'statements' => ['count' => 0],
            'total' => 0,
        ];

        if (in_array($source, ['account', 'all'], true)) {
            $account = $this->fetchAllTransactions($store, $from, $to);
            $result['account'] = [
                'count' => count($account['items'] ?? []),
                'pages' => (int) data_get($account, 'pagination.pages_fetched', 1),
            ];
        }

        if (in_array($source, ['statements', 'all'], true)) {
            $statements = $this->fetchStatementTransactions($store, $from, $to);
            $result['statements'] = [
                'count' => count($statements['items'] ?? []),
                'statements' => (int) data_get($statements, 'pagination.statements_fetched', 0),
                'pages' => (int) data_get($statements, 'pagination.transaction_pages_fetched', 0),
            ];
        }

        $result['total'] = $result['account']['count'] + $result['statements']['count'];

        return $result;
    }

    /**
     * Fetch the final settlement statement transactions for one TikTok order.
     * This is a read-only diagnostic path used by the store API test tool.
     *
     * @return array<string, mixed>
     */
    public function fetchOrderStatementTransactions(Store $store, string $orderId): array
    {
        $this->assertTikTok($store);

        $response = $this->gateway->getOrderStatementTransactions($store, $orderId);
        $this->assertSuccessfulResponse($response);

        return Arr::except($response, ['_meta']);
    }

    /**
     * Fetch all statement pages and their detail transaction pages for a date
     * range. Statement rows are final settlement details, not account-level
     * withdrawals, so they are persisted with a separate source namespace.
     *
     * @return array<string, mixed>
     */
    public function fetchStatementTransactions(Store $store, Carbon $from, Carbon $to, array $options = []): array
    {
        $this->assertTikTok($store);

        $pageSize = min(100, max(1, (int) ($options['page_size'] ?? 100)));
        $statusFilter = strtoupper(trim((string) ($options['status'] ?? '')));
        $items = [];
        $statementToken = '';
        $statementPages = 0;
        $transactionPages = 0;
        $statements = 0;
        $maxPages = 100;

        for ($statementPages = 1; $statementPages <= $maxPages; $statementPages++) {
            $statementResponse = $this->gateway->getStatements(
                $store,
                $from->timestamp,
                $to->timestamp,
                $statementToken,
                $pageSize,
            );
            $this->assertSuccessfulResponse($statementResponse);

            foreach ($this->extractStatements($statementResponse) as $statement) {
                $statementId = trim((string) ($statement['id'] ?? $statement['statement_id'] ?? ''));
                if ($statementId === '') {
                    continue;
                }

                $statements++;
                $transactionToken = '';
                for ($statementTransactionPage = 1; $statementTransactionPage <= $maxPages; $statementTransactionPage++) {
                    $transactionPages++;
                    $transactionResponse = $this->gateway->getTransactionsByStatement(
                        $store,
                        $statementId,
                        $transactionToken,
                        $pageSize,
                    );
                    $this->assertSuccessfulResponse($transactionResponse);

                    foreach ($this->extractStatementTransactions($transactionResponse) as $row) {
                        $item = $this->normalizeStatementTransactionRow($row, $store, $statement);
                        if ($statusFilter !== '' && $item['status'] !== $statusFilter) {
                            continue;
                        }

                        $this->persist($item);
                        $items[] = $item;
                    }

                    $nextTransactionToken = $this->nextPageToken($transactionResponse);
                    if ($nextTransactionToken === '' || $nextTransactionToken === $transactionToken) {
                        break;
                    }
                    $transactionToken = $nextTransactionToken;
                }
            }

            $nextStatementToken = $this->nextPageToken($statementResponse);
            if ($nextStatementToken === '' || $nextStatementToken === $statementToken) {
                break;
            }
            $statementToken = $nextStatementToken;
        }

        return [
            'items' => $items,
            'summary' => $this->summary($items),
            'pagination' => [
                'page_size' => count($items),
                'has_more' => false,
                'next_page_token' => '',
                'statements_fetched' => $statements,
                'statement_pages_fetched' => $statementPages,
                'transaction_pages_fetched' => $transactionPages,
                'source_total_count' => count($items),
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
            'source' => 'withdrawals',
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
            'statement_id' => null,
            'order_id' => null,
            'adjustment_id' => null,
            'settlement_amount' => null,
            'revenue_amount' => null,
            'shipping_cost_amount' => null,
            'fee_tax_amount' => null,
            'adjustment_amount' => null,
            'reserve_amount' => null,
            'raw_payload' => $row,
        ];
    }

    /** @return array<string, mixed> */
    public function normalizeStatementTransactionRow(array $row, Store $store, array $statement = []): array
    {
        $statementId = $this->firstNonEmptyString(
            $statement['id'] ?? null,
            $statement['statement_id'] ?? null,
        );
        $id = $this->firstNonEmptyString(
            $row['id'] ?? null,
            $row['transaction_id'] ?? null,
            $row['adjustment_id'] ?? null,
            $row['order_id'] ?? null,
        );
        if ($id === null) {
            throw new RuntimeException('TikTok statement transaction tidak memiliki ID.');
        }

        $type = strtoupper(trim((string) ($row['type'] ?? $row['transaction_type'] ?? 'OTHER_ADJUSTMENT')));
        $settlementAmount = $this->numericAmount($row['settlement_amount'] ?? null);
        $revenueAmount = $this->numericAmount($row['revenue_amount'] ?? null);
        $shippingCostAmount = $this->numericAmount($row['shipping_cost_amount'] ?? null);
        $feeTaxAmount = $this->numericAmount($row['fee_tax_amount'] ?? null);
        $adjustmentAmount = $this->numericAmount($row['adjustment_amount'] ?? null);
        $reserveAmount = $this->numericAmount($row['reserve_amount'] ?? null);
        $signedAmount = $settlementAmount
            ?? $adjustmentAmount
            ?? $reserveAmount
            ?? 0.0;
        $flow = $signedAmount < 0 ? 'MONEY_OUT' : ($signedAmount > 0 ? 'MONEY_IN' : 'UNKNOWN');
        $amount = abs($signedAmount);
        $createdAt = $this->timestamp(
            $row['order_create_time']
                ?? $row['create_time']
                ?? $statement['create_time']
                ?? $statement['statement_time']
                ?? null,
        );
        $currency = strtoupper(trim((string) ($row['currency'] ?? $statement['currency'] ?? 'IDR'))) ?: 'IDR';
        $status = strtoupper(trim((string) ($row['status'] ?? $statement['status'] ?? 'SETTLED'))) ?: 'SETTLED';
        $meta = $this->statementTypeMeta($type);
        $orderId = $this->firstNonEmptyString(
            $row['order_id'] ?? null,
            $row['associated_order_id'] ?? null,
            $row['adjustment_order_id'] ?? null,
        );
        $adjustmentId = $this->firstNonEmptyString($row['adjustment_id'] ?? null);

        return [
            'transaction_id' => $id,
            'store_id' => $store->id,
            'store_name' => $store->name,
            'source' => 'statement_transactions',
            'create_time' => $createdAt?->timestamp,
            'date_formatted' => $createdAt?->format('Y-m-d H:i:s') ?? '—',
            'date_human' => $createdAt?->translatedFormat('d M Y, H:i') ?? '—',
            'amount' => round($amount, 2),
            'signed_amount' => $signedAmount,
            'formatted_amount' => $this->formatAmount($amount, $currency),
            'signed_formatted_amount' => $this->formatSignedAmount($flow, $amount, $currency),
            'currency' => $currency,
            'money_flow' => $flow,
            'transaction_type' => $type,
            'transaction_type_label' => $meta['label'],
            'badge' => $meta['badge'],
            'status' => $status,
            'reason' => trim((string) ($row['reason'] ?? $row['description'] ?? $row['adjustment_type'] ?? '')) ?: null,
            'statement_id' => $statementId,
            'order_id' => $orderId,
            'adjustment_id' => $adjustmentId,
            'settlement_amount' => $settlementAmount,
            'revenue_amount' => $revenueAmount,
            'shipping_cost_amount' => $shippingCostAmount,
            'fee_tax_amount' => $feeTaxAmount,
            'adjustment_amount' => $adjustmentAmount,
            'reserve_amount' => $reserveAmount,
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
                'source' => $item['source'],
                'external_transaction_id' => $item['transaction_id'],
            ],
            [
                'source' => $item['source'],
                'transaction_type' => $item['transaction_type'],
                'amount' => $item['amount'],
                'currency' => $item['currency'],
                'money_flow' => $item['money_flow'],
                'status' => $item['status'],
                'transaction_created_at' => $item['create_time']
                    ? Carbon::createFromTimestamp($item['create_time'], config('app.timezone'))
                    : null,
                'statement_id' => $item['statement_id'],
                'order_id' => $item['order_id'],
                'adjustment_id' => $item['adjustment_id'],
                'settlement_amount' => $item['settlement_amount'],
                'revenue_amount' => $item['revenue_amount'],
                'shipping_cost_amount' => $item['shipping_cost_amount'],
                'fee_tax_amount' => $item['fee_tax_amount'],
                'adjustment_amount' => $item['adjustment_amount'],
                'reserve_amount' => $item['reserve_amount'],
                'source_payload' => $item['raw_payload'],
                'synced_at' => now(),
            ],
        );
    }

    /** @return array<string, mixed> */
    private function storedSummary($query): array
    {
        $moneyIn = (float) (clone $query)->where('money_flow', 'MONEY_IN')->sum('amount');
        $moneyOut = (float) (clone $query)->where('money_flow', 'MONEY_OUT')->sum('amount');
        $unknown = (int) (clone $query)->where(function ($query): void {
            $query->whereNull('money_flow')->orWhereNotIn('money_flow', ['MONEY_IN', 'MONEY_OUT']);
        })->count();

        $breakdown = (clone $query)
            ->select('transaction_type')
            ->selectRaw('COUNT(*) as count, SUM(amount) as total_amount')
            ->groupBy('transaction_type')
            ->orderByDesc('count')
            ->get()
            ->map(function ($row): array {
                $meta = $this->typeMeta((string) $row->transaction_type);

                return [
                    'type' => (string) $row->transaction_type,
                    'label' => $meta['label'],
                    'count' => (int) $row->count,
                    'total_amount' => round((float) $row->total_amount, 2),
                    'badge' => $meta['badge'],
                ];
            })
            ->values()
            ->all();

        return [
            'total_count' => (int) (clone $query)->count(),
            'total_money_in' => round($moneyIn, 2),
            'total_money_out' => round($moneyOut, 2),
            'net_amount' => round($moneyIn - $moneyOut, 2),
            'unknown_flow_count' => $unknown,
            'type_breakdown' => $breakdown,
        ];
    }

    /** @return array<string, mixed> */
    private function presentStoredRow(MarketplaceTikTokFinancialTransaction $row, ?string $storeName = null): array
    {
        $createdAt = $row->transaction_created_at;
        $amount = (float) $row->amount;
        $flow = (string) ($row->money_flow ?: 'UNKNOWN');
        $type = strtoupper((string) $row->transaction_type);
        $meta = $this->typeMeta($type);
        $payload = is_array($row->source_payload) ? $row->source_payload : [];

        return [
            'transaction_id' => (string) $row->external_transaction_id,
            'store_id' => (int) $row->store_id,
            'store_name' => $storeName,
            'source' => (string) $row->source,
            'create_time' => $createdAt?->timestamp,
            'date_formatted' => $createdAt?->format('Y-m-d H:i:s') ?? '—',
            'date_human' => $createdAt?->translatedFormat('d M Y, H:i') ?? '—',
            'amount' => round($amount, 2),
            'signed_amount' => $flow === 'MONEY_OUT' ? -$amount : ($flow === 'MONEY_IN' ? $amount : $amount),
            'formatted_amount' => $this->formatAmount($amount, (string) $row->currency),
            'signed_formatted_amount' => $this->formatSignedAmount($flow, $amount, (string) $row->currency),
            'currency' => (string) $row->currency,
            'money_flow' => $flow,
            'transaction_type' => $type,
            'transaction_type_label' => $meta['label'],
            'badge' => $meta['badge'],
            'status' => strtoupper((string) ($row->status ?: 'UNKNOWN')),
            'reason' => trim((string) ($payload['reason'] ?? $payload['description'] ?? $payload['adjustment_type'] ?? '')) ?: null,
            'statement_id' => $row->statement_id,
            'order_id' => $row->order_id,
            'adjustment_id' => $row->adjustment_id,
            'settlement_amount' => $row->settlement_amount !== null ? (float) $row->settlement_amount : null,
            'revenue_amount' => $row->revenue_amount !== null ? (float) $row->revenue_amount : null,
            'shipping_cost_amount' => $row->shipping_cost_amount !== null ? (float) $row->shipping_cost_amount : null,
            'fee_tax_amount' => $row->fee_tax_amount !== null ? (float) $row->fee_tax_amount : null,
            'adjustment_amount' => $row->adjustment_amount !== null ? (float) $row->adjustment_amount : null,
            'reserve_amount' => $row->reserve_amount !== null ? (float) $row->reserve_amount : null,
            'raw_payload' => $payload,
        ];
    }

    /** @return array{label:string,flow:string,badge:string} */
    private function typeMeta(string $type): array
    {
        if (isset(self::TYPES[$type])) {
            return self::TYPES[$type];
        }

        $statementMeta = $this->statementTypeMeta($type);

        return [
            'label' => $statementMeta['label'],
            'flow' => 'UNKNOWN',
            'badge' => $statementMeta['badge'],
        ];
    }

    private function firstNonEmptyString(mixed ...$values): ?string
    {
        foreach ($values as $value) {
            $normalized = trim((string) $value);
            if ($normalized !== '') {
                return $normalized;
            }
        }

        return null;
    }

    private function formatSyncTime(mixed $value): ?string
    {
        if (! $value) {
            return null;
        }

        return Carbon::parse((string) $value, config('app.timezone'))->toIso8601String();
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
        $status = (int) data_get($response, '_meta.http_status', data_get($response, 'status', 0));
        if ($status === 429) {
            throw new RuntimeException('TikTok Finance API rate limit: Too many requests. Coba lagi beberapa saat lagi.');
        }

        if (! empty($response['error']) || (isset($response['code']) && (int) $response['code'] !== 0)) {
            throw new RuntimeException('TikTok Finance API Error: '.(string) ($response['message'] ?? $response['error'] ?? 'Unknown error'));
        }
    }

    /** @return array<int, array<string, mixed>> */
    private function extractStatements(array $response): array
    {
        foreach ([
            data_get($response, 'data.statements'),
            data_get($response, 'response.statements'),
            data_get($response, 'statements'),
        ] as $rows) {
            if (is_array($rows)) {
                return array_values(array_filter($rows, 'is_array'));
            }
        }

        return [];
    }

    /** @return array<int, array<string, mixed>> */
    private function extractStatementTransactions(array $response): array
    {
        foreach ([
            data_get($response, 'data.transactions'),
            data_get($response, 'response.transactions'),
            data_get($response, 'transactions'),
        ] as $rows) {
            if (is_array($rows)) {
                return array_values(array_filter($rows, 'is_array'));
            }
        }

        return [];
    }

    private function nextPageToken(array $response): string
    {
        return trim((string) (
            data_get($response, 'data.next_page_token')
            ?? data_get($response, 'response.next_page_token')
            ?? data_get($response, 'next_page_token')
            ?? ''
        ));
    }

    /** @return array{label:string,badge:string} */
    private function statementTypeMeta(string $type): array
    {
        return match ($type) {
            'ORDER' => ['label' => 'Order Settlement', 'badge' => 'success'],
            'RESERVE' => ['label' => 'Reserve', 'badge' => 'warning'],
            'PLATFORM_REIMBURSEMENT', 'PLATFORM_COMPENSATION', 'LOGISTICS_REIMBURSEMENT' => [
                'label' => 'Kompensasi Platform',
                'badge' => 'info',
            ],
            'GMV_PAYMENT_FOR_ADS' => ['label' => 'Pembayaran Iklan dari GMV', 'badge' => 'danger'],
            default => [
                'label' => ucwords(strtolower(str_replace('_', ' ', $type))),
                'badge' => 'secondary',
            ],
        };
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
