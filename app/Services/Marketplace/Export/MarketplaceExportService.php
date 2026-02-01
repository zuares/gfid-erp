<?php

namespace App\Services\Marketplace\Export;

use App\Models\MpShipment;
use App\Services\Export\UniversalExporter;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;

class MarketplaceExportService
{
    public function __construct(
        protected UniversalExporter $exporter
    ) {}

    public function export(Request $request): StreamedResponse
    {
        $tz = 'Asia/Jakarta';

        // ===== filters (reuse index logic) =====
        $filters = [
            'q' => trim((string) $request->get('q', '')),
            'channel' => (string) $request->get('channel', ''),
            'store_id' => (string) $request->get('store_id', ''),
            'status' => (string) $request->get('status', ''),
            'from' => (string) $request->get('from', ''),
            'to' => (string) $request->get('to', ''),
        ];

        $today = Carbon::now($tz)->startOfDay();
        $from = $filters['from'] !== ''
        ? Carbon::parse($filters['from'], $tz)->startOfDay()
        : (clone $today)->subDays(6);

        $to = $filters['to'] !== ''
        ? Carbon::parse($filters['to'], $tz)->endOfDay()
        : (clone $today)->endOfDay();

        // ===== columns =====
        $cols = $this->sanitizeCols((array) $request->get('cols', []));
        $rawCols = $this->sanitizeCols((array) $request->get('raw_cols', []));

        if (empty($cols) && empty($rawCols)) {
            $cols = ['platform_order_id', 'tracking_no', 'status_norm', 'order_created_at', 'total_qty', 'grand_total'];
        }

        $columns = array_merge(
            $cols,
            array_map(fn($c) => "raw:{$c}", $rawCols)
        );

        // ===== labels =====
        $labels = $this->buildLabels($columns);

        // ===== query =====
        $query = MpShipment::query()
            ->with(['store:id,name'])
            ->whereBetween('order_created_at', [$from, $to])
            ->when($filters['channel'] !== '', fn($q) => $q->where('channel', $filters['channel']))
            ->when($filters['store_id'] !== '', fn($q) => $q->where('store_id', (int) $filters['store_id']))
            ->when($filters['status'] !== '', fn($q) => $q->where('status_norm', $filters['status']))
            ->when($filters['q'] !== '', function ($q) use ($filters) {
                $qq = $filters['q'];
                $q->where(function ($w) use ($qq) {
                    $w->where('platform_order_id', 'like', "%{$qq}%")
                        ->orWhere('platform_shipment_id', 'like', "%{$qq}%")
                        ->orWhere('tracking_no', 'like', "%{$qq}%");
                });
            })
            ->orderByDesc('order_created_at')
            ->orderByDesc('id');

        // ===== value resolver =====
        $resolver = fn($row, string $col) =>
        $this->resolveValue($row, $col, $tz);

        $format = (string) $request->get('format', 'csv');
        $filename = 'marketplace_shipments_' . $from->format('Ymd') . '_' . $to->format('Ymd');

        return $this->exporter->stream(
            query: $query,
            filenameBase: $filename,
            columns: $columns,
            labels: $labels,
            valueResolver: $resolver,
            format: $format
        );
    }

    /* ================================
     * Helpers
     * ================================ */

    private function sanitizeCols(array $cols): array
    {
        return array_values(array_filter($cols, fn($c) =>
            is_string($c) && preg_match('/^[A-Za-z0-9_\.\-]+$/', $c)
        ));
    }

    private function buildLabels(array $columns): array
    {
        $map = [
            'platform_order_id' => 'Order ID',
            'platform_shipment_id' => 'Shipment ID',
            'channel' => 'Channel',
            'store' => 'Store',
            'tracking_no' => 'Tracking No',
            'status_norm' => 'Status',
            'order_created_at' => 'Order Date',
            'shipped_at' => 'Shipped At',
            'delivered_at' => 'Delivered At',
            'total_qty' => 'Qty',
            'grand_total' => 'Grand Total',
        ];

        $labels = [];
        foreach ($columns as $c) {
            $labels[$c] = str_starts_with($c, 'raw:')
            ? 'RAW: ' . substr($c, 4)
            : ($map[$c] ?? strtoupper(str_replace('_', ' ', $c)));
        }
        return $labels;
    }

    private function resolveValue($row, string $column, string $tz): string
    {
        if (str_starts_with($column, 'raw:')) {
            $key = substr($column, 4);
            $raw = is_string($row->raw_line)
            ? json_decode($row->raw_line, true) ?: []
            : ($row->raw_line ?? []);
            return (string) data_get($raw, $key, '');
        }

        return match ($column) {
            'store' => (string) ($row->store->name ?? ''),
            'order_created_at',
            'shipped_at',
            'delivered_at' => $row->$column
            ? Carbon::parse($row->$column)->timezone($tz)->format('Y-m-d H:i')
            : '',
            default => (string) data_get($row, $column, ''),
        };
    }
}
