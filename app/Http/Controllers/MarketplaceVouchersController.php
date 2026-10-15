<?php

namespace App\Http\Controllers;

use App\Models\MarketplaceVoucher;
use App\Models\MarketplaceVoucherAutomation;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceApiGateway;
use App\Services\Marketplace\MarketplaceVoucherAutomationService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class MarketplaceVouchersController extends Controller
{
    public function __construct(
        protected MarketplaceApiGateway $gateway,
        protected MarketplaceVoucherAutomationService $automationService,
    ) {}

    public function index(Request $request): JsonResponse
    {
        $data = $request->validate([
            'store_id' => ['required', 'integer', 'exists:stores,id'],
            'status' => ['nullable', 'string', 'in:all,ongoing,upcoming,expired'],
            'page_no' => ['nullable', 'integer', 'min:1', 'max:5000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $store = $this->resolveStore((int) $data['store_id']);
        $status = $data['status'] ?? 'all';
        $response = $this->gateway->getVoucherList(
            $store,
            $status,
            (int) ($data['page_no'] ?? 1),
            (int) ($data['page_size'] ?? 100)
        );

        if ($this->hasError($response)) {
            return $this->errorResponse($response, $store);
        }

        $rows = collect(data_get($response, 'response.voucher_list', []))
            ->filter(fn ($voucher) => is_array($voucher))
            ->map(fn (array $voucher) => $this->persist($store, $this->normalize($voucher), $response))
            ->values();

        return response()->json([
            'success' => true,
            'store' => $this->storePayload($store),
            'status' => $status,
            'vouchers' => $rows,
            'more' => (bool) data_get($response, 'response.more', false),
            'raw' => $response,
        ]);
    }

    public function show(Store $store, int $voucherId): JsonResponse
    {
        $store = $this->resolveStore($store->id);
        $response = $this->gateway->getVoucher($store, $voucherId);

        if ($this->hasError($response)) {
            return $this->errorResponse($response, $store);
        }

        $voucher = $this->normalize(data_get($response, 'response', []));
        $voucher['voucher_id'] = $voucher['voucher_id'] ?: $voucherId;
        $record = $this->persist($store, $voucher, $response);
        $record['raw'] = $response;

        return response()->json([
            'success' => true,
            'store' => $this->storePayload($store),
            'voucher' => $record,
            'raw' => $response,
        ]);
    }

    public function store(Request $request): JsonResponse
    {
        $data = $this->validatePayload($request, true);
        $store = $this->resolveStore((int) $data['store_id']);
        $payload = $this->cleanPayload($data);
        $response = $this->gateway->addVoucher($store, $payload);

        if ($this->hasError($response)) {
            return $this->errorResponse($response, $store);
        }

        $voucherId = (int) data_get($response, 'response.voucher_id', 0);
        if ($voucherId > 0) {
            $this->persist($store, $this->normalize($payload + ['voucher_id' => $voucherId]), $response, $payload);
        }

        return response()->json([
            'success' => true,
            'message' => 'Voucher berhasil dibuat di Shopee.',
            'store' => $this->storePayload($store),
            'voucher_id' => $voucherId,
            'raw' => $response,
        ]);
    }

    public function update(Request $request, Store $store, int $voucherId): JsonResponse
    {
        $store = $this->resolveStore($store->id);
        $data = $this->validatePayload($request, false);
        $payload = $this->cleanPayload($data);
        unset($payload['store_id']);

        if ($payload === []) {
            return response()->json(['success' => false, 'message' => 'Tidak ada perubahan voucher yang dikirim.'], 422);
        }

        $response = $this->gateway->updateVoucher($store, $voucherId, $payload);
        if ($this->hasError($response)) {
            return $this->errorResponse($response, $store);
        }

        $this->persist($store, $this->normalize($payload + ['voucher_id' => $voucherId]), $response, $payload);

        return response()->json(['success' => true, 'message' => 'Voucher berhasil diperbarui.', 'raw' => $response]);
    }

    public function end(Store $store, int $voucherId): JsonResponse
    {
        return $this->mutate($store, $voucherId, 'endVoucher', 'Voucher berhasil diakhiri.', 'ended');
    }

    public function destroy(Store $store, int $voucherId): JsonResponse
    {
        return $this->mutate($store, $voucherId, 'deleteVoucher', 'Voucher berhasil dihapus.', 'deleted');
    }

    public function automations(Request $request): JsonResponse
    {
        $data = $request->validate(['store_id' => ['required', 'integer', 'exists:stores,id']]);
        $store = $this->resolveStore((int) $data['store_id']);

        return response()->json([
            'success' => true,
            'automations' => MarketplaceVoucherAutomation::query()
                ->where('store_id', $store->id)
                ->orderByDesc('is_active')
                ->orderBy('next_run_at')
                ->get(),
        ]);
    }

    public function storeAutomation(Request $request): JsonResponse
    {
        $data = $this->validateAutomation($request, true);
        $store = $this->resolveStore((int) $data['store_id']);
        $automation = MarketplaceVoucherAutomation::create($data + [
            'store_id' => $store->id,
            'display_channel_list' => $data['display_channel_list'] ?? [1],
            'is_active' => $data['is_active'] ?? true,
        ]);

        return response()->json(['success' => true, 'automation' => $automation]);
    }

    public function updateAutomation(Request $request, int $automationId): JsonResponse
    {
        $automation = MarketplaceVoucherAutomation::query()->findOrFail($automationId);
        $this->resolveStore($automation->store_id);
        $data = $this->validateAutomation($request, false, $automation);
        $automation->update($data);

        return response()->json(['success' => true, 'automation' => $automation->refresh()]);
    }

    public function destroyAutomation(Request $request, int $automationId): JsonResponse
    {
        $automation = MarketplaceVoucherAutomation::query()->findOrFail($automationId);
        $this->resolveStore($automation->store_id);
        $automation->delete();

        return response()->json(['success' => true, 'message' => 'Automation voucher dihapus.']);
    }

    public function runAutomation(Request $request, int $automationId): JsonResponse
    {
        $automation = MarketplaceVoucherAutomation::query()->findOrFail($automationId);
        $this->resolveStore($automation->store_id);
        $result = $this->automationService->runOne($automation);

        return response()->json($result, ($result['success'] ?? false) ? 200 : 422);
    }

    private function mutate(Store $store, int $voucherId, string $method, string $message, string $status): JsonResponse
    {
        $store = $this->resolveStore($store->id);
        $response = $this->gateway->{$method}($store, $voucherId);
        if ($this->hasError($response)) {
            return $this->errorResponse($response, $store);
        }

        MarketplaceVoucher::query()->updateOrCreate(
            ['store_id' => $store->id, 'voucher_id' => $voucherId],
            ['channel_code' => $store->channel?->code, 'voucher_status' => $status, 'raw_json' => $response, 'sync_status' => 'synced']
        );

        return response()->json(['success' => true, 'message' => $message, 'raw' => $response]);
    }

    private function validatePayload(Request $request, bool $creating): array
    {
        $rules = [
            'store_id' => [$creating ? 'required' : 'nullable', 'integer', 'exists:stores,id'],
            'voucher_name' => ['nullable', 'string', 'max:255'],
            'voucher_code' => ['nullable', 'string', 'max:50', 'regex:/^[A-Za-z0-9_-]+$/'],
            'start_time' => ['nullable', 'integer', 'min:1'],
            'end_time' => ['nullable', 'integer', 'min:1'],
            'voucher_type' => ['nullable', 'integer', 'in:1,2'],
            'reward_type' => ['nullable', 'integer', 'in:1,2,3'],
            'usage_quantity' => ['nullable', 'integer', 'min:1'],
            'min_basket_price' => ['nullable', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'display_channel_list' => ['nullable', 'array'],
            'display_channel_list.*' => ['integer', 'in:1,2,3,4'],
            'item_id_list' => ['nullable', 'array'],
            'item_id_list.*' => ['integer', 'min:1'],
            'display_start_time' => ['nullable', 'integer', 'min:1'],
        ];

        if ($creating) {
            $rules['voucher_name'][] = 'required';
            $rules['voucher_code'][] = 'required';
            $rules['start_time'][] = 'required';
            $rules['end_time'][] = 'required';
            $rules['voucher_type'][] = 'required';
            $rules['reward_type'][] = 'required';
            $rules['usage_quantity'][] = 'required';
        }

        $data = $request->validate($rules);
        if (($data['start_time'] ?? 0) && ($data['end_time'] ?? 0) && $data['end_time'] < $data['start_time']) {
            abort(response()->json(['message' => 'Waktu selesai harus setelah waktu mulai.'], 422));
        }

        return $data;
    }

    private function validateAutomation(Request $request, bool $creating, ?MarketplaceVoucherAutomation $existing = null): array
    {
        $rules = [
            'store_id' => [$creating ? 'required' : 'nullable', 'integer', 'exists:stores,id'],
            'name_prefix' => [$creating ? 'required' : 'sometimes', 'string', 'max:100'],
            'code_prefix' => [$creating ? 'required' : 'sometimes', 'string', 'max:20', 'regex:/^[A-Za-z0-9]+$/'],
            'interval_hours' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:168'],
            'duration_hours' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:168'],
            'usage_quantity' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1', 'max:100000'],
            'voucher_type' => ['sometimes', 'integer', 'in:1,2'],
            'reward_type' => ['sometimes', 'integer', 'in:1,2,3'],
            'min_basket_price' => ['sometimes', 'numeric', 'min:0'],
            'discount_amount' => ['nullable', 'numeric', 'min:0'],
            'percentage' => ['nullable', 'numeric', 'min:0', 'max:100'],
            'max_price' => ['nullable', 'numeric', 'min:0'],
            'display_channel_list' => ['nullable', 'array'],
            'display_channel_list.*' => ['integer', 'in:1,2,3,4'],
            'item_id_list' => ['nullable', 'array'],
            'item_id_list.*' => ['integer', 'min:1'],
            'next_run_at' => [$creating ? 'required' : 'sometimes', 'integer', 'min:1'],
            'is_active' => ['sometimes', 'boolean'],
        ];

        $data = $request->validate($rules);
        $interval = (int) ($data['interval_hours'] ?? $existing?->interval_hours ?? 0);
        $duration = (int) ($data['duration_hours'] ?? $existing?->duration_hours ?? 0);
        if ($interval < $duration) {
            abort(response()->json(['message' => 'Interval harus sama atau lebih besar dari durasi voucher agar jadwal tidak overlap.'], 422));
        }

        if (! $creating && array_key_exists('store_id', $data)) {
            unset($data['store_id']);
        }

        return $data;
    }

    private function cleanPayload(array $data): array
    {
        $fields = [
            'voucher_name', 'voucher_code', 'start_time', 'end_time', 'voucher_type', 'reward_type',
            'usage_quantity', 'min_basket_price', 'discount_amount', 'percentage', 'max_price',
            'display_channel_list', 'item_id_list', 'display_start_time',
        ];

        return collect($data)->only($fields)->filter(fn ($value) => $value !== null && $value !== '')->all();
    }

    private function normalize(array $voucher): array
    {
        $start = (int) ($voucher['start_time'] ?? 0);
        $end = (int) ($voucher['end_time'] ?? 0);
        $status = strtolower((string) ($voucher['voucher_status'] ?? $voucher['status'] ?? ''));
        if (! in_array($status, ['ongoing', 'upcoming', 'expired', 'ended'], true)) {
            $now = now()->timestamp;
            $status = $start > $now ? 'upcoming' : ($end && $end < $now ? 'expired' : 'ongoing');
        }

        return [
            'voucher_id' => (int) ($voucher['voucher_id'] ?? 0),
            'voucher_code' => (string) ($voucher['voucher_code'] ?? ''),
            'voucher_name' => (string) ($voucher['voucher_name'] ?? ''),
            'voucher_status' => $status,
            'voucher_type' => isset($voucher['voucher_type']) ? (int) $voucher['voucher_type'] : null,
            'reward_type' => isset($voucher['reward_type']) ? (int) $voucher['reward_type'] : null,
            'usage_quantity' => isset($voucher['usage_quantity']) ? (int) $voucher['usage_quantity'] : null,
            'current_usage' => isset($voucher['current_usage']) ? (int) $voucher['current_usage'] : null,
            'start_time' => $start ?: null,
            'end_time' => $end ?: null,
            'display_start_time' => isset($voucher['display_start_time']) ? (int) $voucher['display_start_time'] : null,
            'min_basket_price' => isset($voucher['min_basket_price']) ? (float) $voucher['min_basket_price'] : null,
            'discount_amount' => isset($voucher['discount_amount']) ? (float) $voucher['discount_amount'] : null,
            'percentage' => isset($voucher['percentage']) ? (float) $voucher['percentage'] : null,
            'max_price' => isset($voucher['max_price']) ? (float) $voucher['max_price'] : null,
            'display_channel_list' => $voucher['display_channel_list'] ?? [],
            'item_id_list' => $voucher['item_id_list'] ?? [],
        ];
    }

    private function persist(Store $store, array $voucher, array $raw = [], array $payload = []): array
    {
        if (($voucher['voucher_id'] ?? 0) <= 0) {
            return $voucher;
        }

        $record = MarketplaceVoucher::query()->updateOrCreate(
            ['store_id' => $store->id, 'voucher_id' => $voucher['voucher_id']],
            array_merge($voucher, [
                'channel_code' => $store->channel?->code,
                'request_payload' => $payload ?: null,
                'raw_json' => $raw ?: null,
                'detail_cache_json' => $raw ? data_get($raw, 'response') : null,
                'detail_cached_at' => $raw ? now() : null,
                'sync_status' => 'synced',
                'sync_error' => null,
            ])
        );

        return $record->toArray();
    }

    private function resolveStore(int $storeId): Store
    {
        $store = Store::with('channel')->findOrFail($storeId);
        abort_unless($store->is_active && in_array(strtolower((string) $store->channel?->code), ['shopee', 'shp'], true), 404, 'Toko voucher tidak ditemukan.');
        return $store;
    }

    private function storePayload(Store $store): array
    {
        return ['id' => $store->id, 'name' => $store->name, 'code' => $store->code, 'channel' => $store->channel?->code];
    }

    private function hasError(array $response): bool
    {
        return ! empty($response['error']);
    }

    private function errorResponse(array $response, Store $store): JsonResponse
    {
        return response()->json([
            'success' => false,
            'message' => $response['message'] ?? $response['error'] ?? 'Shopee menolak permintaan voucher.',
            'store' => $this->storePayload($store),
            'raw' => $response,
        ], 422);
    }
}
