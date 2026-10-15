<?php

namespace App\Services\Marketplace;

use App\Models\MarketplaceVoucher;
use App\Models\MarketplaceVoucherAutomation;
use App\Services\Marketplace\MarketplaceApiGateway;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Str;
use RuntimeException;
use Throwable;

class MarketplaceVoucherAutomationService
{
    public function __construct(protected MarketplaceApiGateway $gateway) {}

    public function runDue(?int $automationId = null): array
    {
        $query = MarketplaceVoucherAutomation::query()
            ->with('store.channel')
            ->where('is_active', true);

        if ($automationId) {
            $query->whereKey($automationId);
        } else {
            $query->where('next_run_at', '<=', now()->timestamp);
        }

        return $query->orderBy('next_run_at')->limit($automationId ? 1 : 50)->get()
            ->map(fn (MarketplaceVoucherAutomation $automation) => $this->runOne($automation))
            ->all();
    }

    public function runOne(MarketplaceVoucherAutomation $automation): array
    {
        $lock = Cache::lock('marketplace-voucher-automation:' . $automation->id, 120);

        if (! $lock->get()) {
            return ['success' => false, 'status' => 'locked', 'automation_id' => $automation->id];
        }

        try {
            $automation->refresh()->load('store.channel');
            if (! $automation->is_active) {
                return ['success' => false, 'status' => 'inactive', 'automation_id' => $automation->id];
            }
            if ($automation->next_run_at > now()->timestamp) {
                return ['success' => true, 'status' => 'not_due', 'automation_id' => $automation->id];
            }

            $store = $automation->store;
            $start = now()->timestamp;
            $end = $start + ($automation->duration_hours * 3600);
            $slotKey = $automation->id . ':' . $start;
            $code = $this->code($automation, $start);
            $payload = $this->payload($automation, $start, $end, $code);
            $response = $this->gateway->addVoucher($store, $payload);

            if (! empty($response['error'])) {
                throw new RuntimeException($response['message'] ?? $response['error'] ?? 'Shopee menolak pembuatan voucher.');
            }

            $voucherId = (int) data_get($response, 'response.voucher_id', 0);
            if ($voucherId <= 0) {
                throw new RuntimeException('Shopee tidak mengembalikan voucher_id.');
            }

            MarketplaceVoucher::query()->updateOrCreate(
                ['store_id' => $store->id, 'voucher_id' => $voucherId],
                [
                    'channel_code' => $store->channel?->code,
                    'voucher_code' => $payload['voucher_code'],
                    'voucher_name' => $payload['voucher_name'],
                    'voucher_status' => 'upcoming',
                    'voucher_type' => $payload['voucher_type'],
                    'reward_type' => $payload['reward_type'],
                    'usage_quantity' => $payload['usage_quantity'],
                    'start_time' => $start,
                    'end_time' => $end,
                    'min_basket_price' => $payload['min_basket_price'] ?? null,
                    'discount_amount' => $payload['discount_amount'] ?? null,
                    'percentage' => $payload['percentage'] ?? null,
                    'max_price' => $payload['max_price'] ?? null,
                    'display_channel_list' => $payload['display_channel_list'] ?? [],
                    'item_id_list' => $payload['item_id_list'] ?? [],
                    'request_payload' => $payload,
                    'raw_json' => $response,
                    'sync_status' => 'synced',
                    'sync_error' => null,
                ]
            );

            $automation->update([
                'next_run_at' => $start + ($automation->interval_hours * 3600),
                'last_run_at' => $start,
                'last_voucher_id' => $voucherId,
                'last_slot_key' => $slotKey,
                'run_count' => $automation->run_count + 1,
                'last_error' => null,
            ]);

            return ['success' => true, 'status' => 'created', 'automation_id' => $automation->id, 'voucher_id' => $voucherId, 'voucher_code' => $code];
        } catch (Throwable $e) {
            $automation->update([
                'next_run_at' => now()->addMinutes(15)->timestamp,
                'last_error' => Str::limit($e->getMessage(), 1000),
            ]);

            return ['success' => false, 'status' => 'failed', 'automation_id' => $automation->id, 'message' => $e->getMessage()];
        } finally {
            $lock->release();
        }
    }

    private function payload(MarketplaceVoucherAutomation $automation, int $start, int $end, string $code): array
    {
        $payload = [
            'voucher_name' => trim($automation->name_prefix) . ' ' . now()->format('d M Y H:i'),
            'voucher_code' => $code,
            'start_time' => $start,
            'end_time' => $end,
            'voucher_type' => $automation->voucher_type,
            'reward_type' => $automation->reward_type,
            'usage_quantity' => $automation->usage_quantity,
            'min_basket_price' => $automation->min_basket_price,
            'max_price' => $automation->max_price,
            'display_channel_list' => $automation->display_channel_list ?: [1],
            'item_id_list' => $automation->item_id_list ?: [],
        ];

        if ((int) $automation->reward_type === 1) {
            $payload['discount_amount'] = $automation->discount_amount;
        } else {
            $payload['percentage'] = $automation->percentage;
        }

        return collect($payload)->filter(fn ($value) => $value !== null && $value !== '' && !(is_array($value) && $value === []))->all();
    }

    private function code(MarketplaceVoucherAutomation $automation, int $start): string
    {
        $prefix = strtoupper((string) preg_replace('/[^A-Za-z0-9]/', '', $automation->code_prefix));
        return Str::limit($prefix . str_pad((string) $automation->id, 3, '0', STR_PAD_LEFT) . now()->setTimestamp($start)->format('ymdHi'), 50, '');
    }
}
