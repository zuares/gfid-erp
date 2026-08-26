<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Services\Marketplace\MarketplaceProfitOverviewService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceProfitOverviewController extends Controller
{
    public function __invoke(
        Request $request,
        MarketplaceProfitOverviewService $overview,
    ): JsonResponse|StreamedResponse {
        $filters = $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'status' => ['nullable', 'string', 'max:50'],
            'settlement_status' => ['nullable', 'in:cair,belum_cair'],
            'hpp_status' => ['nullable', 'in:mapped,empty'],
            'sort' => ['nullable', 'in:margin_asc,margin_desc,profit_asc,profit_desc,date_asc,date_desc'],
            'search' => ['nullable', 'string', 'max:100'],
            'order_date_from' => ['nullable', 'date'],
            'order_date_to' => ['nullable', 'date', 'after_or_equal:order_date_from'],
            'settlement_date_from' => ['nullable', 'date'],
            'settlement_date_to' => ['nullable', 'date', 'after_or_equal:settlement_date_from'],
            'page' => ['nullable', 'integer', 'min:1'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
            'export' => ['nullable', 'in:csv'],
        ]);

        $report = $overview->report($filters);

        if (($filters['export'] ?? null) === 'csv') {
            return $this->csv($report);
        }

        $page = (int) ($filters['page'] ?? 1);
        $perPage = (int) ($filters['per_page'] ?? 50);
        $paginator = new LengthAwarePaginator(
            $report['rows']->forPage($page, $perPage)->values(),
            $report['rows']->count(),
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()],
        );

        return response()->json([
            'paginator' => $paginator,
            'meta' => $report['meta'],
        ]);
    }

    /**
     * @param  array{rows: \Illuminate\Support\Collection<int, array<string, mixed>>, meta: array<string, mixed>}  $report
     */
    private function csv(array $report): StreamedResponse
    {
        $filename = 'profit-marketplace-'.now()->format('Ymd-His').'.csv';

        return response()->streamDownload(function () use ($report) {
            $file = fopen('php://output', 'w');
            fwrite($file, "\xEF\xBB\xBF");
            fputcsv($file, [
                'Order SN',
                'Toko',
                'Channel',
                'Status Order',
                'Status Dana',
                'Sumber Dana',
                'Tgl Order',
                'Tgl Cair Aktual',
                'Estimasi Tgl Cair',
                'Harga Jual',
                'Dana Aktual/Estimasi',
                'HPP',
                'Status HPP',
                'Profit Kontribusi',
                'Margin %',
            ]);

            foreach ($report['rows'] as $row) {
                fputcsv($file, [
                    $row['channel_order_id'],
                    data_get($row, 'store.name'),
                    strtoupper((string) data_get($row, 'store.channel')),
                    data_get($row, 'order.order_status'),
                    $row['income_type'] === 'actual' ? 'Cair' : 'Belum Cair',
                    $row['income_source_label'],
                    data_get($row, 'order.ordered_at'),
                    $row['settlement_time'],
                    $row['estimated_payout_at'],
                    $row['gross_sales'],
                    $row['income_available'] ? $row['final_income'] : '',
                    $row['hpp_total'],
                    $row['hpp_mapped'] ? 'Lengkap' : 'Belum lengkap',
                    $row['profit_eligible'] ? $row['profit_contribution'] : '',
                    $row['profit_eligible'] ? $row['margin_pct'] : '',
                ]);
            }

            fputcsv($file, []);
            fputcsv($file, ['RINGKASAN FILTER']);
            fputcsv($file, ['Order ditemukan', $report['meta']['kpi_count']]);
            fputcsv($file, ['Order masuk KPI', $report['meta']['kpi_ready_count']]);
            fputcsv($file, ['Order dikecualikan', $report['meta']['kpi_excluded_count']]);
            fputcsv($file, ['Profit kontribusi', $report['meta']['kpi_profit_contribution']]);
            fputcsv($file, [
                'Iklan + PPN periode',
                $report['meta']['kpi_ads_applicable']
                    ? ($report['meta']['kpi_ads_total'] ?? 0)
                    : 'Tidak dialokasikan untuk filter subset',
            ]);
            fputcsv($file, [
                'Profit setelah iklan',
                $report['meta']['kpi_ads_applicable']
                    ? ($report['meta']['kpi_profit_final'] ?? '')
                    : 'Tidak dihitung untuk filter subset',
            ]);
            fclose($file);
        }, $filename, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }
}
