<?php

namespace App\Http\Controllers\Production;

use App\Http\Controllers\Controller;
use App\Services\Production\ProductionLogService;
use Illuminate\Http\Request;
use Illuminate\View\View;

/**
 * Log Produksi — timeline read-only gabungan ledger stok + audit event.
 */
class ProductionLogController extends Controller
{
    public function __construct(private ProductionLogService $log)
    {
    }

    public function index(Request $request): View
    {
        $filters = [
            'date_from' => $request->input('date_from'),
            'date_to'   => $request->input('date_to'),
            'source'    => $request->input('source'),
            'q'         => $request->input('q'),
        ];

        $rows = $this->log->timeline($filters, 300);

        return view('production.log.index', [
            'rows'       => $rows,
            'filters'    => $filters,
            'sourceOpts' => ProductionLogService::SOURCE_LABELS,
            'log'        => $this->log,
        ]);
    }
}
