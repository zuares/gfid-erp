<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Http\Requests\Marketplace\TikTokFinancialTransactionRequest;
use App\Http\Requests\Marketplace\TikTokFinancialTransactionSyncRequest;
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
        return $this->renderIndex(false);
    }

    public function wallet(): \Illuminate\View\View
    {
        return $this->renderIndex(true);
    }

    private function renderIndex(bool $walletMode): \Illuminate\View\View
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
            'walletMode' => $walletMode,
        ]);
    }

    public function data(TikTokFinancialTransactionRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $source = $request->routeIs('marketplace.api.tiktok-wallet-transactions')
                ? 'account'
                : ($validated['source'] ?? 'statements');
            $result = $this->service->storedTransactions($store, $from, $to, [
                'source' => $source,
                'search' => $validated['search'] ?? '',
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
            [$message, $status, $code] = $this->safeFailure($e, 'Transaksi TikTok belum dapat dibaca dari database lokal.');
            Log::warning('[tiktok-financial-transactions] API gagal', [
                'store_id' => $store->id,
                'code' => $code,
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'code' => $code,
                'message' => $message,
            ], $status);
        } catch (\Throwable $e) {
            Log::error('[tiktok-financial-transactions] Kesalahan internal', [
                'store_id' => $store->id,
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil transaksi finansial TikTok.',
            ], 500);
        }
    }

    public function reconciliation(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
        ]);

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            return response()->json([
                'success' => true,
                'data' => $this->service->cashOutReconciliation($store, $from, $to),
            ]);
        } catch (RuntimeException $e) {
            [$message, $status, $code] = $this->safeFailure($e, 'Rekonsiliasi kas TikTok belum dapat dihitung.');

            return response()->json(['success' => false, 'code' => $code, 'message' => $message], $status);
        }
    }

    public function sync(TikTokFinancialTransactionSyncRequest $request, Store $store): JsonResponse
    {
        $validated = $request->validated();

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

        if (! $store->is_active || strtolower((string) $store->status) !== 'active') {
            return response()->json([
                'success' => false,
                'code' => 'TIKTOK_STORE_INACTIVE',
                'message' => 'Toko TikTok sedang tidak aktif dan tidak dapat disinkronkan.',
            ], 422);
        }

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
                'exception' => get_class($e),
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
            [$message, $status, $code] = $this->safeFailure($e, 'Statement order TikTok belum dapat diambil.');
            Log::warning('[tiktok-order-statement] API gagal', [
                'store_id' => $store->id,
                'order_id' => $orderId,
                'code' => $code,
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'code' => $code,
                'message' => $message,
            ], $status);
        } catch (\Throwable $e) {
            Log::error('[tiktok-order-statement] Kesalahan internal', [
                'store_id' => $store->id,
                'order_id' => $orderId,
                'exception' => get_class($e),
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil settlement order TikTok.',
            ], 500);
        }
    }

    public function export(TikTokFinancialTransactionRequest $request, Store $store): StreamedResponse|JsonResponse
    {
        $validated = $request->validated();

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $source = $request->routeIs('marketplace.api.tiktok-wallet-transactions.export')
                ? 'account'
                : ($validated['source'] ?? 'statements');
            $items = $this->service->streamStoredTransactions($store, $from, $to, [
                'source' => $source,
                'search' => $validated['search'] ?? '',
                'types' => $validated['types'] ?? '',
                'status' => $validated['status'] ?? '',
                'page_size' => $validated['page_size'] ?? 100,
            ]);
            $filename = sprintf(
                'tiktok_%s_%s_%s_%s.csv',
                $source,
                preg_replace('/[^a-zA-Z0-9_-]/', '_', $store->name),
                $from->format('Ymd'),
                $to->format('Ymd'),
            );

            return response()->streamDownload(function () use ($items): void {
                $handle = fopen('php://output', 'w');
                fwrite($handle, "\xEF\xBB\xBF");
                fputcsv($handle, ['Sumber', 'Waktu', 'ID Transaksi', 'Statement ID', 'Order ID', 'Tipe', 'Arah Dana', 'Nominal', 'Mata Uang', 'Status', 'Keterangan']);
                foreach ($items as $item) {
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
            [$message, $status, $code] = $this->safeFailure($e, 'Export transaksi TikTok belum dapat dibuat.');

            return response()->json(['success' => false, 'code' => $code, 'message' => $message], $status);
        }
    }

    /** @return array{0:string,1:int,2:string} */
    private function safeFailure(RuntimeException $exception, string $fallback): array
    {
        $raw = strtolower($exception->getMessage());
        if (str_contains($raw, 'bukan channel')) {
            return ['Toko yang dipilih bukan channel TikTok Shop.', 422, 'NOT_TIKTOK_STORE'];
        }
        if (str_contains($raw, 'too many requests') || str_contains($raw, 'rate limit')) {
            return ['TikTok sedang membatasi permintaan. Coba lagi beberapa saat lagi.', 429, 'TIKTOK_RATE_LIMITED'];
        }
        if (str_contains($raw, 'access token') || str_contains($raw, 'invalid token') || str_contains($raw, 'expired')) {
            return ['Koneksi TikTok perlu diperbarui sebelum data dapat diambil.', 401, 'TIKTOK_AUTH_REQUIRED'];
        }

        return [$fallback, 502, 'TIKTOK_FINANCE_UNAVAILABLE'];
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
