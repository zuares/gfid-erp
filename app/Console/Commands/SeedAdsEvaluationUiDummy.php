<?php

namespace App\Console\Commands;

use App\Models\MarketplaceAdCampaign;
use App\Models\MarketplaceAdCampaignDaily;
use App\Models\MarketplaceAdExperiment;
use App\Models\MarketplaceAdsDaily;
use App\Models\Item;
use App\Models\Store;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SeedAdsEvaluationUiDummy extends Command
{
    private const SOURCE = 'ads_evaluation_ui_v1';
    private const CAMPAIGN_IDS = [
        'DUMMY-EVAL-UI-001',
        'DUMMY-EVAL-UI-002',
        'DUMMY-EVAL-UI-003',
        'DUMMY-EVAL-UI-004',
    ];

    protected $signature = 'marketplace:seed-ads-evaluation-ui
                            {--store=6 : ID toko dummy yang dipakai}
                            {--reset : Hapus data dummy ini sebelum membuat ulang}';

    protected $description = '[DEV ONLY] Buat dummy campaign, fact harian, dan evaluasi untuk test UI Ads Dashboard.';

    public function handle(): int
    {
        if (app()->isProduction()) {
            $this->error('Command dummy tidak boleh dijalankan di production.');

            return self::FAILURE;
        }

        $store = Store::query()
            ->whereKey((int) $this->option('store'))
            ->where('is_active', true)
            ->whereHas('channel', fn ($query) => $query->whereIn('code', ['SHP', 'shopee', 'SHOPEE']))
            ->first();

        if (! $store) {
            $this->error('Toko dummy Shopee tidak ditemukan atau tidak aktif.');

            return self::FAILURE;
        }

        try {
            DB::transaction(function () use ($store): void {
                if ($this->option('reset')) {
                    $this->resetDummy($store);
                }

                // Buat toko terlihat sebagai toko aktif pada dashboard lokal.
                // Tidak mengisi credential/token apa pun.
                $store->forceFill([
                    'status' => 'active',
                    'is_active' => true,
                    'token_expires_at' => now()->addDays(30),
                ])->save();

                $campaigns = [];
                foreach (self::CAMPAIGN_IDS as $index => $campaignId) {
                    $itemNumber = $index + 1;
                    $unitCogs = [24000, 52000, 46000, 18000][$index] ?? 25000;
                    $item = Item::query()->updateOrCreate(
                        ['code' => sprintf('DUMMY-ADS-UI-%03d', $itemNumber)],
                        [
                            'name' => 'DUMMY Produk Ads · Evaluasi ' . $itemNumber,
                            'unit' => 'pcs',
                            'type' => 'finished',
                            'hpp' => $unitCogs,
                            'base_unit_cost' => $unitCogs,
                            'active' => true,
                            'hpp_behavior' => 'hpp',
                        ],
                    );

                    $campaigns[$campaignId] = MarketplaceAdCampaign::query()->updateOrCreate(
                        [
                            'store_id' => $store->id,
                            'channel_campaign_id' => $campaignId,
                        ],
                        [
                            'campaign_name' => 'DUMMY UI · Evaluasi ' . ($index + 1),
                            'campaign_type' => 'manual',
                            'channel_item_id' => 990000 + $itemNumber,
                            'internal_item_id' => $item->id,
                            'status' => 'ongoing',
                            'campaign_status' => 'ongoing',
                            'ad_type' => 'manual',
                            'target_roas' => 3.5,
                            'campaign_budget' => 125000,
                            'mapping_status' => 'manual',
                            'mapping_source' => 'dummy',
                            'synced_at' => now(),
                        ],
                    );
                }

                $this->seedDailyFacts($store, $campaigns);
                $this->seedExperiments($store, $campaigns);
            });
        } catch (\Throwable $exception) {
            $this->error('Gagal membuat dummy Ads Evaluation: ' . $exception->getMessage());

            return self::FAILURE;
        }

        $this->info('Dummy Ads Evaluation berhasil dibuat.');
        $this->line('Toko: ' . $store->name . ' (#' . $store->id . ')');
        $this->line('Campaigns: ' . implode(', ', self::CAMPAIGN_IDS));
        $this->line('Buka: /marketplace/ads-dashboard?store_id=' . $store->id);
        $this->line('Hapus dan buat ulang: php artisan marketplace:seed-ads-evaluation-ui --reset');

        return self::SUCCESS;
    }

    /**
     * @param array<string, MarketplaceAdCampaign> $campaigns
     */
    private function seedDailyFacts(Store $store, array $campaigns): void
    {
        $today = Carbon::today(config('app.timezone', 'Asia/Jakarta'));

        foreach ($campaigns as $campaign) {
            for ($daysAgo = 21; $daysAgo >= 1; $daysAgo--) {
                $date = $today->copy()->subDays($daysAgo);
                $isObservation = $daysAgo <= 10 && $daysAgo >= 3;
                $impressions = $isObservation ? 2100 + ($daysAgo * 35) : 1550 + ($daysAgo * 25);
                $clicks = $isObservation ? 105 + ($daysAgo % 5) : 67 + ($daysAgo % 4);
                $orders = $isObservation ? 8 + ($daysAgo % 3) : 5 + ($daysAgo % 2);
                $spend = $isObservation ? 92000 + ($daysAgo * 850) : 76000 + ($daysAgo * 700);
                $gmv = $isObservation ? 720000 + ($daysAgo * 12000) : 420000 + ($daysAgo * 9000);

                MarketplaceAdCampaignDaily::query()->updateOrCreate(
                    [
                        'store_id' => $store->id,
                        'channel_campaign_id' => $campaign->channel_campaign_id,
                        'date' => $date->toDateString(),
                    ],
                    [
                        'ad_type' => 'manual',
                        'impressions' => $impressions,
                        'clicks' => $clicks,
                        'expense' => $spend,
                        'broad_order' => $orders,
                        'broad_order_amount' => $orders,
                        'broad_gmv' => $gmv,
                        'direct_order' => max(0, $orders - 2),
                        'direct_order_amount' => max(0, $orders - 2),
                        'direct_gmv' => round($gmv * 0.72, 2),
                        'cpc' => round($spend / max(1, $clicks), 4),
                        'raw_json' => ['dummy_source' => self::SOURCE],
                    ],
                );
            }
        }

        for ($daysAgo = 21; $daysAgo >= 1; $daysAgo--) {
            $date = $today->copy()->subDays($daysAgo);
            $isObservation = $daysAgo <= 10 && $daysAgo >= 3;
            $impressions = $isObservation ? 2100 + ($daysAgo * 35) : 1550 + ($daysAgo * 25);
            $clicks = $isObservation ? 105 + ($daysAgo % 5) : 67 + ($daysAgo % 4);
            $orders = $isObservation ? 8 + ($daysAgo % 3) : 5 + ($daysAgo % 2);
            $spend = $isObservation ? 92000 + ($daysAgo * 850) : 76000 + ($daysAgo * 700);
            $gmv = $isObservation ? 720000 + ($daysAgo * 12000) : 420000 + ($daysAgo * 9000);
            MarketplaceAdsDaily::query()->updateOrCreate(
                [
                    'store_id' => $store->id,
                    'date' => $date->toDateString(),
                ],
                [
                    'impressions' => $impressions,
                    'clicks' => $clicks,
                    'ctr' => round(($clicks / max(1, $impressions)) * 100, 4),
                    'spend' => $spend,
                    'orders' => $orders,
                    'gmv' => $gmv,
                    'roas' => round($gmv / max(1, $spend), 4),
                    'raw_json' => ['dummy_source' => self::SOURCE],
                ],
            );
        }
    }

    /**
     * @param array<string, MarketplaceAdCampaign> $campaigns
     */
    private function seedExperiments(Store $store, array $campaigns): void
    {
        $today = Carbon::today(config('app.timezone', 'Asia/Jakarta'));
        $rows = [
            [
                'key' => 'creative',
                'type' => 'creative',
                'days_ago' => 10,
                'before' => 'Foto utama A',
                'after' => 'Foto utama B',
                'objective' => 'Naikkan CTR tanpa menurunkan ROAS',
                'kpi' => 'ctr',
                'notes' => 'Dummy: creative baru lebih terang dan menampilkan detail produk.',
                'confounders' => [],
                'campaign' => 'DUMMY-EVAL-UI-001',
            ],
            [
                'key' => 'roas-confounded',
                'type' => 'target_roas',
                'days_ago' => 8,
                'before' => '3.0',
                'after' => '3.5',
                'objective' => 'Meningkatkan efisiensi belanja iklan',
                'kpi' => 'roas',
                'notes' => 'Dummy: budget juga berubah di hari yang sama untuk demo status Confounded.',
                'confounders' => ['budget'],
                'campaign' => 'DUMMY-EVAL-UI-002',
            ],
            [
                'key' => 'price-early',
                'type' => 'price',
                'days_ago' => 4,
                'before' => '149000',
                'after' => '159000',
                'objective' => 'Menguji dampak harga baru terhadap profit per order',
                'kpi' => 'profit',
                'notes' => 'Dummy: observation baru berjalan beberapa hari.',
                'confounders' => [],
                'campaign' => 'DUMMY-EVAL-UI-003',
            ],
            [
                'key' => 'budget-learning',
                'type' => 'budget',
                'days_ago' => 1,
                'before' => '100000',
                'after' => '125000',
                'objective' => 'Menambah volume saat ROAS masih sehat',
                'kpi' => 'orders',
                'notes' => 'Dummy: event baru untuk demo status Learning.',
                'confounders' => [],
                'campaign' => 'DUMMY-EVAL-UI-004',
            ],
            [
                'key' => 'title-completed',
                'type' => 'title',
                'days_ago' => 15,
                'before' => 'Judul lama',
                'after' => 'Judul dengan keyword utama',
                'objective' => 'Meningkatkan CTR dari pencarian',
                'kpi' => 'ctr',
                'notes' => 'Dummy: event lama untuk demo observation lengkap.',
                'confounders' => [],
                'campaign' => 'DUMMY-EVAL-UI-004',
            ],
            [
                'key' => 'store-level',
                'type' => 'other',
                'days_ago' => 12,
                'before' => 'Strategi lama',
                'after' => 'Strategi baru',
                'objective' => 'Menguji perubahan strategi iklan tingkat toko',
                'kpi' => 'roas',
                'notes' => 'Dummy store-level tanpa campaign spesifik.',
                'confounders' => [],
                'store_level' => true,
            ],
        ];

        foreach ($rows as $index => $row) {
            $changedAt = $today->copy()->subDays($row['days_ago'])->setTime(10, 0);
            $eventId = sprintf('00000000-0000-0000-0000-%012d', 910000 + $index);
            $snapshot = [
                'workflow' => 'manual_optimization',
                'seed_source' => self::SOURCE,
                'change_label' => match ($row['type']) {
                    'creative' => 'Ganti foto / creative',
                    'target_roas' => 'Update target ROAS',
                    'price' => 'Update harga',
                    'budget' => 'Update budget',
                    'title' => 'Update judul/deskripsi',
                    default => 'Perubahan lainnya',
                },
                'before' => $row['before'],
                'after' => $row['after'],
                'objective' => $row['objective'],
                'primary_kpi' => $row['kpi'],
                'notes' => $row['notes'],
                'confounders' => $row['confounders'],
            ];

            MarketplaceAdExperiment::query()->updateOrCreate(
                ['change_event_id' => $eventId],
                [
                    'store_id' => $store->id,
                    'channel_campaign_id' => ! ($row['store_level'] ?? false) ? $row['campaign'] : null,
                    'channel_item_id' => null,
                    'change_type' => $row['type'],
                    'old_price' => $row['type'] === 'price' ? (float) $row['before'] : null,
                    'new_price' => $row['type'] === 'price' ? (float) $row['after'] : null,
                    'old_target_roas' => $row['type'] === 'target_roas' ? (float) $row['before'] : null,
                    'new_target_roas' => $row['type'] === 'target_roas' ? (float) $row['after'] : null,
                    'changed_at' => $changedAt,
                    'effective_date' => $changedAt->toDateString(),
                    'lifecycle_status' => MarketplaceAdExperiment::STATUS_LEARNING,
                    'profit_basis' => 'net_hpp_ads',
                    'source_granularity' => ($row['store_level'] ?? false) ? 'store' : 'campaign',
                    'mapping_status' => 'missing_mapping',
                    'confounded' => $row['confounders'] !== [],
                    'data_quality_flags' => ['partial_day_excluded', 'estimated_qty', 'dummy_data'],
                    'conflict_reason' => $row['confounders'] !== [] ? $row['confounders'] : null,
                    'calculation_snapshot' => $snapshot,
                    'baseline_days' => 7,
                    'observation_days' => 7,
                    'calculation_version' => 'ui-dummy-v1',
                ],
            );
        }
    }

    private function resetDummy(Store $store): void
    {
        $dummyExperimentIds = MarketplaceAdExperiment::query()
            ->where('store_id', $store->id)
            ->whereJsonContains('calculation_snapshot->seed_source', self::SOURCE)
            ->pluck('id');

        MarketplaceAdExperiment::query()->whereKey($dummyExperimentIds)->delete();
        MarketplaceAdCampaignDaily::query()
            ->where('store_id', $store->id)
            ->whereIn('channel_campaign_id', self::CAMPAIGN_IDS)
            ->delete();
        MarketplaceAdsDaily::query()
            ->where('store_id', $store->id)
            ->whereJsonContains('raw_json->dummy_source', self::SOURCE)
            ->delete();
        MarketplaceAdCampaign::query()
            ->where('store_id', $store->id)
            ->whereIn('channel_campaign_id', self::CAMPAIGN_IDS)
            ->delete();
    }
}
