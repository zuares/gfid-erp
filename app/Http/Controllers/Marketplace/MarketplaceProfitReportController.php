<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceProfitReportService;
use Illuminate\Http\Request;

class MarketplaceProfitReportController extends Controller
{
    public function index(Request $request, MarketplaceProfitReportService $reportService)
    {
        $filters = $this->filters($request);
        $report = $reportService->report($filters);
        $stores = Store::with('channel')->where('is_active', true)->orderBy('name')->get();

        return view('marketplace.reports.profit', compact('report', 'stores'));
    }

    public function export(Request $request, MarketplaceProfitReportService $reportService)
    {
        $report = $reportService->report($this->filters($request));
        $filename = 'marketplace-profit-' . now()->format('Ymd-His') . '.csv';

        return response()->streamDownload(function () use ($report) {
            $handle = fopen('php://output', 'w');
            fputcsv($handle, [
                'Order', 'Toko', 'Channel', 'Tanggal Order', 'Tanggal Cair', 'Omzet',
                'Diskon Seller', 'Fee Marketplace', 'Refund/Adjustment', 'Payout',
                'HPP', 'Iklan', 'Laba Kotor', 'Laba Operasional', 'Margin %',
            ]);

            foreach ($report['orders'] as $row) {
                fputcsv($handle, [
                    $row['channel_order_id'],
                    $row['store_name'],
                    $row['channel'],
                    $row['ordered_at'],
                    $row['settlement_time'] ?: 'Belum cair',
                    $row['gross_sales'],
                    $row['seller_discount'],
                    $row['marketplace_fees'],
                    $row['refund'],
                    $row['payout'],
                    $row['hpp'],
                    $row['ad_cost'],
                    $row['gross_profit'],
                    $row['operating_profit'],
                    $row['margin_pct'],
                ]);
            }

            fclose($handle);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    private function filters(Request $request): array
    {
        $defaultFrom = now()->subDays(29)->toDateString();
        $defaultTo = now()->toDateString();

        return $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_basis' => ['nullable', 'in:ordered_at,settlement_time'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]) + [
            'date_basis' => $request->input('date_basis', 'ordered_at'),
            'date_from' => $request->input('date_from', $defaultFrom),
            'date_to' => $request->input('date_to', $defaultTo),
        ];
    }
}
