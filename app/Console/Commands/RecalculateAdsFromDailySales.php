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

        // SQLite cutoff date
        $cutoff = DB::selectOne("SELECT date('now','-{$days} day') AS d")->d;

        $this->info("Recalculating ADS from daily_item_sales for last {$days} days (>= {$cutoff})");

        DB::transaction(function () use ($days, $cutoff, $onlyActive) {

            // update window meta
            DB::table('items')->update([
                'avg_daily_sales_window' => $days,
            ]);

            // reset to 0 first (only active optional)
            $resetQuery = DB::table('items');
            if ($onlyActive) {
                $resetQuery->where('active', 1);
            }

            $resetQuery->update([
                'avg_daily_sales' => 0,
                'avg_daily_sales_updated_at' => now(),
            ]);

            // aggregate per item from daily_item_sales
            $agg = DB::table('daily_item_sales')
                ->selectRaw('item_id, SUM(qty_sold) as qty_sum')
                ->where('date', '>=', $cutoff)
                ->groupBy('item_id')
                ->get();

            if ($agg->isEmpty()) {
                return;
            }

            // update per item in chunks (SQLite friendly)
            $chunks = $agg->chunk(800);

            foreach ($chunks as $chunk) {
                foreach ($chunk as $r) {
                    $ads = ((float) ($r->qty_sum ?? 0)) / $days;

                    DB::table('items')
                        ->where('id', (int) $r->item_id)
                        ->when($onlyActive, fn($q) => $q->where('active', 1))
                        ->update([
                            'avg_daily_sales' => $ads,
                            'avg_daily_sales_window' => $days,
                            'avg_daily_sales_updated_at' => now(),
                        ]);
                }
            }
        });

        $top = DB::table('items')
            ->select('code', 'avg_daily_sales', 'avg_daily_sales_window')
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
