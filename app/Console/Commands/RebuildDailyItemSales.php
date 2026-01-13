<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class RebuildDailyItemSales extends Command
{
    /**
     * days:
     * - rebuild data untuk N hari terakhir (rolling)
     * - default 90 hari biar aman untuk laporan
     *
     * date-from / date-to optional:
     * - kalau mau rebuild range tertentu
     */
    protected $signature = 'sales:rebuild-daily-item-sales
        {--days=90 : Rolling window days (default 90)}
        {--date-from= : Start date (YYYY-MM-DD)}
        {--date-to= : End date (YYYY-MM-DD)}
        {--truncate=0 : Truncate table before rebuild (dangerous for large)}
    ';

    protected $description = 'Rebuild daily_item_sales from posted shipments and shipment_lines (qty_scanned) using shipments.date.';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $truncate = ((int) $this->option('truncate')) === 1;

        // Resolve date range
        // - If date-from/to provided: use that
        // - else: last N days up to today
        if ($dateFrom && $dateTo) {
            $from = $dateFrom;
            $to = $dateTo;
        } else {
            // SQLite date arithmetic
            $from = DB::selectOne("SELECT date('now','-{$days} day') AS d")->d;
            $to = DB::selectOne("SELECT date('now') AS d")->d;
        }

        $this->info("Rebuilding daily_item_sales for range: {$from} → {$to}");

        DB::transaction(function () use ($from, $to, $truncate) {
            if ($truncate) {
                DB::table('daily_item_sales')->truncate();
            } else {
                // Delete only the affected date range (faster & safe)
                DB::table('daily_item_sales')
                    ->whereBetween('date', [$from, $to])
                    ->delete();
            }

            /**
             * Aggregate from shipments + shipment_lines
             * Filters:
             * - shipments.status = posted
             * - cancelled_at IS NULL
             * - date between range
             *
             * value_sold = qty_sold * items.hpp
             */
            $rows = DB::table('shipment_lines as sl')
                ->join('shipments as s', 's.id', '=', 'sl.shipment_id')
                ->join('items as i', 'i.id', '=', 'sl.item_id')
                ->where('s.status', 'posted')
                ->whereNull('s.cancelled_at')
                ->whereBetween('s.date', [$from, $to])
                ->groupBy('s.date', 'sl.item_id', 'i.hpp')
                ->selectRaw('
                    s.date as date,
                    sl.item_id as item_id,
                    COALESCE(SUM(sl.qty_scanned), 0) as qty_sold,
                    COALESCE(SUM(sl.qty_scanned), 0) * COALESCE(i.hpp, 0) as value_sold
                ')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            // Insert chunks to be safe
            $payload = $rows->map(function ($r) {
                return [
                    'date' => $r->date,
                    'item_id' => (int) $r->item_id,
                    'qty_sold' => (float) $r->qty_sold,
                    'value_sold' => (float) $r->value_sold,
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            })->all();

            // Use upsert if available (Laravel 8+)
            // Unique key: (date, item_id)
            // Update: qty_sold, value_sold, updated_at
            $chunks = array_chunk($payload, 1000);
            foreach ($chunks as $chunk) {
                DB::table('daily_item_sales')->upsert(
                    $chunk,
                    ['date', 'item_id'],
                    ['qty_sold', 'value_sold', 'updated_at']
                );
            }
        });

        $count = DB::table('daily_item_sales')
            ->whereBetween('date', [$from, $to])
            ->count();

        $this->info("OK: daily_item_sales rebuilt. Rows in range: {$count}");
        return self::SUCCESS;
    }
}
