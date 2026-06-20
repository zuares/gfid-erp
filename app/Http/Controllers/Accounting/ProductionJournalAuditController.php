<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ProductionJournalAuditService;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionJournalAuditController extends Controller
{
    public function index(Request $request, ProductionJournalAuditService $audit): View
    {
        abort_unless((auth()->user()->role ?? null) === 'owner', 403, 'Hanya owner yang bisa melihat audit jurnal produksi.');

        $selectedSources = collect((array) $request->input('source', []))
            ->filter()
            ->values()
            ->all();

        $rows = $audit->auditRows($selectedSources ?: null);
        $definitions = $audit->sourceDefinitions();

        $totals = [
            'document_count' => (int) $rows->sum('document_count'),
            'active_journal_count' => (int) $rows->sum('active_journal_count'),
            'missing_count' => (int) $rows->sum('missing_count'),
            'amount' => (float) $rows->sum('amount'),
        ];

        return view('accounting.production_journals.audit', compact(
            'rows',
            'definitions',
            'selectedSources',
            'totals'
        ));
    }
}
