<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashExpense;
use App\Services\Accounting\CashExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class CashExpenseController extends Controller
{
    public function index(Request $request)
    {
        $q = CashExpense::query()
            ->with(['expenseAccount', 'cashAccount', 'journal'])
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

        $cashExpenses = $q->paginate(25)->withQueryString();

        return view('accounting.cash_expenses.index', compact('cashExpenses'));
    }

    public function create()
    {
        // 🔽 kamu bisa filter akun disini kalau punya kolom type/is_cash
        // contoh:
        // $expenseAccounts = Account::where('type', 'expense')->orderBy('name')->get();
        // $cashAccounts = Account::where('is_cash', 1)->orderBy('name')->get();

        $expenseAccounts = Account::orderBy('name')->get();
        $cashAccounts = Account::orderBy('name')->get();

        return view('accounting.cash_expenses.create', compact('expenseAccounts', 'cashAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ((int) $data['expense_account_id'] === (int) $data['cash_account_id']) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun biaya dan akun kas/bank tidak boleh sama.');
        }

        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();

        $expense = CashExpense::create($data);

        return redirect()
            ->route('accounting.cash-expenses.show', $expense)
            ->with('status', 'ok')
            ->with('message', 'Pengeluaran tersimpan (DRAFT).');
    }

    public function show(CashExpense $cashExpense)
    {
        $cashExpense->load(['expenseAccount', 'cashAccount', 'journal.lines.account']);

        return view('accounting.cash_expenses.show', compact('cashExpense'));
    }

    public function edit(CashExpense $cashExpense)
    {
        if ($cashExpense->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-expenses.show', $cashExpense)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        $expenseAccounts = Account::orderBy('name')->get();
        $cashAccounts = Account::orderBy('name')->get();

        return view('accounting.cash_expenses.edit', compact('cashExpense', 'expenseAccounts', 'cashAccounts'));
    }

    public function update(Request $request, CashExpense $cashExpense)
    {
        if ($cashExpense->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-expenses.show', $cashExpense)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if ((int) $data['expense_account_id'] === (int) $data['cash_account_id']) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun biaya dan akun kas/bank tidak boleh sama.');
        }

        $cashExpense->update($data);

        return redirect()
            ->route('accounting.cash-expenses.show', $cashExpense)
            ->with('status', 'ok')
            ->with('message', 'Pengeluaran DRAFT berhasil diupdate.');
    }

    public function destroy(CashExpense $cashExpense)
    {
        if ($cashExpense->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-expenses.show', $cashExpense)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }

        $cashExpense->delete();

        return redirect()
            ->route('accounting.cash-expenses.index')
            ->with('status', 'ok')
            ->with('message', 'Pengeluaran DRAFT berhasil dihapus.');
    }

    public function post(CashExpense $cashExpense, CashExpenseService $service)
    {
        $service->post($cashExpense);

        return redirect()
            ->route('accounting.cash-expenses.show', $cashExpense)
            ->with('status', 'ok')
            ->with('message', 'Pengeluaran berhasil di-POST (Journal dibuat).');
    }

    public function void(Request $request, CashExpense $cashExpense, CashExpenseService $service)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        $service->void($cashExpense, $data['reason'] ?? null);

        return redirect()
            ->route('accounting.cash-expenses.show', $cashExpense)
            ->with('status', 'ok')
            ->with('message', 'Pengeluaran berhasil di-VOID (Reversal journal dibuat).');
    }
}
