<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashReceipt;
use App\Services\Accounting\CashReceiptService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashReceiptController extends Controller
{
    public function index(Request $request)
    {
        $q = CashReceipt::query()
            ->with(['sourceAccount', 'cashAccount', 'journal'])
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

        $summaryRows = (clone $q)
            ->withoutEagerLoads()
            ->reorder()
            ->selectRaw('status, COUNT(*) as total_docs, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $cashReceiptSummary = [
            'total_docs' => (int) $summaryRows->sum('total_docs'),
            'total_amount' => (float) $summaryRows->sum('total_amount'),
            'posted_amount' => (float) ($summaryRows->get('posted')->total_amount ?? 0),
            'draft_docs' => (int) ($summaryRows->get('draft')->total_docs ?? 0),
            'void_docs' => (int) ($summaryRows->get('void')->total_docs ?? 0),
        ];

        $cashReceipts = $q->paginate(25)->withQueryString();
        [$sourceAccounts, $cashAccounts] = $this->cashReceiptAccountOptions();

        return view('accounting.cash_receipts.index', compact(
            'cashReceipts',
            'cashReceiptSummary',
            'sourceAccounts',
            'cashAccounts',
        ));
    }

    public function create()
    {
        [$sourceAccounts, $cashAccounts] = $this->cashReceiptAccountOptions();

        return view('accounting.cash_receipts.create', compact('sourceAccounts', 'cashAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'source_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ((int) $data['source_account_id'] === (int) $data['cash_account_id']) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun sumber dan akun kas/bank tidak boleh sama.');
        }

        if (!$this->isValidSourceAccount((int) $data['source_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Sumber penerimaan harus akun pendapatan, modal, atau biaya yang aktif.');
        }

        if (!$this->isValidCashAccount((int) $data['cash_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Terima ke harus akun kas/bank yang aktif.');
        }

        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();

        $receipt = CashReceipt::create($data);

        return redirect()
            ->route('accounting.cash-receipts.show', $receipt)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan tersimpan (DRAFT).');
    }

    public function show(CashReceipt $cashReceipt)
    {
        $cashReceipt->load(['sourceAccount', 'cashAccount', 'journal.lines.account']);

        return view('accounting.cash_receipts.show', compact('cashReceipt'));
    }

    public function edit(CashReceipt $cashReceipt)
    {
        if ($cashReceipt->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-receipts.show', $cashReceipt)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        [$sourceAccounts, $cashAccounts] = $this->cashReceiptAccountOptions();

        return view('accounting.cash_receipts.edit', compact('cashReceipt', 'sourceAccounts', 'cashAccounts'));
    }

    public function update(Request $request, CashReceipt $cashReceipt)
    {
        if ($cashReceipt->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-receipts.show', $cashReceipt)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'source_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ((int) $data['source_account_id'] === (int) $data['cash_account_id']) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun sumber dan akun kas/bank tidak boleh sama.');
        }

        if (!$this->isValidSourceAccount((int) $data['source_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Sumber penerimaan harus akun pendapatan, modal, atau biaya yang aktif.');
        }

        if (!$this->isValidCashAccount((int) $data['cash_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Terima ke harus akun kas/bank yang aktif.');
        }

        $cashReceipt->update($data);

        return redirect()
            ->route('accounting.cash-receipts.show', $cashReceipt)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan DRAFT berhasil diupdate.');
    }

    public function destroy(CashReceipt $cashReceipt)
    {
        if ($cashReceipt->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-receipts.show', $cashReceipt)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }

        $cashReceipt->delete();

        return redirect()
            ->route('accounting.cash-receipts.index')
            ->with('status', 'ok')
            ->with('message', 'Penerimaan DRAFT berhasil dihapus.');
    }

    public function post(CashReceipt $cashReceipt, CashReceiptService $service)
    {
        $service->post($cashReceipt);

        return redirect()
            ->route('accounting.cash-receipts.show', $cashReceipt)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan berhasil di-POST (Journal dibuat).');
    }

    public function void(Request $request, CashReceipt $cashReceipt, CashReceiptService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $service->void($cashReceipt, $data['reason'] ?? null);

        return redirect()
            ->route('accounting.cash-receipts.show', $cashReceipt)
            ->with('status', 'ok')
            ->with('message', 'Penerimaan berhasil di-VOID (Reversal journal dibuat).');
    }

    private function cashReceiptAccountOptions(): array
    {
        $sourceAccounts = Account::query()
            ->whereIn('type', ['revenue', 'equity', 'expense'])
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $cashAccounts = Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return [$sourceAccounts, $cashAccounts];
    }

    private function isValidSourceAccount(int $accountId): bool
    {
        return Account::query()
            ->whereKey($accountId)
            ->whereIn('type', ['revenue', 'equity', 'expense'])
            ->where('is_active', true)
            ->exists();
    }

    private function isValidCashAccount(int $accountId): bool
    {
        return Account::query()
            ->whereKey($accountId)
            ->where('is_cash', true)
            ->where('is_active', true)
            ->exists();
    }
}
