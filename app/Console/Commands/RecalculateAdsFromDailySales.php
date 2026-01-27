<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RecalculateAdsFromDailySales extends Command
{
    protected $signature = 'inventory:recalc-ads-from-daily
        {--days=30 : Rolling window days (default 30)}
        {--only-active=1 : Only items.active=1}
    ';

    protected $description = 'Recalculate items.avg_daily_sales from daily_item_sales (rolling window).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $onlyActive = ((int) $this->option('only-active')) === 1;

        // SQLite cutoff date (YYYY-MM-DD)
        $cutoff = DB::selectOne("SELECT date('now', '-' || ? || ' day') AS d", [$days])->d;

        $this->info(
            "Recalculating ADS from daily_item_sales for last {$days} days (>= {$cutoff})"
            . ($onlyActive ? " [only active]" : " [all items]")
        );

        $now = now();

        DB::transaction(function () use ($days, $cutoff, $onlyActive, $now) {

            // 1) update window meta
            $metaQ = DB::table('items');
            if ($onlyActive) {
                $metaQ->where('active', 1);
            }
            $metaQ->update([
                'avg_daily_sales_window' => $days,
            ]);

            // 2) reset ADS first (scope-respecting)
            $resetQ = DB::table('items');
            if ($onlyActive) {
                $resetQ->where('active', 1);
            }
            $resetQ->update([
                'avg_daily_sales' => 0,
                'avg_daily_sales_window' => $days,
                'avg_daily_sales_updated_at' => $now,
            ]);

            // 3) aggregate qty in window
            $agg = DB::table('daily_item_sales')
                ->selectRaw('item_id, SUM(qty_sold) as qty_sum')
                ->whereDate('date', '>=', $cutoff)
                ->groupBy('item_id')
                ->get();

            if ($agg->isEmpty()) {
                return;
            }

            // 4) update per item (SQLite friendly)
            foreach ($agg->chunk(800) as $chunk) {
                foreach ($chunk as $r) {
                    $itemId = (int) $r->item_id;
                    $qtySum = (float) ($r->qty_sum ?? 0);
                    $ads = $qtySum / $days;

                    $q = DB::table('items')->where('id', $itemId);
                    if ($onlyActive) {
                        $q->where('active', 1);
                    }

                    $q->update([
                        'avg_daily_sales' => $ads,
                        'avg_daily_sales_window' => $days,
                        'avg_daily_sales_updated_at' => $now,
                    ]);
                }
            }
        });

        $top = DB::table('items')
            ->select('code', 'avg_daily_sales', 'avg_daily_sales_window')
            ->when($onlyActive, fn($q) => $q->where('active', 1))
            ->orderByDesc('avg_daily_sales')
            ->limit(5)
            ->get();

        $this->info("OK: ADS updated. Top 5:");
        foreach ($top as $t) {
            $this->line("- {$t->code} : {$t->avg_daily_sales} /day (window {$t->avg_daily_sales_window})");
        }

        return self::SUCCESS;
    }
}
