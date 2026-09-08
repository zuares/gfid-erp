<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Supplier;
use App\Models\SupplierLoan;
use App\Models\SupplierLoanRepayment;
use App\Services\Accounting\SupplierLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class SupplierLoanController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierLoan::query()
            ->with(['supplier', 'cashAccount', 'receivableAccount'])
            ->withSum(['repayments as posted_repayment_amount' => fn ($q) => $q->where('status', 'posted')], 'amount')
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', $request->integer('supplier_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->date('to'));
        }

        $summaryRows = SupplierLoan::query()
            ->withSum(['repayments as posted_repayment_amount' => fn ($q) => $q->where('status', 'posted')], 'amount')
            ->whereIn('status', ['posted', 'settled'])
            ->get();

        $summary = [
            'total_funded' => (float) $summaryRows->sum('principal_amount'),
            'total_repaid' => (float) $summaryRows->sum('posted_repayment_amount'),
            'outstanding' => (float) $summaryRows->sum(fn ($row) => max(0, (float) $row->principal_amount - (float) $row->posted_repayment_amount)),
            'draft_docs' => SupplierLoan::where('status', 'draft')->count(),
        ];

        $loans = $query->paginate(25)->withQueryString();
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'code', 'name']);

        return view('accounting.supplier_loans.index', compact('loans', 'suppliers', 'summary'));
    }

    public function create()
    {
        return view('accounting.supplier_loans.create', $this->formOptions());
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->validateFormAccounts($data);

        $supplier = Supplier::query()->whereKey($data['supplier_id'])->where('active', true)->first();
        if (! $supplier) {
            return back()->withInput()->with('status', 'error')->with('message', 'Supplier harus aktif.');
        }

        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        $loan = SupplierLoan::create($data);

        return redirect()->route('accounting.supplier-loans.show', $loan)
            ->with('status', 'ok')->with('message', 'Dana supplier tersimpan sebagai DRAFT.');
    }

    public function show(SupplierLoan $supplierLoan)
    {
        $supplierLoan->load([
            'supplier', 'cashAccount', 'receivableAccount', 'journal',
            'repayments.cashAccount', 'repayments.journal',
        ]);

        return view('accounting.supplier_loans.show', array_merge(
            ['supplierLoan' => $supplierLoan],
            $this->formOptions(),
        ));
    }

    public function edit(SupplierLoan $supplierLoan)
    {
        if ($supplierLoan->status !== 'draft') {
            return redirect()->route('accounting.supplier-loans.show', $supplierLoan)
                ->with('status', 'error')->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        return view('accounting.supplier_loans.edit', array_merge(
            ['supplierLoan' => $supplierLoan],
            $this->formOptions(),
        ));
    }

    public function update(Request $request, SupplierLoan $supplierLoan)
    {
        if ($supplierLoan->status !== 'draft') {
            return redirect()->route('accounting.supplier-loans.show', $supplierLoan)
                ->with('status', 'error')->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }

        $data = $this->validated($request);
        $this->validateFormAccounts($data);
        $supplier = Supplier::query()->whereKey($data['supplier_id'])->where('active', true)->exists();
        if (! $supplier) {
            return back()->withInput()->with('status', 'error')->with('message', 'Supplier harus aktif.');
        }

        $supplierLoan->update($data);

        return redirect()->route('accounting.supplier-loans.show', $supplierLoan)
            ->with('status', 'ok')->with('message', 'Dana supplier DRAFT berhasil diupdate.');
    }

    public function destroy(SupplierLoan $supplierLoan)
    {
        if ($supplierLoan->status !== 'draft') {
            return redirect()->route('accounting.supplier-loans.show', $supplierLoan)
                ->with('status', 'error')->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }

        $supplierLoan->delete();

        return redirect()->route('accounting.supplier-loans.index')
            ->with('status', 'ok')->with('message', 'Dana supplier DRAFT berhasil dihapus.');
    }

    public function post(SupplierLoan $supplierLoan, SupplierLoanService $service)
    {
        try {
            $service->post($supplierLoan);
        } catch (ValidationException $exception) {
            return back()->with('status', 'error')->with('message', collect($exception->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Dana supplier berhasil di-POST: Dr Piutang Pinjaman Supplier, Cr Kas/Bank.');
    }

    public function void(Request $request, SupplierLoan $supplierLoan, SupplierLoanService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $service->void($supplierLoan, $data['reason'] ?? null);
        } catch (ValidationException $exception) {
            return back()->with('status', 'error')->with('message', collect($exception->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Dana supplier berhasil di-VOID.');
    }

    public function storeRepayment(Request $request, SupplierLoan $supplierLoan)
    {
        if (! in_array($supplierLoan->status, ['posted', 'settled'], true)) {
            return back()->with('status', 'error')->with('message', 'Dana supplier harus POSTED sebelum pengembalian dicatat.');
        }

        $data = $request->validate([
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        $data['supplier_loan_id'] = $supplierLoan->id;
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        SupplierLoanRepayment::create($data);

        return redirect()->route('accounting.supplier-loans.show', $supplierLoan)
            ->with('status', 'ok')->with('message', 'Pengembalian supplier tersimpan sebagai DRAFT.');
    }

    public function postRepayment(SupplierLoanRepayment $repayment, SupplierLoanService $service)
    {
        try {
            $service->postRepayment($repayment);
        } catch (ValidationException $exception) {
            return back()->with('status', 'error')->with('message', collect($exception->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Pengembalian supplier berhasil di-POST.');
    }

    public function voidRepayment(Request $request, SupplierLoanRepayment $repayment, SupplierLoanService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $service->voidRepayment($repayment, $data['reason'] ?? null);
        } catch (ValidationException $exception) {
            return back()->with('status', 'error')->with('message', collect($exception->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Pengembalian supplier berhasil di-VOID.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'supplier_id' => ['required', 'integer', 'exists:suppliers,id'],
            'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'receivable_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
    }

    private function validateFormAccounts(array $data): void
    {
        $cash = Account::query()->whereKey($data['cash_account_id'])->where('is_cash', true)->where('is_active', true)->exists();
        $receivable = Account::query()->whereKey($data['receivable_account_id'])->where('type', 'asset')->where('is_cash', false)->where('is_active', true)->exists();

        if (! $cash || ! $receivable || (int) $data['cash_account_id'] === (int) $data['receivable_account_id']) {
            throw ValidationException::withMessages(['account' => 'Pilih akun kas/bank dan akun piutang aset yang aktif serta berbeda.']);
        }
    }

    private function formOptions(): array
    {
        return [
            'suppliers' => Supplier::query()->where('active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'cashAccounts' => Account::query()->where('is_cash', true)->where('is_active', true)->orderBy('code')->get(),
            'receivableAccounts' => Account::query()->where('type', 'asset')->where('is_cash', false)->where('is_active', true)->orderBy('code')->get(),
        ];
    }
}
