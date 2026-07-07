<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use App\Models\Item;
use App\Models\ItemCategory;
use App\Services\Export\UniversalExporter;
use App\Services\Production\ProductionFlowService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class ProductionReportController extends Controller
{
    public function __construct(
        private ProductionFlowService $flow,
        private UniversalExporter $exporter,
    ) {
    }

    /** Laporan Produksi: rekap throughput per SKU + tautan export. */
    public function index(Request $request): View
    {
        $filters = $this->resolveFilters($request);
        $recap = $this->flow->productionRecap($filters);

        $totals = [
            'siap_jahit' => (float) $recap->sum('to_siap_jahit'),
            'sedang_jahit' => (float) $recap->sum('to_sedang_jahit'),
            'wh_prd' => (float) $recap->sum('to_wh_prd'),
            'ready' => (float) $recap->sum('to_ready'),
            'total_qty' => (float) $recap->sum('total_qty'),
            'moves' => (int) $recap->sum('moves'),
        ];

        return view('production.reports.index', [
            'filters' => $filters,
            'recap' => $recap,
            'totals' => $totals,
            'statuses' => $this->flow->statuses(),
            'itemOptions' => Item::where('type', 'finished_good')->orderBy('code')->get(),
            'categoryOptions' => ItemCategory::where('active', 1)->orderBy('name')->get(),
            'operatorOptions' => Employee::whereIn('role', ['sewing', 'operating'])->orderBy('code')->get(),
        ]);
    }

    /** Export detail mutasi produksi (CSV/XLSX) via UniversalExporter. */
    public function export(Request $request): StreamedResponse
    {
        $filters = $this->resolveFilters($request);
        $query = $this->flow->movementsQuery($filters);
        $statuses = $this->flow->statuses();

        $columns = [
            'code', 'date', 'batch', 'sku', 'product', 'category',
            'from_status', 'to_status', 'qty', 'operator', 'deadline', 'user', 'notes',
        ];

        $labels = [
            'code' => 'Kode Mutasi',
            'date' => 'Tanggal',
            'batch' => 'Batch',
            'sku' => 'SKU',
            'product' => 'Produk',
            'category' => 'Kategori',
            'from_status' => 'Dari Status',
            'to_status' => 'Ke Status',
            'qty' => 'Qty',
            'operator' => 'Penjahit',
            'deadline' => 'Deadline',
            'user' => 'User',
            'notes' => 'Catatan',
        ];

        $resolver = function ($row, string $col) use ($statuses): string {
            return match ($col) {
                'code' => (string) $row->code,
                'date' => $row->date ? Carbon::parse($row->date)->format('Y-m-d') : '',
                'batch' => (string) ($row->bundle?->bundle_code ?? ''),
                'sku' => (string) ($row->item?->code ?? ''),
                'product' => (string) ($row->item?->name ?? ''),
                'category' => (string) ($row->item?->category?->name ?? ''),
                'from_status' => (string) ($statuses[$row->from_status]['label'] ?? $row->from_status ?? ''),
                'to_status' => (string) ($statuses[$row->to_status]['label'] ?? $row->to_status ?? ''),
                'qty' => (string) (float) $row->qty,
                'operator' => (string) ($row->operator?->name ?? ''),
                'deadline' => $row->deadline ? Carbon::parse($row->deadline)->format('Y-m-d') : '',
                'user' => (string) ($row->creator?->name ?? ''),
                'notes' => (string) ($row->notes ?? ''),
                default => '',
            };
        };

        $format = strtolower((string) $request->get('format', 'csv'));
        $filename = 'laporan_produksi_' . $filters['date_from'] . '_' . $filters['date_to'];

        return $this->exporter->stream(
            query: $query,
            filenameBase: $filename,
            columns: $columns,
            labels: $labels,
            valueResolver: $resolver,
            format: $format,
        );
    }

    /** Filter bersama index & export. */
    private function resolveFilters(Request $request): array
    {
        $today = Carbon::today();

        return [
            'date_from' => $request->input('date_from') ?: $today->copy()->subDays(29)->toDateString(),
            'date_to' => $request->input('date_to') ?: $today->toDateString(),
            'item_id' => $request->input('item_id') ?: null,
            'category_id' => $request->input('category_id') ?: null,
            'operator_id' => $request->input('operator_id') ?: null,
            'to_status' => $request->input('to_status') ?: null,
        ];
    }
}
