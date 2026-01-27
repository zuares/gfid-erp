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

        // Resolve date range (SQLite, YYYY-MM-DD)
        $today = DB::selectOne("SELECT date('now') AS d")->d;

        if ($dateFrom && !$dateTo) {
            $from = $dateFrom;
            $to = $today;
        } elseif (!$dateFrom && $dateTo) {
            $to = $dateTo;
            $from = DB::selectOne("SELECT date(?, '-' || ? || ' day') AS d", [$to, $days])->d;
        } elseif ($dateFrom && $dateTo) {
            $from = $dateFrom;
            $to = $dateTo;
        } else {
            $from = DB::selectOne("SELECT date('now', '-' || ? || ' day') AS d", [$days])->d;
            $to = $today;
        }

        // Normalize order just in case user swaps them
        if ($from > $to) {
            [$from, $to] = [$to, $from];
        }

        $this->info(
            "Rebuilding daily_item_sales for range: {$from} → {$to}" . ($truncateAll ? ' (TRUNCATE ALL)' : '')
        );

        $now = now();

        DB::transaction(function () use ($from, $to, $truncateAll, $now) {

            // 0) Delete existing rows first
            if ($truncateAll) {
                DB::table('daily_item_sales')->delete();
            } else {
                DB::table('daily_item_sales')
                    ->whereDate('date', '>=', $from)
                    ->whereDate('date', '<=', $to)
                    ->delete();
            }

            /**
             * Aggregate:
             * - shipments posted (status=posted & posted_at not null)
             * - not cancelled
             * - date range filter (whereDate)
             *
             * value_sold:
             * - kalau kamu sudah punya kolom unit_hpp di shipment_lines (hist cost per scan),
             *   ini paling aman & konsisten historis.
             * - jika unit_hpp belum ada, ganti ke join items & pakai items.hpp (current).
             */
            $rows = DB::table('shipment_lines as sl')
                ->join('shipments as s', 's.id', '=', 'sl.shipment_id')
                ->where('s.status', 'posted')
                ->whereNotNull('s.posted_at')
                ->whereNull('s.cancelled_at')
                ->whereDate('s.date', '>=', $from)
                ->whereDate('s.date', '<=', $to)
                ->groupBy(DB::raw('DATE(s.date)'), 'sl.item_id')
                ->selectRaw('
                    DATE(s.date) as date,
                    sl.item_id as item_id,
                    COALESCE(SUM(sl.qty_scanned), 0) as qty_sold,
                    COALESCE(SUM(sl.qty_scanned * COALESCE(sl.unit_hpp, 0)), 0) as value_sold
                ')
                ->get();

            if ($rows->isEmpty()) {
                return;
            }

            $payload = [];
            foreach ($rows as $r) {
                $payload[] = [
                    'date' => $r->date, // YYYY-MM-DD
                    'item_id' => (int) $r->item_id,
                    'qty_sold' => (float) $r->qty_sold,
                    'value_sold' => (float) $r->value_sold,
                    'created_at' => $now,
                    'updated_at' => $now,
                ];
            }

            // Upsert chunks (unique: date+item_id)
            foreach (array_chunk($payload, 800) as $chunk) {
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
