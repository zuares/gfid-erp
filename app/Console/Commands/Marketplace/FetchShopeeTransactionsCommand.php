<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\Marketplace\Shopee\ShopeeWalletTransactionService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class FetchShopeeTransactionsCommand extends Command
{
    protected $signature = 'shopee:fetch-transactions
        {store? : ID atau sebagian nama toko Shopee}
        {--from= : Tanggal mulai (Y-m-d, default: 7 hari lalu)}
        {--to= : Tanggal selesai (Y-m-d, default: hari ini)}
        {--flow= : Filter arah dana (MONEY_IN, MONEY_OUT, ALL)}
        {--type= : Filter tipe transaksi (misal: 101, 201, 450, ESCROW_VERIFIED_ADD, dll)}
        {--limit=50 : Batas data per request (1-100)}
        {--raw : Tampilkan format raw JSON response}';

    protected $description = 'Tarik riwayat transaksi wallet Shopee secara live tanpa menyentuh database.';

    public function handle(ShopeeWalletTransactionService $service): int
    {
        $timezone = config('app.timezone', 'Asia/Jakarta');

        $fromInput = (string) ($this->option('from') ?: now()->subDays(6)->toDateString());
        $toInput = (string) ($this->option('to') ?: now()->toDateString());

        try {
            $from = Carbon::createFromFormat('Y-m-d', $fromInput, $timezone)->startOfDay();
            $to = Carbon::createFromFormat('Y-m-d', $toInput, $timezone)->endOfDay();
        } catch (\Throwable) {
            $this->error('Format tanggal tidak valid. Gunakan format Y-m-d.');
            return self::FAILURE;
        }

        if ($from->greaterThan($to)) {
            $this->error('Tanggal --from tidak boleh melewati tanggal --to.');
            return self::FAILURE;
        }

        if ($from->diffInSeconds($to) >= 15 * 86400) {
            $this->error('Rentang waktu maksimal 15 hari sesuai batas Shopee API get_wallet_transaction_list.');
            return self::FAILURE;
        }

        $storeIdentifier = $this->argument('store');
        $query = Store::query()
            ->whereHas('channel', fn ($q) => $q->whereIn('code', ['shopee', 'shp', 'SHOPEE']))
            ->where('is_active', true);

        if ($storeIdentifier) {
            if (is_numeric($storeIdentifier)) {
                $query->where('id', (int) $storeIdentifier);
            } else {
                $query->where('name', 'like', "%{$storeIdentifier}%");
            }
        }

        $store = $query->first();

        if (! $store) {
            $this->error('Toko Shopee aktif tidak ditemukan.');
            return self::FAILURE;
        }

        $limit = min(100, max(1, (int) $this->option('limit')));
        $flow = $this->option('flow');
        $type = $this->option('type');

        $this->info("Menarik transaksi wallet live dari Shopee untuk toko: [{$store->id}] {$store->name}...");
        $this->line("Periode: {$from->toDateString()} s/d {$to->toDateString()} (Timezone: {$timezone})");

        try {
            $result = $service->fetchTransactions($store, $from, $to, [
                'page_no'          => 0,
                'page_size'        => $limit,
                'money_flow'       => $flow,
                'transaction_type' => $type,
            ]);

            if ($this->option('raw')) {
                $this->line(json_encode($result, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE));
                return self::SUCCESS;
            }

            $summary = $result['summary'];
            $items = $result['items'];

            // Tampilkan Ringkasan In-Memory
            $this->newLine();
            $this->table(
                ['Metrik', 'Nilai'],
                [
                    ['Total Transaksi', number_format($summary['total_count'])],
                    ['Total Dana Masuk (+)', 'Rp ' . number_format($summary['total_money_in'], 0, ',', '.')],
                    ['Total Dana Keluar (-)', 'Rp ' . number_format($summary['total_money_out'], 0, ',', '.')],
                    ['Perubahan Bersih (Net)', ($summary['net_amount'] < 0 ? '- Rp ' : '+ Rp ') . number_format(abs($summary['net_amount']), 0, ',', '.')],
                ]
            );

            if (empty($items)) {
                $this->warn('Tidak ada transaksi ditemukan pada rentang waktu dan filter tersebut.');
                return self::SUCCESS;
            }

            // Tampilkan Tabel Baris Transaksi
            $this->newLine();
            $tableRows = array_map(function ($item) {
                return [
                    $item['date_formatted'],
                    $item['transaction_id'],
                    $item['transaction_type_label'],
                    $item['money_flow'] === 'MONEY_IN' ? 'MASUK' : 'KELUAR',
                    $item['signed_formatted_amount'],
                    $item['formatted_balance'],
                    $item['order_sn'] ?? '—',
                    mb_strimwidth($item['reason'] ?? '—', 0, 30, '...'),
                ];
            }, $items);

            $this->table(
                ['Waktu', 'ID Transaksi', 'Tipe Transaksi', 'Arah', 'Nominal', 'Saldo Akhir', 'Order SN', 'Keterangan'],
                $tableRows
            );

            $this->info("Catatan: Data ini ditarik secara live in-memory dari Shopee dan TIDAK disimpan ke database.");

            return self::SUCCESS;
        } catch (\Throwable $e) {
            $this->error("Gagal menarik data dari Shopee: " . $e->getMessage());
            return self::FAILURE;
        }
    }
}
