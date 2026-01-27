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
     * - bisa isi salah satu saja, sisanya auto
     */
    protected $signature = 'sales:rebuild-daily-item-sales
        {--days=90 : Rolling window days (default 90)}
        {--date-from= : Start date (YYYY-MM-DD)}
        {--date-to= : End date (YYYY-MM-DD)}
        {--truncate=0 : Delete ALL rows before rebuild (dangerous)}
    ';

    protected $description = 'Rebuild daily_item_sales from posted shipments and shipment_lines (qty_scanned) using shipments.date (date-only).';

    public function handle(): int
    {
        $days = max(1, (int) $this->option('days'));
        $dateFrom = $this->option('date-from');
        $dateTo = $this->option('date-to');
        $truncateAll = ((int) $this->option('truncate')) === 1;

        // Resolve date range (flexible)
        // If user provides only one side, fill the other
        $today = DB::selectOne("SELECT date('now') AS d")->d;

        if ($dateFrom && !$dateTo) {
            $from = $dateFrom;
            $to = $today;
        } elseif (!$dateFrom && $dateTo) {
            $to = $dateTo;
            $from = DB::selectOne("SELECT date('{$to}','-{$days} day') AS d")->d;
        } elseif ($dateFrom && $dateTo) {
            $from = $dateFrom;
            $to = $dateTo;
        } else {
            $from = DB::selectOne("SELECT date('now','-{$days} day') AS d")->d;
            $to = $today;
        }

        $this->info("Rebuilding daily_item_sales for range: {$from} → {$to}" . ($truncateAll ? ' (TRUNCATE ALL)' : ''));

        DB::transaction(function () use ($from, $to, $truncateAll) {

            if ($truncateAll) {
                // SQLite-safe "truncate": delete all rows
                DB::table('daily_item_sales')->delete();
            } else {
                // Delete only affected date range
                DB::table('daily_item_sales')
                    ->whereDate('date', '>=', $from)
                    ->whereDate('date', '<=', $to)
                    ->delete();
            }

            /**
             * Aggregate from shipments + shipment_lines
             *
             * Filters:
             * - shipments.status = posted
             * - posted_at IS NOT NULL  (lebih valid)
             * - cancelled_at IS NULL
             * - ship date within range (date-only)
             *
             * Note:
             * - DATE(s.date) supaya kalau s.date DATETIME, tetap grup per tanggal
             */
            $rows = DB::table('shipment_lines as sl')
                ->join('shipments as s', 's.id', '=', 'sl.shipment_id')
                ->join('items as i', 'i.id', '=', 'sl.item_id')
                ->where('s.status', 'posted')
                ->whereNotNull('s.posted_at')
                ->whereNull('s.cancelled_at')
                ->whereDate('s.date', '>=', $from)
                ->whereDate('s.date', '<=', $to)
                ->groupBy(DB::raw('DATE(s.date)'), 'sl.item_id', 'i.hpp')
                ->selectRaw('
                    DATE(s.date) as date,
                    sl.item_id as item_id,
                    COALESCE(SUM(sl.qty_scanned), 0) as qty_sold,
                    COALESCE(SUM(sl.qty_scanned), 0) * COALESCE(i.hpp, 0) as value_sold
                ')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            $now = now();

            $payload = $rows->map(function ($r) use ($now) {
                return [
                    'date' => $r->date, // YYYY-MM-DD
                    'item_id' => (int) $r->item_id,
                    'qty_sold' => (float) $r->qty_sold,
                    'value_sold' => (float) $r->value_sold,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            })->all();

            // Upsert chunks (unique key: date+item_id)
            $chunks = array_chunk($payload, 800);
            foreach ($chunks as $chunk) {
                DB::table('daily_item_sales')->upsert(
                    $chunk,
                    ['date', 'item_id'],
                    ['qty_sold', 'value_sold', 'updated_at']
                );
            }
        });

        $count = DB::table('daily_item_sales')
            ->whereDate('date', '>=', $from)
            ->whereDate('date', '<=', $to)
            ->count();

        $this->info("OK: daily_item_sales rebuilt. Rows in range: {$count}");

        return self::SUCCESS;
    }
}
