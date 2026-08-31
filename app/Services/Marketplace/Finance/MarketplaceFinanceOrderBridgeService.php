<?php

namespace App\Services\Marketplace\Finance;

use App\Models\MarketplaceFinancialTransaction;
use App\Models\MarketplaceOrder;
use App\Models\SalesInvoice;
use App\Models\Shipment;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

class MarketplaceFinanceOrderBridgeService
{
    public function syncFromOrder(MarketplaceOrder $order): MarketplaceFinancialTransaction
    {
        $order->loadMissing('store.channel');

        $store = $order->store;
        $channel = strtolower(trim((string) ($store?->channel?->code ?? '')));
        $orderSn = trim((string) ($order->channel_order_id ?: $order->external_order_id));

        if (! $store || $channel === '') {
            throw new InvalidArgumentException('Marketplace order belum memiliki store/channel yang valid.');
        }

        if ($orderSn === '') {
            throw new InvalidArgumentException('Marketplace order belum memiliki order SN.');
        }

        $identity = [
            'store_id' => $store->id,
            'channel' => $channel,
            'order_sn' => $orderSn,
        ];

        $salesInvoice = $this->findSalesInvoice($order, $channel, $orderSn);
        $shipment = $salesInvoice
            ? $this->findShipment($order, $salesInvoice->id)
            : null;

        $values = [
            'marketplace_order_id' => $order->id,
            'currency' => $this->currency($order->currency),
            'gross_amount' => $this->grossAmount($order),
        ];

        // Missing operational records are a valid bridge state. If a later
        // retry finds them, fill the FK; never clear a link found previously.
        if ($salesInvoice) {
            $values['sales_invoice_id'] = $salesInvoice->id;
        }
        if ($shipment) {
            $values['shipment_id'] = $shipment->id;
        }

        return DB::transaction(function () use ($identity, $values): MarketplaceFinancialTransaction {
            // The database unique key is the final concurrency guard. Finance
            // fields (net/status/raw/journal refs) are intentionally omitted
            // from the update payload so bridge retries cannot reset them.
            return MarketplaceFinancialTransaction::updateOrCreate($identity, $values)->fresh();
        });
    }

    private function findSalesInvoice(MarketplaceOrder $order, string $channel, string $orderSn): ?SalesInvoice
    {
        return SalesInvoice::query()
            ->where('store_id', $order->store_id)
            ->where('channel', $channel)
            ->where('channel_order_no', $orderSn)
            ->first();
    }

    private function findShipment(MarketplaceOrder $order, int $salesInvoiceId): ?Shipment
    {
        return Shipment::query()
            ->where('store_id', $order->store_id)
            ->where('sales_invoice_id', $salesInvoiceId)
            ->orderByDesc('id')
            ->first();
    }

    private function currency(?string $currency): string
    {
        $currency = strtoupper(trim((string) $currency));

        return $currency !== '' ? $currency : 'IDR';
    }

    private function grossAmount(MarketplaceOrder $order): string
    {
        // total_amount is the omnichannel value. Older rows may only have the
        // legacy total_paid_customer/subtotal_items fields populated.
        foreach (['total_amount', 'total_paid_customer', 'subtotal_items'] as $field) {
            $value = $order->{$field};
            if ($value !== null && (float) $value !== 0.0) {
                return number_format((float) $value, 2, '.', '');
            }
        }

        return '0.00';
    }
}
