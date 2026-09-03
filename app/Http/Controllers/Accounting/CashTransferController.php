<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashTransfer;
use App\Services\Accounting\CashTransferService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class CashTransferController extends Controller
{
    public function index(Request $request)
    {
        $query = CashTransfer::query()
            ->with(['fromCashAccount', 'toCashAccount', 'journal'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->date('from'));
        }

        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->date('to'));
        }

        $summaryRows = (clone $query)
            ->withoutEagerLoads()
            ->reorder()
            ->selectRaw('status, COUNT(*) as total_docs, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $summary = [
            'total_docs' => (int) $summaryRows->sum('total_docs'),
            'total_amount' => (float) $summaryRows->sum('total_amount'),
            'posted_amount' => (float) ($summaryRows->get('posted')->total_amount ?? 0),
            'draft_docs' => (int) ($summaryRows->get('draft')->total_docs ?? 0),
            'void_docs' => (int) ($summaryRows->get('void')->total_docs ?? 0),
        ];

        $transfers = $query->paginate(25)->withQueryString();

        return view('accounting.cash_transfers.index', compact('transfers', 'summary'));
    }

    public function create()
    {
        $cashAccounts = $this->cashAccountOptions();

        return view('accounting.cash_transfers.create', compact('cashAccounts'));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        try {
            $this->validateAccounts($data);
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', collect($exception->errors())->flatten()->first() ?: 'Akun transfer tidak valid.');
        }

        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();

        $transfer = CashTransfer::create($data);

        return redirect()
            ->route('accounting.cash-transfers.show', $transfer)
            ->with('status', 'ok')
            ->with('message', 'Transfer kas/bank tersimpan (DRAFT).');
    }

    public function show(CashTransfer $cashTransfer)
    {
        $cashTransfer->load(['fromCashAccount', 'toCashAccount', 'journal.lines.account']);

        return view('accounting.cash_transfers.show', compact('cashTransfer'));
    }

    public function edit(CashTransfer $cashTransfer)
    {
        if ($cashTransfer->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-transfers.show', $cashTransfer)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        $cashAccounts = $this->cashAccountOptions();

        return view('accounting.cash_transfers.edit', compact('cashTransfer', 'cashAccounts'));
    }

    public function update(Request $request, CashTransfer $cashTransfer)
    {
        if ($cashTransfer->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-transfers.show', $cashTransfer)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }

        $data = $this->validated($request);
        try {
            $this->validateAccounts($data);
        } catch (ValidationException $exception) {
            return back()
                ->withInput()
                ->with('status', 'error')
                ->with('message', collect($exception->errors())->flatten()->first() ?: 'Akun transfer tidak valid.');
        }
        $cashTransfer->update($data);

        return redirect()
            ->route('accounting.cash-transfers.show', $cashTransfer)
            ->with('status', 'ok')
            ->with('message', 'Transfer DRAFT berhasil diupdate.');
    }

    public function destroy(CashTransfer $cashTransfer)
    {
        if ($cashTransfer->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-transfers.show', $cashTransfer)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }

        $cashTransfer->delete();

        return redirect()
            ->route('accounting.cash-transfers.index')
            ->with('status', 'ok')
            ->with('message', 'Transfer DRAFT berhasil dihapus.');
    }

    public function post(CashTransfer $cashTransfer, CashTransferService $service)
    {
        try {
            $service->post($cashTransfer);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('accounting.cash-transfers.show', $cashTransfer)
                ->with('status', 'error')
                ->with('message', collect($exception->errors())->flatten()->first() ?: 'Transfer tidak bisa diposting.');
        }

        return redirect()
            ->route('accounting.cash-transfers.show', $cashTransfer)
            ->with('status', 'ok')
            ->with('message', 'Transfer berhasil di-POST (jurnal dibuat).');
    }

    public function void(Request $request, CashTransfer $cashTransfer, CashTransferService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        try {
            $service->void($cashTransfer, $data['reason'] ?? null);
        } catch (ValidationException $exception) {
            return redirect()
                ->route('accounting.cash-transfers.show', $cashTransfer)
                ->with('status', 'error')
                ->with('message', collect($exception->errors())->flatten()->first() ?: 'Transfer tidak bisa di-VOID.');
        }

        return redirect()
            ->route('accounting.cash-transfers.show', $cashTransfer)
            ->with('status', 'ok')
            ->with('message', 'Transfer berhasil di-VOID (jurnal pembalik dibuat).');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'from_cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'to_cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function validateAccounts(array $data): void
    {
        if ((int) $data['from_cash_account_id'] === (int) $data['to_cash_account_id']) {
            throw ValidationException::withMessages([
                'account' => 'Akun asal dan akun tujuan harus berbeda.',
            ]);
        }

        $validCount = Account::query()
            ->whereIn('id', [$data['from_cash_account_id'], $data['to_cash_account_id']])
            ->where('is_cash', true)
            ->where('is_active', true)
            ->count();

        if ($validCount !== 2) {
            throw ValidationException::withMessages([
                'account' => 'Akun asal dan tujuan harus akun kas/bank yang aktif.',
            ]);
        }
    }

    private function cashAccountOptions()
    {
        return Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();
    }
}
