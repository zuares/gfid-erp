<?php

namespace App\Services\Export;

use Barryvdh\DomPDF\Facade\Pdf;
use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniversalPdfExporter
{
    /**
     * PDF cocok untuk <= ±2000 rows
     */
    public function stream(
        Builder | QueryBuilder $query,
        string $filename,
        array $columns,
        string $view, // blade view khusus pdf
        ?array $labels = null,
        ?Closure $valueResolver = null,
        int $limit = 2000,
        array $paper = ['a4', 'landscape']
    ): StreamedResponse {
        $columns = $this->sanitizeColumns($columns);
        if (empty($columns)) {
            $columns = ['id'];
        }

        $labels = $labels ?? [];

        $rows = $query->limit($limit)->get();

        $data = [
            'rows' => $rows,
            'columns' => $columns,
            'labels' => $labels,
            'value' => function ($row, string $col) use ($valueResolver) {
                return $valueResolver
                ? $valueResolver($row, $col)
                : $this->defaultGet($row, $col);
            },
        ];

        $pdf = Pdf::loadView($view, $data)->setPaper(...$paper);

        return response()->streamDownload(
            fn() => print($pdf->output()),
            $filename,
            ['Content-Type' => 'application/pdf']
        );
    }

    protected function defaultGet($row, string $col): mixed
    {
        if (str_starts_with($col, 'raw:')) {
            $key = substr($col, 4);
            $raw = data_get($row, 'raw_line');
            if (is_string($raw)) {
                $raw = json_decode($raw, true);
            }

            return data_get($raw ?? [], $key);
        }

        if ($col === 'store') {
            return data_get($row, 'store.name');
        }

        return data_get($row, $col);
    }

    protected function sanitizeColumns(array $columns): array
    {
        $out = [];
        foreach ($columns as $c) {
            $c = trim((string) $c);
            if ($c === '') {
                continue;
            }

            if (!preg_match('/^[A-Za-z0-9_\.\-:]+$/', $c)) {
                continue;
            }

            $out[] = $c;
        }
        return array_values(array_unique($out));
    }
}
