<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\CashExpense;
use App\Services\Accounting\CashExpenseService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Storage;
use Illuminate\Validation\ValidationException;

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

        $summaryRows = (clone $q)
            ->withoutEagerLoads()
            ->reorder()
            ->selectRaw('status, COUNT(*) as total_docs, COALESCE(SUM(amount), 0) as total_amount')
            ->groupBy('status')
            ->get()
            ->keyBy('status');

        $cashExpenseSummary = [
            'total_docs' => (int) $summaryRows->sum('total_docs'),
            'total_amount' => (float) $summaryRows->sum('total_amount'),
            'posted_amount' => (float) ($summaryRows->get('posted')->total_amount ?? 0),
            'draft_docs' => (int) ($summaryRows->get('draft')->total_docs ?? 0),
            'void_docs' => (int) ($summaryRows->get('void')->total_docs ?? 0),
        ];

        $cashExpenses = $q->paginate(25)->withQueryString();
        [$expenseAccounts, $cashAccounts] = $this->cashBasisAccountOptions();

        return view('accounting.cash_expenses.index', compact(
            'cashExpenses',
            'cashExpenseSummary',
            'expenseAccounts',
            'cashAccounts',
        ));
    }

    public function create()
    {
        [$expenseAccounts, $cashAccounts] = $this->cashBasisAccountOptions();

        return view('accounting.cash_expenses.create', compact('expenseAccounts', 'cashAccounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'expense_account_id' => ['required', 'string'],
            'category_new' => ['nullable', 'string', 'max:120'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);

        $expenseAccountId = $this->resolveExpenseAccountId($data['expense_account_id'], $data['category_new'] ?? null);
        if (!$expenseAccountId) {
            return back()->withInput()->with('status', 'error')->with('message', 'Kategori pengeluaran harus dipilih atau isi kategori baru.');
        }

        if ((int) $expenseAccountId === (int) $data['cash_account_id']) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun biaya dan akun kas/bank tidak boleh sama.');
        }

        if (!$this->isValidExpenseAccount((int) $expenseAccountId)) {
            return back()->withInput()->with('status', 'error')->with('message', 'Kategori pengeluaran harus akun biaya yang aktif.');
        }

        if (!$this->isValidCashAccount((int) $data['cash_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Bayar dari harus akun kas/bank yang aktif.');
        }

        $data['expense_account_id'] = $expenseAccountId;
        if ($request->hasFile('proof_photo')) {
            $data['proof_photo_path'] = $request->file('proof_photo')->store('cash-expenses/proofs', 'local');
        }
        unset($data['category_new']);
        unset($data['proof_photo']);
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

    public function proof(CashExpense $cashExpense)
    {
        abort_unless($cashExpense->proof_photo_path, 404);

        if (Storage::disk('local')->exists($cashExpense->proof_photo_path)) {
            return Storage::disk('local')->response($cashExpense->proof_photo_path);
        }

        if (Storage::disk('public')->exists($cashExpense->proof_photo_path)) {
            return Storage::disk('public')->response($cashExpense->proof_photo_path);
        }

        abort(404);
    }

    public function edit(CashExpense $cashExpense)
    {
        if ($cashExpense->status !== 'draft') {
            return redirect()
                ->route('accounting.cash-expenses.show', $cashExpense)
                ->with('status', 'error')
                ->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        [$expenseAccounts, $cashAccounts] = $this->cashBasisAccountOptions();

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
            'expense_account_id' => ['required', 'string'],
            'category_new' => ['nullable', 'string', 'max:120'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'proof_photo' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
            'notes' => ['nullable', 'string'],
        ]);

        $expenseAccountId = $this->resolveExpenseAccountId($data['expense_account_id'], $data['category_new'] ?? null);
        if (!$expenseAccountId) {
            return back()->withInput()->with('status', 'error')->with('message', 'Kategori pengeluaran harus dipilih atau isi kategori baru.');
        }

        if ((int) $expenseAccountId === (int) $data['cash_account_id']) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun biaya dan akun kas/bank tidak boleh sama.');
        }

        if (!$this->isValidExpenseAccount((int) $expenseAccountId)) {
            return back()->withInput()->with('status', 'error')->with('message', 'Kategori pengeluaran harus akun biaya yang aktif.');
        }

        if (!$this->isValidCashAccount((int) $data['cash_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Bayar dari harus akun kas/bank yang aktif.');
        }

        $data['expense_account_id'] = $expenseAccountId;
        if ($request->hasFile('proof_photo')) {
            if ($cashExpense->proof_photo_path) {
                Storage::disk('local')->delete($cashExpense->proof_photo_path);
                Storage::disk('public')->delete($cashExpense->proof_photo_path);
            }
            $data['proof_photo_path'] = $request->file('proof_photo')->store('cash-expenses/proofs', 'local');
        }
        unset($data['category_new']);
        unset($data['proof_photo']);
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
        try {
            $service->post($cashExpense);
        } catch (ValidationException $e) {
            return redirect()
                ->route('accounting.cash-expenses.show', $cashExpense)
                ->with('status', 'error')
                ->with('message', collect($e->errors())->flatten()->first() ?: 'Pengeluaran tidak bisa diposting.');
        }

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

    private function cashBasisAccountOptions(): array
    {
        $expenseAccounts = Account::query()
            ->where('type', 'expense')
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        $cashAccounts = Account::query()
            ->where('is_cash', true)
            ->where('is_active', true)
            ->orderBy('code')
            ->get();

        return [$expenseAccounts, $cashAccounts];
    }

    private function isValidExpenseAccount(int $accountId): bool
    {
        return Account::query()
            ->whereKey($accountId)
            ->where('type', 'expense')
            ->where('is_active', true)
            ->exists();
    }

    private function resolveExpenseAccountId(string $value, ?string $newCategory): ?int
    {
        if ($value !== '__new_category__') {
            return ctype_digit($value) ? (int) $value : null;
        }

        $name = trim((string) $newCategory);
        if ($name === '') {
            return null;
        }

        $existing = Account::query()
            ->where('type', 'expense')
            ->whereRaw('LOWER(name) = ?', [mb_strtolower($name)])
            ->first();

        if ($existing) {
            if (!$existing->is_active) {
                $existing->update(['is_active' => true]);
            }

            return (int) $existing->id;
        }

        $nextCode = $this->nextExpenseAccountCode();

        return (int) Account::create([
            'code' => $nextCode,
            'name' => $name,
            'type' => 'expense',
            'is_cash' => false,
            'is_active' => true,
        ])->id;
    }

    private function nextExpenseAccountCode(): string
    {
        $max = Account::query()
            ->where('type', 'expense')
            ->pluck('code')
            ->filter(fn($code) => ctype_digit((string) $code))
            ->map(fn($code) => (int) $code)
            ->max();

        return (string) max(((int) $max) + 1, 6101);
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
