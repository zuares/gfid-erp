<?php

namespace App\Console\Commands\Marketplace;

use App\Models\Store;
use App\Services\MarketplaceSyncService;
use Illuminate\Console\Command;

class BackfillTikTokAffiliateFeeDuplicatesCommand extends Command
{
    protected $signature = 'marketplace:backfill-tiktok-affiliate-fees
        {--store_id=all : TikTok store ID atau all}
        {--limit=5000 : Maksimum row settlement yang dipindai per toko}
        {--apply : Simpan koreksi (default dry-run)}';

    protected $description = 'Hapus duplikasi fee affiliate TikTok dari settlement lama (default dry-run).';

    public function handle(MarketplaceSyncService $syncService): int
    {
        $storeOption = (string) $this->option('store_id');
        $limit = (int) $this->option('limit');
        if ($limit < 1 || $limit > 50000) {
            $this->error('Opsi --limit harus berada di antara 1 dan 50000.');

            return self::INVALID;
        }

        $stores = Store::query()
            ->with('channel')
            ->where('status', 'active')
            ->where('is_active', true)
            ->whereHas('channel', fn ($channel) => $channel->whereIn('code', ['tiktok', 'ttk', 'tkt', 'tt']))
            ->when($storeOption !== 'all', fn ($query) => $query->whereKey((int) $storeOption))
            ->get();

        if ($stores->isEmpty()) {
            $this->error('Tidak ada toko TikTok aktif yang cocok.');

            return self::FAILURE;
        }

        $apply = (bool) $this->option('apply');
        $totals = [
            'scanned' => 0,
            'candidates' => 0,
            'updated' => 0,
            'unchanged' => 0,
            'overcount_total' => 0.0,
            'errors' => 0,
        ];

        $this->info(($apply ? 'MODE APPLY' : 'MODE DRY-RUN').' · '.count($stores).' toko');
        foreach ($stores as $store) {
            $stats = $syncService->backfillTikTokAffiliateFeeDuplicates($store, $apply, $limit);
            foreach ($totals as $key => $value) {
                $totals[$key] += $stats[$key] ?? 0;
            }

            $this->table(
                ['Store', 'Scanned', 'Candidates', 'Updated', 'Overcount'],
                [[
                    $store->name.' (#'.$store->id.')',
                    $stats['scanned'],
                    $stats['candidates'],
                    $stats['updated'],
                    'Rp '.number_format((float) $stats['overcount_total'], 0, ',', '.'),
                ]],
            );

            foreach (array_slice($stats['examples'] ?? [], 0, 5) as $example) {
                $this->line(sprintf(
                    '  %s: Rp %s -> Rp %s',
                    $example['order_id'],
                    number_format((float) $example['commission_fee'], 0, ',', '.'),
                    number_format((float) $example['corrected_commission_fee'], 0, ',', '.'),
                ));
            }
        }

        $this->line('SUMMARY '.json_encode($totals, JSON_UNESCAPED_UNICODE | JSON_UNESCAPED_SLASHES));
        if (! $apply) {
            $this->warn('Dry-run: tidak ada perubahan disimpan. Tambahkan --apply setelah hasil preview sesuai.');
        }

        return $totals['errors'] > 0 ? self::FAILURE : self::SUCCESS;
    }
}
