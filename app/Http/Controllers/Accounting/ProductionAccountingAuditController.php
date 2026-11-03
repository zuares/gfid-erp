<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ProductionAccountingAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionAccountingAuditController extends Controller
{
    public function index(Request $request, ProductionAccountingAuditService $audit): View
    {
        abort_unless((auth()->user()->role ?? null) === 'owner', 403, 'Hanya owner yang bisa melihat audit akuntansi produksi.');

        $data = $request->validate([
            'from' => ['nullable', 'date'],
            'to' => ['nullable', 'date', 'after_or_equal:from'],
            'stage' => ['nullable', 'array'],
            'stage.*' => ['string'],
        ]);

        $selectedStages = collect($data['stage'] ?? [])->filter()->values()->all();
        $report = $audit->report(
            $data['from'] ?? null,
            $data['to'] ?? null,
            $selectedStages ?: null,
        );

        return view('accounting.production_journals.accounting_audit', [
            'report' => $report,
            'definitions' => $audit->definitionsFor(),
            'filters' => [
                'from' => $data['from'] ?? '',
                'to' => $data['to'] ?? '',
                'stage' => $selectedStages,
            ],
        ]);
    }
}
