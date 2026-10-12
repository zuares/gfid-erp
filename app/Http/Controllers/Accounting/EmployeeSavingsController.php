<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Employee;
use App\Models\EmployeeSavingsTransaction;
use App\Services\Accounting\EmployeeSavingsService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\ValidationException;

class EmployeeSavingsController extends Controller
{
    public function index(Request $request)
    {
        $query = EmployeeSavingsTransaction::query()
            ->with(['employee', 'cashAccount', 'journal'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('employee_id')) {
            $query->where('employee_id', $request->integer('employee_id'));
        }
        if ($request->filled('status')) {
            $query->where('status', $request->string('status')->toString());
        }

        $summary = [
            'deposited' => (float) EmployeeSavingsTransaction::query()
                ->where('type', EmployeeSavingsTransaction::TYPE_DEPOSIT)
                ->where('status', 'posted')
                ->sum('amount'),
            'paid' => (float) EmployeeSavingsTransaction::query()
                ->where('type', EmployeeSavingsTransaction::TYPE_PAYMENT)
                ->where('status', 'posted')
                ->sum('amount'),
            'draft' => (int) EmployeeSavingsTransaction::query()->where('type', EmployeeSavingsTransaction::TYPE_PAYMENT)->where('status', 'draft')->count(),
        ];
        $summary['outstanding'] = max(0, $summary['deposited'] - $summary['paid']);

        $transactions = $query->paginate(25)->withQueryString();
        $employees = Employee::query()->orderBy('name')->get(['id', 'code', 'name']);

        return view('accounting.employee_savings.index', compact('transactions', 'employees', 'summary'));
    }

    public function create()
    {
        return view('accounting.employee_savings.create', [
            'employees' => Employee::query()->where('active', true)->orderBy('name')->get(['id', 'code', 'name']),
            'cashAccounts' => Account::query()->where('is_cash', true)->where('is_active', true)->orderBy('code')->get(),
        ]);
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'employee_id' => ['required', 'integer', 'exists:employees,id'],
            'date' => ['required', 'date'],
            'amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);

        if (! Employee::query()->whereKey($data['employee_id'])->where('active', true)->exists()) {
            return back()->withInput()->with('status', 'error')->with('message', 'Karyawan harus aktif.');
        }

        $data['type'] = EmployeeSavingsTransaction::TYPE_PAYMENT;
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        $transaction = EmployeeSavingsTransaction::create($data);

        return redirect()
            ->route('accounting.employee-savings.show', $transaction)
            ->with('status', 'ok')
            ->with('message', 'Pembayaran tabungan tersimpan sebagai DRAFT.');
    }

    public function show(EmployeeSavingsTransaction $employeeSavings)
    {
        $employeeSavings->load(['employee', 'cashAccount', 'journal']);
        $outstanding = app(EmployeeSavingsService::class)->outstandingForEmployee(
            $employeeSavings->employee_id,
            $employeeSavings->type === EmployeeSavingsTransaction::TYPE_PAYMENT ? $employeeSavings->id : null,
        );

        return view('accounting.employee_savings.show', compact('employeeSavings', 'outstanding'));
    }

    public function post(EmployeeSavingsTransaction $employeeSavings, EmployeeSavingsService $service)
    {
        try {
            $service->post($employeeSavings);
        } catch (ValidationException $e) {
            return back()->with('status', 'error')->with('message', collect($e->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Pembayaran tabungan berhasil di-POST: Dr Tabungan Karyawan, Cr Kas/Bank.');
    }

    public function void(Request $request, EmployeeSavingsTransaction $employeeSavings, EmployeeSavingsService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);

        try {
            $service->void($employeeSavings, $data['reason'] ?? null);
        } catch (ValidationException $e) {
            return back()->with('status', 'error')->with('message', collect($e->errors())->flatten()->first());
        }

        return back()->with('status', 'ok')->with('message', 'Pembayaran tabungan berhasil di-VOID.');
    }
}
