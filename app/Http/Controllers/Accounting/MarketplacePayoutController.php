<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\MarketplacePayout;
use App\Services\Accounting\MarketplacePayoutService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class MarketplacePayoutController extends Controller
{
    public function index(Request $request)
    {
        $q = MarketplacePayout::query()
            ->with(['bankAccount', 'journal'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $q->where('status', $request->string('status')->toString());
        }

        if ($request->filled('from')) {
            $q->whereDate('date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $q->whereDate('date', '<=', $request->date('to'));
        }

        if ($request->filled('marketplace')) {
            $q->where('marketplace_name', $request->string('marketplace')->toString());
        }

        $summaryRows = (clone $q)
            ->withoutEagerLoads()
            ->reorder()
            ->selectRaw('status, COUNT(*) as total_docs, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $summary = [
            'total_docs'     => (int) $summaryRows->sum('total_docs'),
            'total_amount'   => (float) $summaryRows->sum('total_amount'),
            'posted_amount'  => (float) ($summaryRows->get('posted')->total_amount ?? 0),
            'draft_docs'     => (int) ($summaryRows->get('draft')->total_docs ?? 0),
            'void_docs'      => (int) ($summaryRows->get('void')->total_docs ?? 0),
        ];

        $payouts = $q->paginate(25)->withQueryString();
        $bankAccounts = $this->bankAccountOptions();
        $marketplaceNames = MarketplacePayout::select('marketplace_name')
            ->distinct()->orderBy('marketplace_name')->pluck('marketplace_name');

        return view('accounting.marketplace_payouts.index', compact(
            'payouts', 'summary', 'bankAccounts', 'marketplaceNames'
        ));
    }

    public function create()
    {
        $bankAccounts = $this->bankAccountOptions();

        return view('accounting.marketplace_payouts.create', compact('bankAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date'             => ['required', 'date'],
            'marketplace_name' => ['required', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'bank_account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        if (! $this->isBankAccount((int) $data['bank_account_id'])) {
            return back()->withInput()
                ->with('status', 'error')
                ->with('message', 'Akun tujuan harus akun kas/bank yang aktif.');
        }

        $data['status']     = 'draft';
        $data['created_by'] = Auth::id();

        $payout = MarketplacePayout::create($data);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $payout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan marketplace tersimpan (DRAFT).');
    }

    public function show(MarketplacePayout $marketplacePayout)
    {
        $marketplacePayout->load(['bankAccount', 'journal.lines.account']);

        return view('accounting.marketplace_payouts.show', compact('marketplacePayout'));
    }

    public function edit(MarketplacePayout $marketplacePayout)
    {
        if ($marketplacePayout->status !== 'draft') {
            return redirect()
                ->route('accounting.marketplace-payouts.show', $marketplacePayout)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        $bankAccounts = $this->bankAccountOptions();

        return view('accounting.marketplace_payouts.edit', compact('marketplacePayout', 'bankAccounts'));
    }

    public function update(Request $request, MarketplacePayout $marketplacePayout)
    {
        if ($marketplacePayout->status !== 'draft') {
            return redirect()
                ->route('accounting.marketplace-payouts.show', $marketplacePayout)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }

        $data = $request->validate([
            'date'             => ['required', 'date'],
            'marketplace_name' => ['required', 'string', 'max:100'],
            'amount'           => ['required', 'numeric', 'min:0.01'],
            'bank_account_id'  => ['required', 'integer', 'exists:accounts,id'],
            'reference'        => ['nullable', 'string', 'max:100'],
            'description'      => ['nullable', 'string', 'max:255'],
            'notes'            => ['nullable', 'string'],
        ]);

        if (! $this->isBankAccount((int) $data['bank_account_id'])) {
            return back()->withInput()
                ->with('status', 'error')
                ->with('message', 'Akun tujuan harus akun kas/bank yang aktif.');
        }

        $marketplacePayout->update($data);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $marketplacePayout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan marketplace berhasil diupdate.');
    }

    public function destroy(MarketplacePayout $marketplacePayout)
    {
        if ($marketplacePayout->status !== 'draft') {
            return redirect()
                ->route('accounting.marketplace-payouts.show', $marketplacePayout)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }

        $marketplacePayout->delete();

        return redirect()
            ->route('accounting.marketplace-payouts.index')
            ->with('status', 'ok')
            ->with('message', 'Penerimaan DRAFT berhasil dihapus.');
    }

    public function post(MarketplacePayout $marketplacePayout, MarketplacePayoutService $service)
    {
        $service->post($marketplacePayout);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $marketplacePayout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan berhasil di-POST (Dr Bank / Cr Piutang Marketplace).');
    }

    public function void(Request $request, MarketplacePayout $marketplacePayout, MarketplacePayoutService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $service->void($marketplacePayout, $data['reason'] ?? null);

        return redirect()
            ->route('accounting.marketplace-payouts.show', $marketplacePayout)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan berhasil di-VOID (reversal journal dibuat).');
    }

    private function bankAccountOptions()
    {
        return Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }

    private function isBankAccount(int $accountId): bool
    {
        return Account::query()
            ->whereKey($accountId)
            ->where('is_cash', true)
            ->where('is_active', true)
            ->exists();
    }
}
