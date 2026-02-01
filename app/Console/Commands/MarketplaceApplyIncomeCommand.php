<?php

namespace App\Console\Commands;

use App\Models\MpIncome;
use App\Models\MpShipment;
use Illuminate\Console\Command;

class MarketplaceApplyIncomeCommand extends Command
{
    protected $signature = 'marketplace:apply-income
        {channel : shopee|tiktok}
        {--store_id= : store id}
        {--from= : released_at from (Y-m-d)}
        {--to= : released_at to (Y-m-d)}
        {--dry-run}';

    protected $description = 'Apply mp_incomes payout to mp_shipments (primary shipment only).';

    public function handle(): int
    {
        $channel = strtolower(trim((string) $this->argument('channel')));
        $storeId = (int) ($this->option('store_id') ?: 0);
        $from = $this->option('from');
        $to = $this->option('to');
        $dry = (bool) $this->option('dry-run');

        if ($storeId <= 0) {
            $this->error('store_id wajib. contoh: --store_id=1');
            return self::FAILURE;
        }

        $q = MpIncome::query()
            ->where('store_id', $storeId)
            ->where('channel', $channel);

        if ($from) {
            $q->whereDate('released_at', '>=', $from);
        }

        if ($to) {
            $q->whereDate('released_at', '<=', $to);
        }

        $incomes = $q->get();

        $orders = 0;
        $matched = 0;
        $unmatched = 0;
        $shipUpdated = 0;

        foreach ($incomes as $inc) {
            $orders++;

            $primary = MpShipment::query()
                ->where('store_id', $storeId)
                ->where('channel', $channel)
                ->where('platform_order_id', $inc->platform_order_id)
                ->orderBy('id', 'asc')
                ->first();

            if (!$primary) {$unmatched++;
                continue;}
            $matched++;

            if ($dry) {
                continue;
            }

            $u = MpShipment::query()->whereKey($primary->id)->update([
                'platform_fee_total' => $inc->platform_fee_total,
                'refund_total' => $inc->refund_total,
                'net_payout_actual' => $inc->net_payout_actual,
                'released_at' => $inc->released_at,
            ]);

            $shipUpdated += (int) $u;
        }

        $this->info("Apply income channel={$channel} store_id={$storeId} dry_run=" . ($dry ? 'YES' : 'NO'));
        $this->line("orders={$orders} matched={$matched} unmatched={$unmatched} shipments_updated={$shipUpdated}");

        return self::SUCCESS;
    }
}
