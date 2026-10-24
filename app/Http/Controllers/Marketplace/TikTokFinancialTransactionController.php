<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\TikTokShop\TikTokFinancialTransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
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
            'page_token' => ['nullable', 'string', 'max:2000'],
        ]);

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $source = $validated['source'] ?? 'account';
            $result = $source === 'statements'
                ? $this->service->fetchStatementTransactions($store, $from, $to, [
                    'status' => $validated['status'] ?? '',
                    'page_size' => $validated['page_size'] ?? 100,
                ])
                : $this->service->fetchTransactions($store, $from, $to, [
                    'types' => $validated['types'] ?? '',
                    'status' => $validated['status'] ?? '',
                    'page_size' => $validated['page_size'] ?? 100,
                    'page_token' => $validated['page_token'] ?? '',
                ]);

            return response()->json([
                'success' => true,
                'source' => 'tiktok_financial_transactions',
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

    public function export(Request $request, Store $store): StreamedResponse|JsonResponse
    {
        $validated = $request->validate([
            'date_from' => ['required', 'date_format:Y-m-d'],
            'date_to' => ['required', 'date_format:Y-m-d'],
            'types' => ['nullable', 'string', 'max:200'],
            'status' => ['nullable', 'string', 'max:40'],
            'source' => ['nullable', 'in:account,statements'],
        ]);

        [$from, $to, $error] = $this->parseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json(['success' => false, 'message' => $error], 422);
        }

        try {
            $source = $validated['source'] ?? 'account';
            $result = $source === 'statements'
                ? $this->service->fetchStatementTransactions($store, $from, $to, [
                    'status' => $validated['status'] ?? '',
                ])
                : $this->service->fetchAllTransactions($store, $from, $to, [
                    'types' => $validated['types'] ?? '',
                    'status' => $validated['status'] ?? '',
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
