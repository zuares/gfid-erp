<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\EscrowService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;

class EscrowController extends Controller
{
    public function __construct(
        protected EscrowService $escrow,
    ) {}

    public function index(): \Illuminate\View\View
    {
        return view('marketplace.escrow');
    }

    public function list(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'page_no' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $data['date_from'], config('app.timezone'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $data['date_to'], config('app.timezone'))->endOfDay();

        if ($from->greaterThan($to)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai tidak boleh melewati tanggal akhir.',
            ], 422);
        }

        // Kontrak Shopee membatasi release time window maksimal 15 x 24 jam.
        if ($from->diffInSeconds($to) >= 15 * 86400) {
            return response()->json([
                'success' => false,
                'message' => 'Rentang tanggal maksimal 15 hari sesuai batas endpoint Shopee.',
            ], 422);
        }

        try {
            $result = $this->escrow->fetchList(
                $store,
                $from,
                $to,
                (int) ($data['page_no'] ?? 1),
                (int) ($data['page_size'] ?? 100),
            );

            return response()->json([
                'success' => true,
                'source' => 'shopee_live',
                'data' => $result,
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'escrow list', $store);
        } catch (\Throwable $e) {
            Log::error('[escrow] Gagal mengambil list', ['store_id' => $store->id, 'exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar escrow dari Shopee.',
            ], 502);
        }
    }

    public function orders(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'page_no' => ['nullable', 'integer', 'min:1', 'max:10000'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $from = Carbon::createFromFormat('Y-m-d', $data['date_from'], config('app.timezone'))->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $data['date_to'], config('app.timezone'))->endOfDay();

        if ($from->greaterThan($to)) {
            return response()->json([
                'success' => false,
                'message' => 'Tanggal mulai tidak boleh melewati tanggal akhir.',
            ], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'source' => 'local_orders_from_webhook',
                'data' => $this->escrow->fetchLocalOrders(
                    $store,
                    $from,
                    $to,
                    (int) ($data['page_no'] ?? 1),
                    (int) ($data['page_size'] ?? 50),
                ),
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'order escrow', $store);
        } catch (\Throwable $e) {
            Log::error('[escrow] Gagal mengambil order lokal', ['store_id' => $store->id, 'exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil daftar order untuk escrow.',
            ], 502);
        }
    }

    public function detailBatch(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'order_sn_list' => ['required', 'array', 'min:1', 'max:50'],
            'order_sn_list.*' => ['required', 'string', 'max:64'],
        ]);

        try {
            return response()->json([
                'success' => true,
                'source' => 'shopee_live_batch',
                'data' => $this->escrow->detailBatch($store, $data['order_sn_list']),
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'escrow detail batch', $store);
        } catch (\Throwable $e) {
            Log::error('[escrow] Gagal mengambil batch detail', ['store_id' => $store->id, 'exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil batch detail escrow dari Shopee.',
            ], 502);
        }
    }

    public function detail(Request $request, Store $store): JsonResponse
    {
        $data = $request->validate([
            'order_sn' => ['required', 'string', 'max:64'],
        ]);

        try {
            return response()->json([
                'success' => true,
                'source' => 'shopee_live',
                'data' => $this->escrow->detail($store, trim($data['order_sn'])),
            ]);
        } catch (RuntimeException $e) {
            return $this->apiError($e, 'escrow detail', $store);
        } catch (\Throwable $e) {
            Log::error('[escrow] Gagal mengambil detail', ['store_id' => $store->id, 'exception' => $e]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal mengambil detail escrow dari Shopee.',
            ], 502);
        }
    }

    private function apiError(RuntimeException $exception, string $operation, Store $store): JsonResponse
    {
        Log::warning("[escrow] Gagal mengambil {$operation}", [
            'store_id' => $store->id,
            'message' => $exception->getMessage(),
        ]);

        return response()->json([
            'success' => false,
            'message' => $exception->getMessage(),
        ], 502);
    }
}
