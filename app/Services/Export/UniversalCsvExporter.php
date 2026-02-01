<?php

namespace App\Services\Export;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniversalCsvExporter
{
    public function stream(
        Builder | QueryBuilder $query,
        string $filename,
        array $columns,
        ?array $labels = null,
        ?Closure $rowResolver = null,
        ?Closure $valueResolver = null,
        int $chunkSize = 1000,
        array $headers = []
    ): StreamedResponse {
        $columns = $this->sanitizeColumns($columns);
        if (empty($columns)) {
            $columns = ['id'];
        }

        $labels = $labels ? $this->sanitizeLabels($labels) : [];

        $headers = array_merge([
            'Content-Type' => 'text/csv; charset=UTF-8',
        ], $headers);

        return response()->streamDownload(function () use (
            $query, $columns, $labels, $rowResolver, $valueResolver, $chunkSize
        ) {
            $out = fopen('php://output', 'w');

            // BOM untuk Excel (kalau mau)
            fwrite($out, "\xEF\xBB\xBF");

            fputcsv($out, array_map(fn($c) => $labels[$c] ?? $c, $columns));

            $resolver = $rowResolver ?? function ($row) use ($columns, $valueResolver) {
                $line = [];
                foreach ($columns as $col) {
                    $line[] = $valueResolver
                    ? $valueResolver($row, $col)
                    : $this->defaultGet($row, $col);
                }
                return $line;
            };

            $chunkFn = method_exists($query, 'chunkById') ? 'chunkById' : 'chunk';

            $query->{$chunkFn}($chunkSize, function ($rows) use ($out, $resolver) {
                foreach ($rows as $row) {
                    $line = array_map([$this, 'stringify'], $resolver($row));
                    fputcsv($out, $line);
                }
            });

            fclose($out);
        }, $filename, $headers);
    }

    protected function defaultGet($row, string $col): mixed
    {
        if (str_starts_with($col, 'raw:')) {
            $key = substr($col, 4);
            $raw = $this->asArray(data_get($row, 'raw_line'));
            return data_get($raw, $key);
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

    protected function sanitizeLabels(array $labels): array
    {
        $out = [];
        foreach ($labels as $k => $v) {
            $k = trim((string) $k);
            if ($k === '') {
                continue;
            }

            $out[$k] = trim((string) $v);
        }
        return $out;
    }

    protected function asArray($v): array
    {
        if (is_array($v)) {
            return $v;
        }

        if (is_string($v)) {
            $d = json_decode($v, true);
            return is_array($d) ? $d : [];
        }
        return [];
    }

    protected function stringify($v): string
    {
        if ($v === null) {
            return '';
        }

        if (is_bool($v)) {
            return $v ? '1' : '0';
        }

        if (is_scalar($v)) {
            return (string) $v;
        }

        if (is_array($v)) {
            return json_encode($v, JSON_UNESCAPED_UNICODE);
        }

        if (is_object($v) && method_exists($v, '__toString')) {
            return (string) $v;
        }

        return json_encode($v, JSON_UNESCAPED_UNICODE);
    }
}
