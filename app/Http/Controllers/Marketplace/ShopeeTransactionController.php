<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\Shopee\ShopeeWalletTransactionService;
use Carbon\Carbon;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use RuntimeException;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ShopeeTransactionController extends Controller
{
    public function __construct(
        protected ShopeeWalletTransactionService $walletService,
    ) {}

    /**
     * Tampilan modul transaksi wallet Shopee.
     */
    public function index(): \Illuminate\View\View
    {
        $stores = Store::query()
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->where('is_active', true)
            ->orderBy('name')
            ->get();

        $transactionTypes = $this->walletService->getTransactionTypes();

        return view('marketplace.shopee.transactions', compact('stores', 'transactionTypes'));
    }

    /**
     * Endpoint API JSON untuk penarikan data transaksi live dari Shopee.
     */
    public function data(Request $request, Store $store): JsonResponse
    {
        $validated = $request->validate([
            'date_from'        => ['required', 'date_format:Y-m-d'],
            'date_to'          => ['required', 'date_format:Y-m-d'],
            'page_no'          => ['nullable', 'integer', 'min:0'],
            'page_size'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'money_flow'       => ['nullable', 'string', 'in:MONEY_IN,MONEY_OUT,ALL,money_in,money_out,all'],
            'transaction_type' => ['nullable', 'string', 'max:100'],
            'wallet_type'      => ['nullable', 'string', 'max:50'],
            'search'           => ['nullable', 'string', 'max:100'],
        ]);

        [$from, $to, $error] = $this->validateAndParseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], 422);
        }

        try {
            $options = [
                'page_no'          => (int) ($validated['page_no'] ?? 0),
                'page_size'        => (int) ($validated['page_size'] ?? 100),
                'money_flow'       => $validated['money_flow'] ?? null,
                'transaction_type' => $validated['transaction_type'] ?? null,
                'wallet_type'      => $validated['wallet_type'] ?? null,
                'search'           => $validated['search'] ?? null,
            ];

            $result = $this->walletService->fetchTransactions($store, $from, $to, $options);

            return response()->json([
                'success' => true,
                'source'  => 'shopee_live',
                'data'    => $result,
            ]);
        } catch (RuntimeException $e) {
            Log::warning('[shopee-transactions] Gagal menarik data dari Shopee', [
                'store_id' => $store->id,
                'error'    => $e->getMessage(),
            ]);

            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 502);
        } catch (\Throwable $e) {
            Log::error('[shopee-transactions] Kesalahan internal saat mengambil data', [
                'store_id'  => $store->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Terjadi kesalahan sistem saat mengambil data transaksi dari Shopee.',
            ], 500);
        }
    }

    /**
     * Unduh hasil penarikan data transaksi sebagai file CSV secara streaming (tanpa database).
     */
    public function export(Request $request, Store $store): StreamedResponse|JsonResponse
    {
        $validated = $request->validate([
            'date_from'        => ['required', 'date_format:Y-m-d'],
            'date_to'          => ['required', 'date_format:Y-m-d'],
            'page_size'        => ['nullable', 'integer', 'min:1', 'max:100'],
            'money_flow'       => ['nullable', 'string'],
            'transaction_type' => ['nullable', 'string'],
            'wallet_type'      => ['nullable', 'string'],
            'search'           => ['nullable', 'string', 'max:100'],
        ]);

        [$from, $to, $error] = $this->validateAndParseDateWindow($validated['date_from'], $validated['date_to']);
        if ($error !== null) {
            return response()->json([
                'success' => false,
                'message' => $error,
            ], 422);
        }

        try {
            $options = [
                'page_no'          => 0,
                'page_size'        => min(100, max(1, (int) ($validated['page_size'] ?? 100))),
                'money_flow'       => $validated['money_flow'] ?? null,
                'transaction_type' => $validated['transaction_type'] ?? null,
                'wallet_type'      => $validated['wallet_type'] ?? null,
                'search'           => $validated['search'] ?? null,
            ];

            $result = $this->walletService->fetchTransactions($store, $from, $to, $options);
            $items = $result['items'] ?? [];

            $filename = sprintf(
                'shopee_wallet_transactions_%s_%s_%s.csv',
                preg_replace('/[^a-zA-Z0-9_-]/', '_', $store->name),
                $from->format('Ymd'),
                $to->format('Ymd')
            );

            return response()->streamDownload(function () use ($items) {
                $handle = fopen('php://output', 'w');
                // UTF-8 BOM untuk Excel
                fputs($handle, "\xEF\xBB\xBF");

                // Header CSV
                fputcsv($handle, [
                    'Waktu Transaksi',
                    'ID Transaksi',
                    'Tipe Transaksi',
                    'Arah Dana',
                    'Nominal (Rp)',
                    'Saldo Berjalan (Rp)',
                    'No Pesanan (Order SN)',
                    'Nama Pembeli',
                    'Keterangan / Deskripsi',
                    'Status',
                    'Tipe Wallet',
                ]);

                foreach ($items as $item) {
                    fputcsv($handle, [
                        $item['date_formatted'] ?? '',
                        $item['transaction_id'] ?? '',
                        $item['transaction_type_label'] ?? $item['transaction_type'] ?? '',
                        $item['money_flow'] === 'MONEY_IN' ? 'Dana Masuk' : 'Dana Keluar',
                        $item['signed_amount'] ?? 0,
                        $item['current_balance'] ?? '',
                        $item['order_sn'] ?? '',
                        $item['buyer_name'] ?? '',
                        $item['reason'] ?? '',
                        $item['status'] ?? '',
                        $item['wallet_type'] ?? '',
                    ]);
                }

                fclose($handle);
            }, $filename, [
                'Content-Type' => 'text/csv; charset=UTF-8',
            ]);
        } catch (\Throwable $e) {
            Log::error('[shopee-transactions-export] Gagal ekspor CSV', [
                'store_id'  => $store->id,
                'exception' => $e,
            ]);

            return response()->json([
                'success' => false,
                'message' => 'Gagal menghasilkan file ekspor CSV: ' . $e->getMessage(),
            ], 502);
        }
    }

    /**
     * Validasi batas rentang tanggal maksimal 60 hari.
     * Service secara otomatis memecah request ke potongan 14 hari bila rentang > 15 hari.
     *
     * @return array{0: ?Carbon, 1: ?Carbon, 2: ?string}
     */
    protected function validateAndParseDateWindow(string $fromStr, string $toStr): array
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');
        $from = Carbon::createFromFormat('Y-m-d', $fromStr, $timezone)->startOfDay();
        $to = Carbon::createFromFormat('Y-m-d', $toStr, $timezone)->endOfDay();

        if ($from->greaterThan($to)) {
            return [null, null, 'Tanggal mulai tidak boleh melewati tanggal akhir.'];
        }

        if ($from->diffInDays($to) > 60) {
            return [null, null, 'Rentang tanggal maksimal 60 hari.'];
        }

        return [$from, $to, null];
    }
}
