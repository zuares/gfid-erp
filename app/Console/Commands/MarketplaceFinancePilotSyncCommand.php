<?php

namespace App\Console\Commands;

use App\Models\Store;
use App\Services\Marketplace\Finance\MarketplaceFinanceBackfillService;
use App\Services\Marketplace\Finance\MarketplaceSettlementNormalizer;
use App\Services\Marketplace\Finance\MarketplaceSettlementSyncService;
use App\Services\Marketplace\MarketplaceApiGateway;
use Carbon\Carbon;
use Illuminate\Console\Command;

class MarketplaceFinancePilotSyncCommand extends Command
{
    protected $signature = 'marketplace:finance-pilot-sync
                            {--store=Greatfit.id : Nama toko Shopee yang persis, bukan ID lintas lingkungan}
                            {--from= : Tanggal awal payout (Y-m-d)}
                            {--to= : Tanggal akhir payout (Y-m-d)}
                            {--probe-payout : Baca halaman pertama payout info/detail per 7 hari tanpa menulis Finance}
                            {--apply : Backfill tabel Finance dan sinkronkan payout; default hanya dry-run}';

    protected $description = 'Pilot Finance satu toko Shopee: backfill legacy lalu sinkronkan payout tanpa posting jurnal';

    public function handle(
        MarketplaceFinanceBackfillService $backfill,
        MarketplaceSettlementSyncService $settlementSync,
        MarketplaceApiGateway $gateway,
        MarketplaceSettlementNormalizer $normalizer,
    ): int {
        $name = trim((string) $this->option('store'));
        $stores = Store::query()->with('channel')->where('name', $name)->limit(2)->get();
        if ($name === '' || $stores->count() !== 1 || strtolower((string) $stores->first()->channel?->code) !== 'shopee') {
            $this->error('Toko harus cocok persis dengan satu toko Shopee yang terdaftar.');

            return self::INVALID;
        }
        $store = $stores->first();

        $from = $this->dateOption('from');
        $to = $this->dateOption('to');
        if (! $from || ! $to || $from->greaterThan($to)) {
            $this->error('Isi --from dan --to sebagai rentang tanggal Y-m-d yang valid.');

            return self::INVALID;
        }

        $apply = (bool) $this->option('apply');
        $probePayout = (bool) $this->option('probe-payout');
        $this->line(sprintf('Toko: %s (ID lingkungan ini: %d) | %s s.d. %s | %s',
            $store->name, $store->id, $from->toDateString(), $to->toDateString(), $apply ? 'APPLY' : 'DRY-RUN'));
        if (($apply || $probePayout) && $store->connection_status !== 'CONNECTED') {
            $this->error('Koneksi API toko belum aktif. Tidak ada backfill atau sinkronisasi yang dijalankan.');

            return self::FAILURE;
        }

        // Always preflight before making a write; this step never posts a journal.
        $preflight = $backfill->run(['dry_run' => true, 'store_id' => $store->id]);
        $this->line('Backfill dry-run: '.json_encode($preflight['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (($preflight['summary']['unallocated_wallet_withdrawals'] ?? 0) > 0) {
            $this->warn($preflight['summary']['unallocated_wallet_withdrawals'].' penarikan wallet tanpa alokasi order; ini bukan bukti uang sudah masuk bank.');
        }
        if ($preflight['summary']['errors'] > 0 || $preflight['summary']['duplicates'] > 0 || $preflight['summary']['unmatched'] > 0) {
            $this->error('Backfill memiliki error, duplikat, atau sumber tanpa pasangan. Tidak ada perubahan yang dijalankan.');

            return self::FAILURE;
        }
        if ($apply || $probePayout) {
            for ($windowStart = $from->copy(); $windowStart->lessThanOrEqualTo($to); $windowStart->addDays(7)) {
                $windowEnd = $windowStart->copy()->addDays(6)->min($to)->endOfDay();
                $probe = $this->probePayout($gateway, $normalizer, $store, $windowStart->copy()->startOfDay(), $windowEnd);
                $this->line(sprintf('Payout API probe %s s.d. %s: info=%d, detail=%d, order_refs=%d, halaman_lanjutan=%s',
                    $windowStart->toDateString(), $windowEnd->toDateString(), $probe['info'], $probe['detail'], $probe['order_refs'], $probe['more'] ? 'ya' : 'tidak'));
                if (! $probe['ok']) {
                    $this->error('Endpoint payout tidak berhasil dibaca. Tidak ada backfill atau sinkronisasi yang dijalankan.');

                    return self::FAILURE;
                }
            }
            $this->warn('Probe hanya membaca halaman pertama tiap jendela; jumlah di atas bukan total payout dan bukan bukti penerimaan bank.');
        }
        if (! $apply) {
            $this->info($probePayout
                ? 'DRY-RUN: payout API dibaca, tabel Finance dan jurnal tidak disentuh.'
                : 'DRY-RUN: payout API dan jurnal tidak disentuh.');

            return self::SUCCESS;
        }

        $backfillResult = $backfill->run(['dry_run' => false, 'store_id' => $store->id]);
        $this->line('Backfill apply: '.json_encode($backfillResult['summary'], JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if ($backfillResult['summary']['errors'] > 0 || $backfillResult['summary']['duplicates'] > 0 || $backfillResult['summary']['unmatched'] > 0) {
            $this->error('Backfill apply belum bersih. Payout tidak disinkronkan.');

            return self::FAILURE;
        }

        $payouts = ['found' => 0, 'synced' => 0, 'allocated' => 0, 'unmatched' => 0, 'errors' => 0];
        for ($windowStart = $from->copy(); $windowStart->lessThanOrEqualTo($to); $windowStart->addDays(7)) {
            $windowEnd = $windowStart->copy()->addDays(6)->min($to)->endOfDay();
            $result = $settlementSync->syncPayouts($store, $windowStart->copy()->startOfDay(), $windowEnd);
            foreach (array_keys($payouts) as $key) {
                $payouts[$key] += (int) ($result[$key] ?? 0);
            }
            if (($result['status'] ?? '') !== 'synced') {
                $this->error('Sinkronisasi payout berhenti pada '.$windowStart->toDateString().': '.($result['message'] ?? 'status tidak berhasil'));

                return self::FAILURE;
            }
        }

        $this->info('Payout: '.json_encode($payouts, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        $this->line('Tidak ada jurnal atau penerimaan bank yang diposting. Cocokkan payout dan mutasi bank sebelum posting.');

        return self::SUCCESS;
    }

    /** @return array{ok:bool,info:int,detail:int,order_refs:int,more:bool} */
    private function probePayout(
        MarketplaceApiGateway $gateway,
        MarketplaceSettlementNormalizer $normalizer,
        Store $store,
        Carbon $from,
        Carbon $to,
    ): array {
        $result = ['ok' => false, 'info' => 0, 'detail' => 0, 'order_refs' => 0, 'more' => false];

        try {
            $info = $gateway->getPayoutInfo($store, $from->timestamp, $to->timestamp, '', 100);
            if ($this->apiFailed($info)) {
                return $result;
            }
            $detail = $gateway->getPayoutDetail($store, $from->timestamp, $to->timestamp, 1, 100);
            if ($this->apiFailed($detail)) {
                return $result;
            }
            $infoRows = data_get($info, 'response.payout_list') ?? data_get($info, 'payout_list', []);
            $detailRows = data_get($detail, 'response.payout_list') ?? data_get($detail, 'payout_list', []);
            if (! is_array($infoRows) || ! is_array($detailRows)) {
                return $result;
            }
            $infoRows = array_is_list($infoRows) ? $infoRows : [$infoRows];
            $detailRows = array_is_list($detailRows) ? $detailRows : [$detailRows];
            $result['info'] = count(array_filter($infoRows, 'is_array'));
            $result['detail'] = count(array_filter($detailRows, 'is_array'));
            foreach ($detailRows as $row) {
                if (is_array($row)) {
                    $result['order_refs'] += count($normalizer->allocations($row));
                }
            }
            $result['more'] = (bool) (data_get($info, 'response.more') ?? data_get($info, 'more') ?? false)
                || (bool) (data_get($detail, 'response.more') ?? data_get($detail, 'more') ?? false);
            $result['ok'] = true;
        } catch (\Throwable) {
            // Tidak tampilkan pesan API mentah di terminal karena dapat memuat token.
        }

        return $result;
    }

    private function apiFailed(array $response): bool
    {
        return ! empty($response['error'])
            || (($response['code'] ?? null) !== null && (string) $response['code'] !== '0');
    }

    private function dateOption(string $name): ?Carbon
    {
        $value = (string) $this->option($name);
        if (! preg_match('/^\d{4}-\d{2}-\d{2}$/', $value)) {
            return null;
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', $value, config('app.timezone'));

            return $date && $date->format('Y-m-d') === $value ? $date : null;
        } catch (\Throwable) {
            return null;
        }
    }
}
