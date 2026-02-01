<?php

namespace App\Services\Export;

use Closure;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use PhpOffice\PhpSpreadsheet\Spreadsheet;
use PhpOffice\PhpSpreadsheet\Writer\Xlsx;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniversalXlsxExporter
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
            'Content-Type' => 'application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
        ], $headers);

        return response()->streamDownload(function () use (
            $query, $columns, $labels, $rowResolver, $valueResolver, $chunkSize
        ) {
            $spreadsheet = new Spreadsheet();
            $sheet = $spreadsheet->getActiveSheet();
            $sheet->setTitle('Export');

            // Header row
            $colIndex = 1;
            foreach ($columns as $c) {
                $sheet->setCellValueByColumnAndRow($colIndex, 1, $labels[$c] ?? $c);
                $colIndex++;
            }

            $resolver = $rowResolver ?? function ($row) use ($columns, $valueResolver) {
                $line = [];
                foreach ($columns as $col) {
                    $line[] = $valueResolver
                    ? $valueResolver($row, $col)
                    : $this->defaultGet($row, $col);
                }
                return $line;
            };

            $rowNum = 2;
            $chunkFn = method_exists($query, 'chunkById') ? 'chunkById' : 'chunk';

            $query->{$chunkFn}($chunkSize, function ($rows) use ($sheet, $resolver, &$rowNum) {
                foreach ($rows as $row) {
                    $line = $resolver($row);
                    $c = 1;
                    foreach ($line as $v) {
                        $sheet->setCellValueExplicitByColumnAndRow($c, $rowNum, $this->stringify($v));
                        $c++;
                    }
                    $rowNum++;
                }
            });

            // write to temp file then output
            $tmp = tempnam(sys_get_temp_dir(), 'xlsx_');
            $writer = new Xlsx($spreadsheet);
            $writer->save($tmp);

            readfile($tmp);
            @unlink($tmp);

            $spreadsheet->disconnectWorksheets();
            unset($spreadsheet);
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
