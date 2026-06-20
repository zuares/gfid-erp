<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Services\Accounting\ProductionValueReportService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\View\View;

class ProductionValueReportController extends Controller
{
    public function index(Request $request, ProductionValueReportService $report): View
    {
        abort_unless((auth()->user()->role ?? null) === 'owner', 403, 'Hanya owner yang bisa melihat laporan nilai produksi.');

        $from = $request->filled('from')
            ? Carbon::parse($request->date('from'))->toDateString()
            : now()->startOfMonth()->toDateString();

        $to = $request->filled('to')
            ? Carbon::parse($request->date('to'))->toDateString()
            : now()->toDateString();

        $asOf = $request->filled('as_of')
            ? Carbon::parse($request->date('as_of'))->toDateString()
            : $to;

        $mainAccounts = $report->mainAccountBalances($asOf);
        $cards = $report->productionCards($from, $to);
        $auditRows = $report->stockJournalAudit($from, $to);
        $profitLoss = $report->profitLossSnapshot($from, $to);

        $totals = [
            'card_amount' => (float) $cards->sum('amount'),
            'card_journal' => (float) $cards->sum('journal_amount'),
            'audit_diff' => (float) $auditRows->sum('diff'),
        ];

        return view('accounting.production_value_report.index', compact(
            'from',
            'to',
            'asOf',
            'mainAccounts',
            'cards',
            'auditRows',
            'profitLoss',
            'totals'
        ));
    }
}
