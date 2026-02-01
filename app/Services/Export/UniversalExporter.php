<?php

namespace App\Services\Export;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Query\Builder as QueryBuilder;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UniversalExporter
{
    public function __construct(
        protected UniversalCsvExporter $csv,
        protected UniversalXlsxExporter $xlsx,
    ) {}

    public function stream(
        Builder | QueryBuilder $query,
        string $filenameBase,
        array $columns,
        ?array $labels = null,
        ? callable $rowResolver = null,
        ? callable $valueResolver = null,
        string $format = 'csv',
        int $chunkSize = 1000,
        array $headers = []
    ) : StreamedResponse {
        $format = strtolower(trim($format));

        return match ($format) {
            'xlsx' => $this->xlsx->stream(
                query : $query,
                filename: $filenameBase . '.xlsx',
                columns: $columns,
                labels: $labels,
                rowResolver: $rowResolver,
                valueResolver: $valueResolver,
                chunkSize: $chunkSize,
                headers: $headers
            ),
            default => $this->csv->stream(
                query: $query,
                filename: $filenameBase . '.csv',
                columns: $columns,
                labels: $labels,
                rowResolver: $rowResolver,
                valueResolver: $valueResolver,
                chunkSize: $chunkSize,
                headers: $headers
            ),
        };
    }
}
