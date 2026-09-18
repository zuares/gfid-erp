<?php

namespace App\Services\Marketplace\Crm;

use App\Models\Customer;
use App\Models\MarketplaceImportBatch;
use App\Models\MarketplaceOrder;
use App\Models\MarketplaceOrderItem;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Illuminate\Database\QueryException;
use PhpOffice\PhpSpreadsheet\IOFactory;
use PhpOffice\PhpSpreadsheet\Shared\Date as ExcelDate;

class MarketplaceCrmImportService
{
    /**
     * Import order export marketplace ke tabel order marketplace yang sudah ada.
     *
     * Identitas order sengaja hanya memakai store + channel_order_id. File yang
     * sama atau file periode berbeda tidak akan membuat order kedua.
     */
    public function import(string $path, int $storeId, string $sourceFile, ?int $userId = null): array
    {
        $store = Store::with('channel')->findOrFail($storeId);
        $channel = strtolower((string) ($store->channel?->code ?: $store->channel?->name ?: 'marketplace'));
        $fileHash = is_file($path) ? hash_file('sha256', $path) : null;
        $batchId = (string) Str::uuid();
        $normalized = $this->parse($path, $storeId, $sourceFile);

        $stats = [
            'batch_id' => $batchId,
            'source_file' => $sourceFile,
            'file_hash' => $fileHash,
            'rows' => $normalized['rows'],
            'orders' => count($normalized['orders']),
            'items' => $normalized['items'],
            'inserted_orders' => 0,
            'updated_orders' => 0,
            'inserted_customers' => 0,
            'updated_customers' => 0,
            'collapsed_item_rows' => $normalized['collapsed_item_rows'],
            'warnings' => $normalized['warnings'],
        ];

        DB::transaction(function () use ($normalized, $store, $channel, $sourceFile, $fileHash, $batchId, $userId, &$stats) {
            $orderIds = array_keys($normalized['orders']);
            $existingOrders = [];

            // Hindari satu query per order. SQLite juga memiliki batas jumlah
            // parameter, jadi identity dicari dalam chunk kecil.
            foreach (array_chunk($orderIds, 400) as $orderIdChunk) {
                MarketplaceOrder::query()
                    ->where('store_id', $store->id)
                    ->whereIn('channel_order_id', $orderIdChunk)
                    ->get()
                    ->each(function (MarketplaceOrder $order) use (&$existingOrders): void {
                        $existingOrders[(string) $order->channel_order_id] = $order;
                    });
            }

            $preparedCustomers = $this->prepareCustomerCache($normalized['orders']);
            $customerCache = $preparedCustomers['cache'];
            $newCustomerKeys = $preparedCustomers['new_keys'];
            $orderRows = [];
            $now = now()->toDateTimeString();
            $orderColumns = [
                'store_id', 'channel_order_id', 'external_order_id', 'order_date',
                'status', 'order_status', 'buyer_username', 'buyer_name', 'buyer_phone',
                'shipping_address', 'shipping_city', 'shipping_province',
                'shipping_courier_code', 'shipping_awb_no', 'subtotal_items',
                'shipping_fee_customer', 'total_paid_customer', 'total_amount', 'currency',
                'payment_method', 'payment_status', 'payment_date', 'ordered_at', 'paid_at',
                'completed_at', 'customer_id', 'synced_at', 'raw_json', 'meta',
                'created_at', 'updated_at',
            ];

            foreach ($normalized['orders'] as $data) {
                $customerKey = $this->customerCacheKey($data);
                $customer = $customerCache[$customerKey] ?? null;
                if (isset($newCustomerKeys[$customerKey])) {
                    $stats['inserted_customers']++;
                    unset($newCustomerKeys[$customerKey]);
                } elseif ($customer) {
                    $stats['updated_customers']++;
                }

                $channelOrderId = (string) $data['order_id'];
                $existingOrder = $existingOrders[$channelOrderId] ?? null;
                $payload = $this->orderPayload($data, $customer, $channel, $sourceFile, $fileHash, $batchId);

                if ($existingOrder) {
                    $payload = $this->sparseOrderUpdatePayload($existingOrder, $payload);
                    // Jangan menghapus payload API yang mungkin sudah ada. Import file
                    // hanya menambahkan provenance CRM marketplace di metadata.
                    $payload = $this->mergeImportedMetadata($existingOrder, $payload);
                    $stats['updated_orders']++;
                } else {
                    $stats['inserted_orders']++;
                }

                $row = [
                    'store_id' => $store->id,
                    'channel_order_id' => $channelOrderId,
                ];
                foreach (array_diff($orderColumns, ['store_id', 'channel_order_id', 'created_at', 'updated_at']) as $column) {
                    $value = array_key_exists($column, $payload)
                        ? $payload[$column]
                        : ($existingOrder?->getRawOriginal($column));
                    if (in_array($column, ['raw_json', 'meta'], true)) {
                        $value = json_encode((array) $value, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES);
                    }
                    $row[$column] = $value;
                }
                $row['order_date'] = $row['order_date'] ?: $now;
                $row['created_at'] = $existingOrder?->getRawOriginal('created_at') ?: $now;
                $row['updated_at'] = $now;
                $orderRows[] = $row;
            }

            if ($orderRows) {
                // SQLite memiliki batas jumlah bind variables per statement.
                // Dengan 34 kolom, upsert seluruh file sekaligus akan gagal
                // pada file export marketplace yang besar.
                foreach (array_chunk($orderRows, 20) as $orderChunk) {
                    DB::table('marketplace_orders')->upsert(
                        $orderChunk,
                        ['store_id', 'channel_order_id'],
                        array_values(array_diff($orderColumns, ['store_id', 'channel_order_id', 'created_at']))
                    );
                }
            }

            $persistedOrders = [];
            foreach (array_chunk($orderIds, 400) as $orderIdChunk) {
                DB::table('marketplace_orders')
                    ->where('store_id', $store->id)
                    ->whereIn('channel_order_id', $orderIdChunk)
                    ->get(['id', 'channel_order_id'])
                    ->each(function ($order) use (&$persistedOrders): void {
                        $persistedOrders[(string) $order->channel_order_id] = (int) $order->id;
                    });
            }

            $persistedIds = array_values($persistedOrders);
            foreach (array_chunk($persistedIds, 400) as $idChunk) {
                MarketplaceOrderItem::query()->whereIn('order_id', $idChunk)->delete();
            }

            $itemRows = [];
            foreach ($normalized['orders'] as $data) {
                $channelOrderId = (string) $data['order_id'];
                $orderId = $persistedOrders[$channelOrderId] ?? null;
                if (! $orderId) {
                    continue;
                }

                foreach ($data['items'] as $lineNo => $item) {
                    $itemRow = $this->itemPayloadForDatabase($orderId, $item, $lineNo + 1, $sourceFile, $now);
                    $itemRows[] = $itemRow;
                    // Baris item juga memiliki banyak kolom; 20 baris tetap
                    // berada di bawah limit parameter SQLite.
                    if (count($itemRows) >= 20) {
                        DB::table('marketplace_order_items')->insert($itemRows);
                        $itemRows = [];
                    }
                }
            }
            if ($itemRows) {
                DB::table('marketplace_order_items')->insert($itemRows);
            }

            MarketplaceImportBatch::create([
                'id' => $batchId,
                'channel' => $channel,
                'store_id' => $store->id,
                'source_type' => 'marketplace_crm',
                'source_file' => $sourceFile,
                'file_hash' => $fileHash,
                'status' => 'completed',
                'total_rows' => $stats['rows'],
                'shipments_parsed' => $stats['orders'],
                'items_parsed' => $stats['items'],
                'inserted_shipments' => $stats['inserted_orders'],
                'updated_shipments' => $stats['updated_orders'],
                'inserted_items' => $stats['items'],
                'warnings' => $stats['warnings'],
                'created_by' => $userId,
                'started_at' => now(),
                'completed_at' => now(),
            ]);
        });

        return $stats;
    }

    private function parse(string $path, int $storeId, string $sourceFile): array
    {
        $reader = IOFactory::createReaderForFile($path);
        $reader->setReadDataOnly(true);
        if (method_exists($reader, 'setReadEmptyCells')) {
            $reader->setReadEmptyCells(false);
        }
        $worksheetNames = $reader->listWorksheetNames($path);
        if ($worksheetNames) {
            $reader->setLoadSheetsOnly($worksheetNames[0]);
        }
        $spreadsheet = $reader->load($path);
        $sheet = $spreadsheet->getActiveSheet();
        $rows = $sheet->toArray(null, true, true, true);
        $headers = $this->headerMap($rows[1] ?? []);
        if (! isset($headers['no. pesanan'])) {
            throw new \InvalidArgumentException('Header "No. Pesanan" tidak ditemukan pada file marketplace.');
        }

        $orders = [];
        $rowCount = 0;
        $collapsed = 0;
        $warnings = [];

        foreach ($rows as $index => $row) {
            if ($index === 1) {
                continue;
            }

            $orderId = $this->text($this->value($row, $headers, ['no. pesanan', 'no pesanan', 'order id']));
            if ($orderId === '') {
                continue;
            }

            $rowCount++;
            if (! isset($orders[$orderId])) {
                $orders[$orderId] = [
                    'order_id' => $orderId,
                    'status_original' => null,
                    'status' => 'new',
                    'tracking_no' => null,
                    'shipping_service' => null,
                    'buyer_username' => null,
                    'buyer_name' => null,
                    'buyer_phone' => null,
                    'shipping_address' => null,
                    'shipping_city' => null,
                    'shipping_province' => null,
                    'payment_method' => null,
                    'ordered_at' => null,
                    'paid_at' => null,
                    'completed_at' => null,
                    'shipping_fee' => 0,
                    'total_paid' => 0,
                    'items' => [],
                ];
            }

            $order = &$orders[$orderId];
            $statusOriginal = $this->cleanIncomingText($this->value($row, $headers, ['status pesanan']));
            $order['status_original'] = $order['status_original'] ?: $statusOriginal;
            if ($statusOriginal !== '') {
                $order['status'] = $this->normalizeStatus($statusOriginal);
            }
            $order['tracking_no'] = $this->firstValue($order['tracking_no'], $this->text($this->value($row, $headers, ['no. resi', 'no resi', 'resi'])));
            $order['shipping_service'] = $this->firstValue($order['shipping_service'], $this->text($this->value($row, $headers, ['opsi pengiriman'])));
            $order['buyer_username'] = $this->firstValue($order['buyer_username'], $this->text($this->value($row, $headers, ['username (pembeli)', 'username pembeli'])));
            $order['buyer_name'] = $this->firstValue($order['buyer_name'], $this->text($this->value($row, $headers, ['nama penerima', 'nama pembeli'])));
            $order['buyer_phone'] = $this->firstValue($order['buyer_phone'], $this->text($this->value($row, $headers, ['no. telepon', 'no telepon', 'nomor telepon'])));
            $order['shipping_address'] = $this->firstValue($order['shipping_address'], $this->text($this->value($row, $headers, ['alamat pengiriman'])));
            $order['shipping_city'] = $this->firstValue($order['shipping_city'], $this->text($this->value($row, $headers, ['kota/kabupaten', 'kota'])));
            $order['shipping_province'] = $this->firstValue($order['shipping_province'], $this->text($this->value($row, $headers, ['provinsi'])));
            $order['payment_method'] = $this->firstValue($order['payment_method'], $this->text($this->value($row, $headers, [
                'metode pembayaran pembeli',
                'metode pembayaran',
                'payment method',
            ])));
            $order['ordered_at'] = $order['ordered_at'] ?: $this->date($this->value($row, $headers, ['waktu pesanan dibuat']));
            $order['paid_at'] = $order['paid_at'] ?: $this->date($this->value($row, $headers, ['waktu pembayaran dilakukan']));
            $order['completed_at'] = $order['completed_at'] ?: $this->date($this->value($row, $headers, ['waktu pesanan selesai']));

            $shippingFee = $this->money($this->value($row, $headers, ['ongkos kirim dibayar oleh pembeli']));
            $totalPaid = $this->money($this->value($row, $headers, ['total pembayaran']));
            if ($shippingFee > 0) {
                $order['shipping_fee'] = $shippingFee;
            }
            if ($totalPaid > 0) {
                $order['total_paid'] = $totalPaid;
            }

            $item = [
                'sku' => $this->text($this->value($row, $headers, ['nomor referensi sku', 'seller sku', 'sku'])),
                'parent_sku' => $this->text($this->value($row, $headers, ['sku induk'])),
                'name' => $this->text($this->value($row, $headers, ['nama produk'])),
                'variant' => $this->text($this->value($row, $headers, ['nama variasi', 'variasi'])),
                'qty' => (int) round($this->number($this->value($row, $headers, ['jumlah', 'qty', 'quantity']))),
                'returned_qty' => (int) round($this->number($this->value($row, $headers, ['returned quantity']))),
                'original_price' => $this->money($this->value($row, $headers, ['harga awal'])),
                'price' => $this->money($this->value($row, $headers, ['harga setelah diskon', 'harga'])),
                'subtotal' => $this->money($this->value($row, $headers, [
                    'total harga produk',
                    'subtotal pesanan',
                    'subtotal',
                ])),
            ];

            if ($item['qty'] <= 0 && $item['name'] === '' && $item['sku'] === '') {
                $warnings[] = "Baris {$index} order {$orderId} tidak memiliki item yang bisa disimpan.";
                unset($order);
                continue;
            }

            $lineKey = hash('sha256', json_encode([
                $item['sku'], $item['parent_sku'], $item['name'], $item['variant'],
                $item['original_price'], $item['price'],
            ], JSON_UNESCAPED_UNICODE));

            if (isset($order['items'][$lineKey])) {
                $order['items'][$lineKey]['qty'] += $item['qty'];
                $order['items'][$lineKey]['returned_qty'] += $item['returned_qty'];
                $order['items'][$lineKey]['subtotal'] += $item['subtotal'];
                $collapsed++;
            } else {
                $order['items'][$lineKey] = $item + ['line_key' => $lineKey];
            }

            unset($order);
        }

        foreach ($orders as &$order) {
            $order['items'] = array_values($order['items']);
            if ($order['total_paid'] <= 0) {
                $order['total_paid'] = array_sum(array_column($order['items'], 'subtotal')) + $order['shipping_fee'];
            }
        }
        $spreadsheet->disconnectWorksheets();
        unset($rows, $sheet, $spreadsheet, $reader, $worksheetNames);
        gc_collect_cycles();

        return [
            'store_id' => $storeId,
            'source_file' => $sourceFile,
            'rows' => $rowCount,
            'orders' => array_values($orders),
            'items' => array_sum(array_map(fn ($o) => count($o['items']), $orders)),
            'collapsed_item_rows' => $collapsed,
            'warnings' => array_values(array_unique($warnings)),
        ];
    }

    private function orderPayload(array $data, ?Customer $customer, string $channel, string $sourceFile, ?string $fileHash, string $batchId): array
    {
        $subtotal = array_sum(array_column($data['items'], 'subtotal'));
        return [
            'external_order_id' => $data['order_id'],
            'order_date' => $data['ordered_at'],
            'status' => $data['status'],
            'order_status' => $data['status_original'],
            'buyer_username' => $data['buyer_username'] ?: null,
            'buyer_name' => $data['buyer_name'] ?: null,
            'buyer_phone' => $this->normalizePhone($data['buyer_phone']),
            'shipping_address' => $data['shipping_address'] ?: null,
            'shipping_city' => $data['shipping_city'] ?: null,
            'shipping_province' => $data['shipping_province'] ?: null,
            'shipping_courier_code' => $data['shipping_service'] ?: null,
            'shipping_awb_no' => $data['tracking_no'] ?: null,
            'subtotal_items' => $subtotal,
            'shipping_fee_customer' => $data['shipping_fee'],
            'total_paid_customer' => $data['total_paid'],
            'total_amount' => $data['total_paid'],
            'currency' => 'IDR',
            'payment_method' => $data['payment_method'] ?: null,
            'payment_status' => $data['paid_at'] ? 'paid' : 'unpaid',
            'payment_date' => $data['paid_at'],
            'ordered_at' => $data['ordered_at'],
            'paid_at' => $data['paid_at'],
            'completed_at' => $data['completed_at'],
            'customer_id' => $customer?->id,
            'synced_at' => now(),
            'raw_json' => [
                'source' => 'marketplace_crm_import',
                'channel' => $channel,
                'source_file' => $sourceFile,
                'file_hash' => $fileHash,
                'import_batch_id' => $batchId,
            ],
            'meta' => [
                'crm_source' => 'marketplace_import',
                'shipping_service' => $data['shipping_service'],
                'buyer_username' => $data['buyer_username'],
            ],
        ];
    }

    private function itemPayload(MarketplaceOrder $order, array $item, int $lineNo, string $sourceFile): array
    {
        return [
            'order_id' => $order->id,
            'marketplace_order_id' => $order->id,
            'line_no' => $lineNo,
            'external_item_id' => $item['line_key'],
            'external_sku' => $item['sku'] ?: null,
            'item_code_snapshot' => $item['parent_sku'] ?: null,
            'item_name_snapshot' => $item['name'] ?: null,
            'variant_snapshot' => $item['variant'] ?: null,
            'item_name' => $item['name'] ?: null,
            'item_sku' => $item['sku'] ?: null,
            'model_sku' => $item['parent_sku'] ?: null,
            'variant_name' => $item['variant'] ?: null,
            'qty' => $item['qty'],
            'price' => $item['price'],
            'price_original' => $item['original_price'],
            'price_after_discount' => $item['price'],
            'line_discount' => max(0, ($item['original_price'] - $item['price']) * $item['qty']),
            'line_gross_amount' => $item['subtotal'],
            'line_net_amount' => $item['subtotal'],
            'raw_json' => ['source_file' => $sourceFile, 'line_key' => $item['line_key'], 'returned_qty' => $item['returned_qty']],
        ];
    }

    private function itemPayloadForDatabase(int $orderId, array $item, int $lineNo, string $sourceFile, string $timestamp): array
    {
        return [
            'order_id' => $orderId,
            'marketplace_order_id' => $orderId,
            'line_no' => $lineNo,
            'external_item_id' => $item['line_key'],
            'external_sku' => $item['sku'] ?: null,
            'item_code_snapshot' => $item['parent_sku'] ?: null,
            'item_name_snapshot' => $item['name'] ?: null,
            'variant_snapshot' => $item['variant'] ?: null,
            'item_name' => $item['name'] ?: null,
            'item_sku' => $item['sku'] ?: null,
            'model_sku' => $item['parent_sku'] ?: null,
            'variant_name' => $item['variant'] ?: null,
            'qty' => $item['qty'],
            'price' => $item['price'],
            'price_original' => $item['original_price'],
            'price_after_discount' => $item['price'],
            'line_discount' => max(0, ($item['original_price'] - $item['price']) * $item['qty']),
            'line_gross_amount' => $item['subtotal'],
            'line_net_amount' => $item['subtotal'],
            'raw_json' => json_encode([
                'source_file' => $sourceFile,
                'line_key' => $item['line_key'],
                'returned_qty' => $item['returned_qty'],
            ], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES),
            'created_at' => $timestamp,
            'updated_at' => $timestamp,
        ];
    }

    private function customerCacheKey(array $data): string
    {
        $phone = $this->normalizePhone($this->cleanIncomingText($data['buyer_phone'] ?? null));
        if ($phone !== '') {
            return 'phone:'.$phone;
        }

        return 'fallback:'.sha1(implode('|', [
            $this->cleanIncomingText($data['buyer_name'] ?? null),
            $this->cleanIncomingText($data['shipping_address'] ?? null),
        ]));
    }

    private function prepareCustomerCache(array $orders): array
    {
        $descriptors = [];
        $phones = [];

        foreach ($orders as $data) {
            $key = $this->customerCacheKey($data);
            if (isset($descriptors[$key])) {
                continue;
            }

            $phone = $this->normalizePhone($this->cleanIncomingText($data['buyer_phone'] ?? null));
            $descriptors[$key] = compact('data', 'phone');
            if ($phone !== '') {
                $phones[] = $phone;
            }
        }

        $existingByPhone = [];
        foreach (array_chunk(array_values(array_unique($phones)), 400) as $phoneChunk) {
            Customer::query()
                ->whereIn('phone', $phoneChunk)
                ->get()
                ->each(function (Customer $customer) use (&$existingByPhone): void {
                    $existingByPhone[(string) $customer->phone] ??= $customer;
                });
        }

        $cache = [];
        $newCustomerRows = [];
        $newCustomerKeys = [];
        $newPhones = [];

        foreach ($descriptors as $key => $descriptor) {
            $data = $descriptor['data'];
            $phone = $descriptor['phone'];
            if ($phone === '') {
                $customer = $this->resolveCustomer($data);
                $cache[$key] = $customer;
                if ($customer?->wasRecentlyCreated) {
                    $newCustomerKeys[$key] = true;
                }
                continue;
            }

            if (isset($existingByPhone[$phone])) {
                $customer = $existingByPhone[$phone];
                $this->fillCustomer(
                    $customer,
                    $this->cleanIncomingText($data['buyer_name'] ?? null),
                    $phone,
                    $this->cleanIncomingText($data['shipping_address'] ?? null),
                    $data
                );
                $cache[$key] = $customer;
                continue;
            }

            $newCustomerRows[] = [
                'name' => $this->cleanIncomingText($data['buyer_name'] ?? null) ?: 'Buyer Marketplace',
                'phone' => $phone,
                'address' => $this->cleanIncomingText($data['shipping_address'] ?? null) ?: null,
                'city' => $this->cleanIncomingText($data['shipping_city'] ?? null) ?: null,
                'province' => $this->cleanIncomingText($data['shipping_province'] ?? null) ?: null,
                'created_at' => now()->toDateTimeString(),
                'updated_at' => now()->toDateTimeString(),
            ];
            $newCustomerKeys[$key] = true;
            $newPhones[] = $phone;
        }

        foreach (array_chunk($newCustomerRows, 400) as $customerChunk) {
            DB::table('customers')->insert($customerChunk);
        }

        $createdByPhone = [];
        foreach (array_chunk(array_values(array_unique($newPhones)), 400) as $phoneChunk) {
            Customer::query()
                ->whereIn('phone', $phoneChunk)
                ->get()
                ->each(function (Customer $customer) use (&$createdByPhone): void {
                    $createdByPhone[(string) $customer->phone] ??= $customer;
                });
        }

        foreach ($descriptors as $key => $descriptor) {
            if (! isset($cache[$key]) && $descriptor['phone'] !== '') {
                $cache[$key] = $createdByPhone[$descriptor['phone']] ?? null;
            }
        }

        return [
            'cache' => $cache,
            'new_keys' => $newCustomerKeys,
        ];
    }

    private function resolveCustomer(array $data): ?Customer
    {
        $phone = $this->normalizePhone($this->cleanIncomingText($data['buyer_phone']));
        $name = $this->cleanIncomingText($data['buyer_name']);
        $address = $this->cleanIncomingText($data['shipping_address']);

        if ($phone !== '') {
            $customer = Customer::query()->where('phone', $phone)->first();
            if ($customer) {
                $this->fillCustomer($customer, $name, $phone, $address, $data);
                return $customer;
            }
        }

        if ($name === '' && $phone === '') {
            return null;
        }

        $customer = Customer::query()
            ->where('name', $name !== '' ? $name : 'Buyer Marketplace')
            ->when($address !== '', fn ($q) => $q->where('address', $address))
            ->first();

        if ($customer) {
            $this->fillCustomer($customer, $name, $phone, $address, $data);
            return $customer;
        }

        return Customer::create([
            'name' => $name !== '' ? $name : 'Buyer Marketplace',
            'phone' => $phone ?: null,
            'address' => $address ?: null,
            'city' => $this->cleanIncomingText($data['shipping_city']) ?: null,
            'province' => $this->cleanIncomingText($data['shipping_province']) ?: null,
        ]);
    }

    private function fillCustomer(Customer $customer, string $name, string $phone, string $address, array $data): void
    {
        $updates = [];
        foreach ([
            'name' => $name,
            'phone' => $phone,
            'address' => $address,
            'city' => $this->cleanIncomingText($data['shipping_city']),
            'province' => $this->cleanIncomingText($data['shipping_province']),
        ] as $field => $value) {
            if ($value !== '' && (blank($customer->{$field}) || $this->isMaskedValue($customer->{$field}))) {
                $updates[$field] = $value;
            }
        }
        if ($updates) {
            $customer->update($updates);
        }
    }

    private function headerMap(array $row): array
    {
        $map = [];
        foreach ($row as $column => $header) {
            $key = $this->header((string) $header);
            if ($key !== '') {
                $map[$key] = $column;
            }
        }
        return $map;
    }

    private function value(array $row, array $headers, array $names): mixed
    {
        foreach ($names as $name) {
            $key = $this->header($name);
            if (isset($headers[$key])) {
                $value = $row[$headers[$key]] ?? null;
                if ($value !== null && $value !== '') {
                    return $value;
                }
            }
        }
        return null;
    }

    private function header(string $value): string
    {
        return mb_strtolower(trim((string) preg_replace('/\s+/', ' ', $value)));
    }

    private function text(mixed $value): string
    {
        return trim((string) ($value ?? ''));
    }

    private function firstValue(?string $current, string $new): ?string
    {
        $current = $this->cleanIncomingText($current);
        $new = $this->cleanIncomingText($new);

        return $current !== '' ? $current : ($new !== '' ? $new : null);
    }

    /**
     * Nilai sensor/placeholder dari export tidak boleh menghapus data yang
     * sudah lengkap. Nilai asli dari import berikutnya tetap diterima.
     */
    private function cleanIncomingText(mixed $value): string
    {
        $value = $this->text($value);
        if ($value === '' || $value === '-') {
            return '';
        }

        if (str_contains($value, '*') || str_contains($value, '•') || str_contains($value, '…')) {
            return '';
        }

        if (preg_match('/^(?:x{2,}|X{2,}|_+)$/u', $value)) {
            return '';
        }

        return $value;
    }

    private function isMaskedValue(mixed $value): bool
    {
        return $this->cleanIncomingText($value) === '' && $this->text($value) !== '';
    }

    private function sparseOrderUpdatePayload(MarketplaceOrder $order, array $payload): array
    {
        foreach ([
            'buyer_username', 'buyer_name', 'buyer_phone',
            'shipping_address', 'shipping_city', 'shipping_province',
            'shipping_courier_code', 'shipping_awb_no', 'payment_method',
        ] as $field) {
            if (! $this->hasMeaningfulValue($payload[$field] ?? null)) {
                unset($payload[$field]);
            }
        }

        // Status hanya berubah jika file memang membawa status yang valid.
        if (! $this->hasMeaningfulValue($payload['order_status'] ?? null)) {
            unset($payload['status'], $payload['order_status']);
        }

        if (empty($payload['order_date'])) {
            unset($payload['order_date']);
        }
        if (empty($payload['ordered_at'])) {
            unset($payload['ordered_at']);
        }
        if (empty($payload['paid_at'])) {
            // Jangan mengubah order yang sudah paid menjadi unpaid hanya karena
            // export berikutnya tidak menampilkan waktu pembayaran.
            unset($payload['paid_at'], $payload['payment_date'], $payload['payment_status']);
        }
        if (empty($payload['completed_at'])) {
            unset($payload['completed_at']);
        }
        if (empty($payload['customer_id'])) {
            unset($payload['customer_id']);
        }

        // Angka nol dari field tersensor/kosong tidak boleh menghapus nominal
        // yang sudah tersimpan. Nilai positif tetap selalu boleh memperbarui.
        foreach (['subtotal_items', 'shipping_fee_customer', 'total_paid_customer', 'total_amount'] as $field) {
            if ((float) ($payload[$field] ?? 0) <= 0 && (float) ($order->{$field} ?? 0) > 0) {
                unset($payload[$field]);
            }
        }

        return $payload;
    }

    private function hasMeaningfulValue(mixed $value): bool
    {
        return $this->cleanIncomingText($value) !== '';
    }

    private function mergeImportedMetadata(MarketplaceOrder $order, array $payload): array
    {
        $payload['raw_json'] = array_merge(
            (array) $order->raw_json,
            array_filter((array) ($payload['raw_json'] ?? []), fn ($value) => $value !== null && $value !== '')
        );
        $payload['meta'] = array_merge(
            (array) $order->meta,
            array_filter((array) ($payload['meta'] ?? []), fn ($value) => $value !== null && $value !== '')
        );

        return $payload;
    }

    private function number(mixed $value): float
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_numeric($value)) {
            return (float) $value;
        }
        $value = str_replace(',', '.', preg_replace('/[^0-9,\.\-]/', '', (string) $value));
        return is_numeric($value) ? (float) $value : 0;
    }

    private function money(mixed $value): int
    {
        if ($value === null || $value === '') {
            return 0;
        }
        if (is_int($value) || is_float($value)) {
            return (int) round((float) $value);
        }

        $value = trim((string) $value);
        $clean = preg_replace('/[^0-9,\.\-]/', '', $value);
        if ($clean === '' || $clean === '-') {
            return 0;
        }

        if (str_contains($clean, ',') && str_contains($clean, '.')) {
            if (strrpos($clean, ',') > strrpos($clean, '.')) {
                $clean = str_replace('.', '', $clean);
                $clean = str_replace(',', '.', $clean);
            } else {
                $clean = str_replace(',', '', $clean);
            }
        } elseif (str_contains($clean, ',')) {
            $clean = preg_match('/^-?\d{1,3}(?:,\d{3})+$/', $clean)
                ? str_replace(',', '', $clean)
                : str_replace(',', '.', $clean);
        } elseif (str_contains($clean, '.') && preg_match('/^-?\d{1,3}(?:\.\d{3})+$/', $clean)) {
            $clean = str_replace('.', '', $clean);
        }

        $number = (float) $clean;
        return (int) round($number);
    }

    private function date(mixed $value): ?string
    {
        if ($value === null || $value === '') {
            return null;
        }
        try {
            if (is_numeric($value) && (float) $value > 1) {
                return Carbon::instance(ExcelDate::excelToDateTimeObject((float) $value))->toDateTimeString();
            }
            return Carbon::parse((string) $value)->toDateTimeString();
        } catch (\Throwable) {
            return null;
        }
    }

    private function normalizePhone(?string $phone): string
    {
        $phone = preg_replace('/\D+/', '', (string) $phone);
        if ($phone === '') {
            return '';
        }
        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }
        return $phone;
    }

    private function normalizeStatus(string $status): string
    {
        $status = mb_strtolower(trim($status));
        if (str_contains($status, 'batal') || str_contains($status, 'kembali')) return 'cancelled';
        if (str_contains($status, 'selesai')) return 'completed';
        if (str_contains($status, 'dikirim') || str_contains($status, 'kirim')) return 'shipped';
        if (str_contains($status, 'diproses') || str_contains($status, 'siap')) return 'packed';
        return 'new';
    }

    private function isUniqueConstraintError(QueryException $exception): bool
    {
        $message = mb_strtolower($exception->getMessage());
        return str_contains($message, 'unique')
            || str_contains($message, 'duplicate')
            || str_contains($message, 'constraint failed');
    }

}
