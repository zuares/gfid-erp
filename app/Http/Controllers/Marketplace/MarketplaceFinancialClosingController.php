<?php

namespace App\Http\Controllers\Marketplace;

use App\Http\Controllers\Controller;
use App\Models\MarketplaceFinancialClosing;
use App\Models\Store;
use App\Services\Marketplace\MarketplaceFinancialClosingService;
use Illuminate\Http\Request;

class MarketplaceFinancialClosingController extends Controller
{
    public function index(Request $request, MarketplaceFinancialClosingService $closingService)
    {
        $filters = $this->filters($request);
        $audit = $closingService->audit($filters);
        $stores = Store::with('channel')->where('is_active', true)->orderBy('name')->get();

        return view('marketplace.reports.financial_closing', compact('audit', 'stores'));
    }

    public function close(Request $request, MarketplaceFinancialClosingService $closingService)
    {
        $filters = $this->filters($request);
        $closingService->close($filters, $request->user()?->id);

        return redirect()
            ->route('marketplace.reports.financial-closing', $filters)
            ->with('status', 'Periode marketplace berhasil di-close. Posting pada scope overlap sekarang dikunci.');
    }

    public function reopen(Request $request, MarketplaceFinancialClosing $closing, MarketplaceFinancialClosingService $closingService)
    {
        $data = $request->validate([
            'reason' => ['required', 'string', 'min:3', 'max:1000'],
        ]);

        $closingService->reopen($closing, $data['reason'], $request->user()?->id);

        return redirect()
            ->route('marketplace.reports.financial-closing', $closingService->filtersForClosing($closing))
            ->with('status', 'Periode berhasil di-reopen dan alasan tersimpan di audit log.');
    }

    private function filters(Request $request): array
    {
        $defaultFrom = now()->subDays(29)->toDateString();
        $defaultTo = now()->toDateString();

        return $request->validate([
            'store_id' => ['nullable', 'integer', 'exists:stores,id'],
            'date_basis' => ['nullable', 'in:ordered_at,settlement_time'],
            'date_from' => ['nullable', 'date'],
            'date_to' => ['nullable', 'date', 'after_or_equal:date_from'],
        ]) + [
            'date_basis' => $request->input('date_basis', 'ordered_at'),
            'date_from' => $request->input('date_from', $defaultFrom),
            'date_to' => $request->input('date_to', $defaultTo),
        ];
    }
}
