<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Jobs\SyncTikTokFinancialTransactionsJob;
use App\Models\Store;
use App\Services\Marketplace\TikTokShop\TikTokFinancialTransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class TikTokFinancialTransactionController extends Controller
{
    public function __construct(
        protected TikTokFinancialTransactionService $service,
    ) {}

    public function index(): \Illuminate\View\View
    {
        $stores = Store::query()
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['tiktok', 'ttk', 'tkt', 'tt']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        return view('marketplace.tiktok.financial-transactions', [
            'stores' => $stores,
            'transactionTypes' => $this->service->getTransactionTypes(),
            'dataSources' => $this->service->getDataSources(),
            'statuses' => ['PROCESSING', 'COMPLETED', 'SUCCESS', 'FAILED', 'REVERSED', 'SETTLED', 'UNKNOWN'],
        ]);
    }

    public function data(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'types' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'in:account,statements'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
            'page' => ['nullable', 'integer', 'min:1'],
        ]);

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $source = $validated['source'] ?? 'statements';
            $result = $this->service->storedTransactions($store, $from, $to, [
                'source' => $source,
                'types' => $validated['types'] ?? '',
                'status' => $validated['status'] ?? '',
                'page_size' => $validated['page_size'] ?? 100,
                'page' => $validated['page'] ?? 1,
            ]);

            return response()->json([
                'success' => true,
                'source' => 'tiktok_financial_transactions',
                'storage' => 'local_database',
                'data_source' => $source,
                'data' => $result,
            ]);
        } catch (RuntimeException $e) {
            Log::warning('[tiktok-financial-transactions] API gagal', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            $rateLimited = preg_match('/too many requests|rate[ _-]?limit/i', $e->getMessage()) === 1;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], str_contains($e->getMessage(), 'bukan channel') ? 422 : ($rateLimited ? 429 : 502));
        } catch (\Throwable $e) {
            Log::error('[tiktok-financial-transactions] Kesalahan internal', [
                'store_id' => $store->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil transaksi finansial TikTok.',
            ], 500);
        }
    }

    public function sync(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'source' => ['nullable', 'in:account,statements,all'],
        ]);

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        $store->loadMissing('channel');
        $channelCode = strtolower((string) ($store->channel?->code ?? ''));
        if (! in_array($channelCode, ['tiktok', 'ttk', 'tkt', 'tt'], true)) {
            return response()->json([
                'success' => false,
                'code' => 'NOT_TIKTOK_STORE',
                'message' => 'Toko yang dipilih bukan channel TikTok Shop.',
            ], 422);
        }

        if ($store->connection_status !== 'CONNECTED') {
            return response()->json([
                'success' => false,
                'code' => 'TIKTOK_AUTH_REQUIRED',
                'message' => 'Koneksi TikTok Shop belum aktif. Hubungkan ulang toko sebelum sinkronisasi.',
            ], 401);
        }

        $store->loadMissing('channel');
        $source = $validated['source'] ?? 'all';
        $activeKey = SyncTikTokFinancialTransactionsJob::activeKey((int) $store->id);
        $progressKey = SyncTikTokFinancialTransactionsJob::progressKey((int) $store->id);
        if (! Cache::add($activeKey, [
            'store_id' => $store->id,
            'store_name' => $store->name,
            'source' => $source,
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'queued_at' => now()->toISOString(),
        ], 3600)) {
            return response()->json([
                'success' => false,
                'code' => 'TIKTOK_FINANCIAL_SYNC_BUSY',
                'message' => "Sinkronisasi Finance TikTok untuk toko {$store->name} sedang berjalan atau masih antre.",
                'progress' => Cache::get($progressKey),
            ], 429);
        }

        Cache::put($progressKey, [
            'status' => 'queued',
            'phase' => 'queued',
            'percent' => 2,
            'label' => "Sinkronisasi Finance TikTok {$store->name} masuk antrean…",
            'store_id' => $store->id,
            'store_name' => $store->name,
            'source' => $source,
            'date_from' => $from->toDateString(),
            'date_to' => $to->toDateString(),
            'updated_at' => now()->toISOString(),
        ], 3600);

        try {
            SyncTikTokFinancialTransactionsJob::dispatch(
                (int) $store->id,
                $from->toDateString(),
                $to->toDateString(),
                $source,
                'manual',
            );
        } catch (\Throwable $e) {
            Cache::forget($activeKey);
            Cache::put($progressKey, [
                'status' => 'error',
                'phase' => 'dispatch_failed',
                'percent' => 100,
                'label' => 'Sinkronisasi gagal dimasukkan ke antrean.',
                'store_id' => $store->id,
                'store_name' => $store->name,
                'source' => $source,
                'updated_at' => now()->toISOString(),
            ], 3600);
            Log::error('[tiktok-financial-transactions] Dispatch sync gagal', [
                'store_id' => $store->id,
                'error' => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'code' => 'TIKTOK_FINANCIAL_SYNC_QUEUE_ERROR',
                'message' => 'Sinkronisasi gagal dimasukkan ke antrean. Periksa queue worker lalu coba lagi.',
            ], 503);
        }

        return response()->json([
            'success' => true,
            'status' => 'queued',
            'storage' => 'local_database',
            'message' => 'Sinkronisasi Finance TikTok masuk antrean.',
            'progress' => Cache::get($progressKey),
        ]);
    }

    public function syncStatus(Store $store): JsonResponse
    {
        return response()->json(array_merge([
            'status' => 'idle',
            'store_id' => $store->id,
            'store_name' => $store->name,
        ], Cache::get(SyncTikTokFinancialTransactionsJob::progressKey((int) $store->id), [])));
    }

    public function orderStatement(Store $store, string $orderId): JsonResponse
    {
        $orderId = trim($orderId);
        if ($orderId === '' || mb_strlen($orderId) > 64) {
            return response()->json([
                'success' => false,
                'message' => 'Order ID TikTok wajib diisi dan maksimal 64 karakter.',
            ], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'source' => 'tiktok_live',
                'endpoint' => 'get_order_statement_transactions',
                'data' => $this->service->fetchOrderStatementTransactions($store, $orderId),
            ]);
        } catch (RuntimeException $e) {
            Log::warning('[tiktok-order-statement] API gagal', [
                'store_id' => $store->id,
                'order_id' => $orderId,
                'error' => $e->getMessage(),
            ]);

            $rateLimited = preg_match('/too many requests|rate[ _-]?limit/i', $e->getMessage()) === 1;

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], str_contains($e->getMessage(), 'bukan channel') ? 422 : ($rateLimited ? 429 : 502));
        } catch (\Throwable $e) {
            Log::error('[tiktok-order-statement] Kesalahan internal', [
                'store_id' => $store->id,
                'order_id' => $orderId,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil settlement order TikTok.',
            ], 500);
        }
    }

    public function export(Request $request, Store $store): StreamedResponse|JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'types' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'in:account,statements'],
            'page_size' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $source = $validated['source'] ?? 'statements';
            $result = $this->service->storedTransactions($store, $from, $to, [
                'source' => $source,
                'types' => $validated['types'] ?? '',
                'status' => $validated['status'] ?? '',
                'page_size' => $validated['page_size'] ?? 100,
                'all' => true,
            ]);
            $filename = sprintf(
                'tiktok_%s_%s_%s_%s.csv',
                $source,
                preg_replace('/[^a-zA-Z0-9_-]/', '_', $store->name),
                $from->format('Ymd'),
                $to->format('Ymd'),
            );

            return response()->streamDownload(function () use ($result): void {
                $handle = fopen('php://output', 'w');
                fputs($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['Sumber', 'Waktu', 'ID Transaksi', 'Statement ID', 'Order ID', 'Tipe', 'Arah Dana', 'Nominal', 'Mata Uang', 'Status', 'Keterangan']);
                foreach ($result['items'] as $item) {
                    fputcsv($handle, [
                        $item['source'],
                        $item['date_formatted'],
                        $item['transaction_id'],
                        $item['statement_id'] ?? '',
                        $item['order_id'] ?? '',
                        $item['transaction_type_label'],
                        $item['money_flow'],
                        $item['amount'],
                        $item['currency'],
                        $item['status'],
                        $item['reason'] ?? '',
                    ]);
                }
                fclose($handle);
            }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
        } catch (RuntimeException $e) {
            return response()->json(['success' => false, 'message' => $e->getMessage()], 502);
        }
    }

    /** @return array{0:Carbon,1:Carbon,2:?string} */
    private function parseDateWindow(string $fromValue, string $toValue): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $from = Carbon::createFromFormat('Y-m-d', $fromValue, $timezone)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $toValue, $timezone)->endOfDay();

        if ($from->greaterThan($to)) {
            return [$from, $to, 'Tanggal mulai tidak boleh melewati tanggal akhir.'];
        }
        if ($from->diffInDays($to) > 60) {
            return [$from, $to, 'Rentang tanggal maksimal 60 hari.'];
        }

        return [$from, $to, null];
    }
}
