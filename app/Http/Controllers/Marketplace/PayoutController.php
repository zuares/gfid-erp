<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\PayoutService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class PayoutController extends Controller
{
    public function __construct(
        protected PayoutService $payout,
    ) {}

    public function index(): \Illuminate\View\View
    {
        return view('marketplace.payout');
    }

    public function info(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'cursor' => ['nullable', 'string', 'max:1000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$from, $to] = $this->window($data);
        if ($from->greaterThan($to)) {
            return response()->json(['success' => false, 'message' => 'Tanggal mulai tidak boleh melewati tanggal akhir.'], 422);
        }
        if ($from->diffInSeconds($to) >= 15 * 86400) {
            return response()->json(['success' => false, 'message' => 'Rentang tanggal maksimal 15 hari sesuai batas endpoint wallet payout Shopee.'], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'source' => 'shopee_live',
                'data' => $this->payout->fetchInfo($store, $from, $to, (string) ($data['cursor'] ?? ''), (int) ($data['page_size'] ?? 100)),
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'payout info', $store);
        } catch (\Throwable $e) {
            Log::error('[payout] Gagal mengambil info', ['store_id' => $store->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Gagal mengambil daftar payout dari Shopee.'], 502);
        }
    }

    public function detail(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'page_no' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$from, $to] = $this->window($data);
        if ($from->greaterThan($to)) {
            return response()->json(['success' => false, 'message' => 'Tanggal mulai tidak boleh melewati tanggal akhir.'], 422);
        }
        if ($from->diffInSeconds($to) >= 15 * 86400) {
            return response()->json(['success' => false, 'message' => 'Rentang tanggal maksimal 15 hari sesuai batas endpoint wallet payout Shopee.'], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'source' => 'shopee_live',
                'data' => $this->payout->fetchDetail($store, $from, $to, (int) ($data['page_no'] ?? 1), (int) ($data['page_size'] ?? 100)),
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'payout detail', $store);
        } catch (\Throwable $e) {
            Log::error('[payout] Gagal mengambil detail', ['store_id' => $store->id, 'exception' => $e]);
            return response()->json(['success' => false, 'message' => 'Gagal mengambil detail payout dari Shopee.'], 502);
        }
    }

    public function records(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'cursor' => ['nullable', 'string', 'max:1000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$from, $to] = $this->window($data);
        if ($from->greaterThan($to)) {
            return response()->json(['success' => false, 'message' => 'Tanggal mulai tidak boleh melewati tanggal akhir.'], 422);
        }
        if ($from->diffInSeconds($to) >= 15 * 86400) {
            return response()->json(['success' => false, 'message' => 'Rentang tanggal maksimal 15 hari sesuai batas endpoint wallet payout Shopee.'], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'source' => 'database',
                'data' => $this->payout->storedInfo(
                    $store,
                    $from,
                    $to,
                    (string) ($data['cursor'] ?? ''),
                    (int) ($data['page_size'] ?? 100),
                ),
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'payout records', $store);
        } catch (\Throwable $e) {
            Log::error('[payout] Gagal membaca payout tersimpan', ['store_id' => $store->id, 'exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Gagal membaca data payout tersimpan.'], 500);
        }
    }

    public function sync(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$from, $to] = $this->window($data);
        if ($from->greaterThan($to)) {
            return response()->json(['success' => false, 'message' => 'Tanggal mulai tidak boleh melewati tanggal akhir.'], 422);
        }
        if ($from->diffInSeconds($to) >= 15 * 86400) {
            return response()->json(['success' => false, 'message' => 'Rentang tanggal maksimal 15 hari sesuai batas endpoint wallet payout Shopee.'], 422);
        }

        try {
            $result = $this->payout->syncWallet(
                $store,
                $from,
                $to,
                (int) ($data['page_size'] ?? 100),
            );

            if (($result['status'] ?? null) !== 'synced') {
                return response()->json([
                    'success' => false,
                    'message' => $result['message'] ?: 'Sinkronisasi payout belum selesai.',
                    'data' => $result,
                ], 502);
            }

            return response()->json(['success' => true, 'source' => 'database', 'data' => $result]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'payout sync', $store);
        } catch (\Throwable $e) {
            Log::error('[payout] Gagal menyinkronkan payout', ['store_id' => $store->id, 'exception' => $e]);

            return response()->json(['success' => false, 'message' => 'Gagal menyinkronkan payout dari Shopee.'], 502);
        }
    }

    /** @return array{0:Carbon,1:Carbon} */
    private function window(array $data): array
    {
        return [
            Carbon::createFromFormat('Y-m-d', $data['date_from'], config('app.timezone'))->startOfDay(),
            Carbon::createFromFormat('Y-m-d', $data['date_to'], config('app.timezone'))->endOfDay(),
        ];
    }

    private function apiError(RuntimeException $exception, string $operation, Store $store): JsonResponse
    {
        Log::warning("[payout] Gagal mengambil {$operation}", ['store_id' => $store->id, 'message' => $exception->getMessage()]);
        return response()->json(['success' => false, 'message' => $exception->getMessage()], 502);
    }
}
