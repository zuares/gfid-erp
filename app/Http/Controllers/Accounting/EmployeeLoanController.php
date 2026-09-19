<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeLoan;
use App\Models\EmployeeLoanRepayment;
use App\Services\Accounting\EmployeeLoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmployeeLoanController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeLoan::query()
            ->with(['employee', 'cashAccount', 'receivableAccount'])
            ->withSum(['repayments as posted_repayment_amount' => fn ($q) => $q->where('status', 'posted')], 'amount')
            ->orderByDesc('date')->orderByDesc('id');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
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

        $summaryRows = EmployeeLoan::query()
            ->withSum(['repayments as posted_repayment_amount' => fn ($q) => $q->where('status', 'posted')], 'amount')
            ->whereIn('status', ['posted', 'settled'])->get();
        $summary = [
            'total_funded' => (float) $summaryRows->sum('principal_amount'),
            'total_repaid' => (float) $summaryRows->sum('posted_repayment_amount'),
            'outstanding' => (float) $summaryRows->sum(fn ($row) => max(0, (float) $row->principal_amount - (float) $row->posted_repayment_amount)),
            'draft_docs' => EmployeeLoan::where('status', 'draft')->count(),
        ];

        $loans = $query->paginate(25)->withQueryString();
        $employees = Employee::query()->orderBy('name')->get(['id', 'code', 'name']);

        return view('accounting.employee_loans.index', compact('loans', 'employees', 'summary'));
    }

    public function create()
    {
        return view('accounting.employee_loans.create', array_merge(
            ['employeeLoan' => new EmployeeLoan],
            $this->formOptions(),
        ));
    }

    public function store(Request $request)
    {
        $data = $this->validated($request);
        $this->validateAccounts($data);
        if (! Employee::whereKey($data['employee_id'])->where('active', true)->exists()) {
            return back()->withInput()->with('status', 'error')->with('message', 'Karyawan harus aktif.');
        }

        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        $loan = EmployeeLoan::create($data);

        return redirect()->route('accounting.employee-loans.show', $loan)->with('status', 'ok')->with('message', 'Pinjaman karyawan tersimpan sebagai DRAFT.');
    }

    public function show(EmployeeLoan $employeeLoan)
    {
        $employeeLoan->load(['employee', 'cashAccount', 'receivableAccount', 'journal', 'repayments.cashAccount', 'repayments.journal']);

        return view('accounting.employee_loans.show', array_merge(['employeeLoan' => $employeeLoan], $this->formOptions()));
    }

    public function edit(EmployeeLoan $employeeLoan)
    {
        if ($employeeLoan->status !== 'draft') {
            return redirect()->route('accounting.employee-loans.show', $employeeLoan)->with('status', 'error')->with('message', 'Hanya DRAFT yang bisa diedit.');
        }

        return view('accounting.employee_loans.edit', array_merge(['employeeLoan' => $employeeLoan], $this->formOptions()));
    }

    public function update(Request $request, EmployeeLoan $employeeLoan)
    {
        if ($employeeLoan->status !== 'draft') {
            return redirect()->route('accounting.employee-loans.show', $employeeLoan)->with('status', 'error')->with('message', 'Hanya DRAFT yang bisa diupdate.');
        }
        $data = $this->validated($request);
        $this->validateAccounts($data);
        if (! Employee::whereKey($data['employee_id'])->where('active', true)->exists()) {
            return back()->withInput()->with('status', 'error')->with('message', 'Karyawan harus aktif.');
        }
        $employeeLoan->update($data);

        return redirect()->route('accounting.employee-loans.show', $employeeLoan)->with('status', 'ok')->with('message', 'Pinjaman karyawan DRAFT berhasil diupdate.');
    }

    public function destroy(EmployeeLoan $employeeLoan)
    {
        if ($employeeLoan->status !== 'draft') {
            return redirect()->route('accounting.employee-loans.show', $employeeLoan)->with('status', 'error')->with('message', 'Hanya DRAFT yang bisa dihapus.');
        }
        $employeeLoan->delete();

        return redirect()->route('accounting.employee-loans.index')->with('status', 'ok')->with('message', 'Pinjaman karyawan DRAFT berhasil dihapus.');
    }

    public function post(EmployeeLoan $employeeLoan, EmployeeLoanService $service)
    {
        try {
            $service->post($employeeLoan);
        } catch (ValidationException $e) {
            return back()->with('status', 'error')->with('message', collect($e->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Pinjaman karyawan berhasil di-POST: Dr Piutang Karyawan, Cr Kas/Bank.');
    }

    public function void(Request $request, EmployeeLoan $employeeLoan, EmployeeLoanService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        try {
            $service->void($employeeLoan, $data['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('status', 'error')->with('message', collect($e->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Pinjaman karyawan berhasil di-VOID.');
    }

    public function storeRepayment(Request $request, EmployeeLoan $employeeLoan)
    {
        if (! in_array($employeeLoan->status, ['posted', 'settled'], true)) {
            return back()->with('status', 'error')->with('message', 'Pinjaman harus POSTED sebelum angsuran dicatat.');
        }
        $data = $request->validate([
            'date' => ['required', 'date'], 'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string'],
        ]);
        $data['employee_loan_id'] = $employeeLoan->id;
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        EmployeeLoanRepayment::create($data);

        return redirect()->route('accounting.employee-loans.show', $employeeLoan)->with('status', 'ok')->with('message', 'Angsuran tersimpan sebagai DRAFT.');
    }

    public function postRepayment(EmployeeLoanRepayment $repayment, EmployeeLoanService $service)
    {
        try {
            $service->postRepayment($repayment);
        } catch (ValidationException $e) {
            return back()->with('status', 'error')->with('message', collect($e->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Angsuran karyawan berhasil di-POST.');
    }

    public function voidRepayment(Request $request, EmployeeLoanRepayment $repayment, EmployeeLoanService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        try {
            $service->voidRepayment($repayment, $data['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('status', 'error')->with('message', collect($e->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Angsuran karyawan berhasil di-VOID.');
    }

    private function validated(Request $request): array
    {
        return $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'], 'date' => ['required', 'date'],
            'due_date' => ['nullable', 'date', 'after_or_equal:date'], 'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'installment_amount' => ['nullable', 'numeric', 'min:0.01'], 'installment_count' => ['nullable', 'integer', 'min:1'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'], 'receivable_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'], 'reference' => ['nullable', 'string', 'max:100'], 'notes' => ['nullable', 'string'],
        ]);
    }

    private function validateAccounts(array $data): void
    {
        $cash = Account::whereKey($data['cash_account_id'])->where('is_cash', true)->where('is_active', true)->exists();
        $receivable = Account::whereKey($data['receivable_account_id'])->where('type', 'asset')->where('is_cash', false)->where('is_active', true)->exists();
        if (! $cash || ! $receivable || (int) $data['cash_account_id'] === (int) $data['receivable_account_id']) {
            throw ValidationException::withMessages(['account' => 'Pilih akun kas/bank dan akun piutang aset yang aktif serta berbeda.']);
        }
    }

    private function formOptions(): array
    {
        return [
            'employees' => Employee::where('active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'cashAccounts' => Account::where('is_cash', true)->where('is_active', true)->orderBy('code')->get(),
            'receivableAccounts' => Account::where('type', 'asset')->where('is_cash', false)->where('is_active', true)->orderBy('code')->get(),
        ];
    }
}
