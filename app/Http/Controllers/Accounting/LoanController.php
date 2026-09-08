<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Loan;
use App\Models\LoanRepayment;
use App\Services\Accounting\LoanService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class LoanController extends Controller
{
    public function index(Request $request)
    {
        $query = Loan::with(['cashAccount', 'liabilityAccount'])
            ->withSum(['repayments as posted_principal_paid' => fn ($q) => $q->where('status', 'posted')], 'principal_amount')
            ->orderByDesc('date')->orderByDesc('id');
        if ($request->filled('status')) $query->where('status', $request->string('status')->toString());
        if ($request->filled('from')) $query->whereDate('date', '>=', $request->date('from'));
        if ($request->filled('to')) $query->whereDate('date', '<=', $request->date('to'));

        $summaryRows = Loan::query()->reorder()
            ->selectRaw('status, COUNT(*) total_docs, COALESCE(SUM(principal_amount), 0) total_amount')
            ->groupBy('status')->get()->keyBy('status');
        $summary = [
            'total_amount' => (float) $summaryRows->sum('total_amount'),
            'posted_amount' => (float) ($summaryRows->get('posted')->total_amount ?? 0),
            'draft_docs' => (int) ($summaryRows->get('draft')->total_docs ?? 0),
            'void_docs' => (int) ($summaryRows->get('void')->total_docs ?? 0),
        ];
        $loans = $query->paginate(25)->withQueryString();
        return view('accounting.loans.index', compact('loans', 'summary'));
    }

    public function create()
    {
        return view('accounting.loans.create', $this->accountOptions());
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'lender' => ['required', 'string', 'max:160'],
            'principal_amount' => ['required', 'numeric', 'min:0.01'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'liability_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'description' => ['nullable', 'string', 'max:255'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
        if (!$this->cashAccount($data['cash_account_id']) || !$this->liabilityAccount($data['liability_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun kas/bank atau akun utang tidak valid.');
        }
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        $loan = Loan::create($data);
        return redirect()->route('accounting.loans.show', $loan)->with('status', 'ok')->with('message', 'Pinjaman tersimpan sebagai DRAFT.');
    }

    public function show(Loan $loan)
    {
        $loan->load(['cashAccount', 'liabilityAccount', 'journal', 'repayments.cashAccount', 'repayments.interestAccount', 'repayments.journal']);
        $options = $this->accountOptions();
        return view('accounting.loans.show', array_merge(compact('loan'), $options));
    }

    public function post(Loan $loan, LoanService $service)
    {
        $service->post($loan);
        return back()->with('status', 'ok')->with('message', 'Pinjaman berhasil di-POST: debit kas/bank dan kredit utang pinjaman.');
    }

    public function void(Request $request, Loan $loan, LoanService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $service->void($loan, $data['reason'] ?? null);
        return back()->with('status', 'ok')->with('message', 'Pinjaman berhasil di-VOID.');
    }

    public function storeRepayment(Request $request, Loan $loan)
    {
        $data = $request->validate([
            'date' => ['required', 'date'],
            'principal_amount' => ['required', 'numeric', 'min:0'],
            'interest_amount' => ['nullable', 'numeric', 'min:0'],
            'interest_account_id' => ['nullable', 'integer', 'exists:accounts,id'],
            'cash_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'reference' => ['nullable', 'string', 'max:100'],
            'notes' => ['nullable', 'string'],
        ]);
        if (!$this->cashAccount($data['cash_account_id']) || ($data['interest_account_id'] ?? null) && !$this->expenseAccount($data['interest_account_id'])) {
            return back()->withInput()->with('status', 'error')->with('message', 'Akun pembayaran atau akun beban bunga tidak valid.');
        }
        $data['loan_id'] = $loan->id;
        $data['interest_amount'] = $data['interest_amount'] ?? 0;
        $data['status'] = 'draft';
        $data['created_by'] = Auth::id();
        $repayment = LoanRepayment::create($data);
        return redirect()->route('accounting.loans.show', $loan)->with('status', 'ok')->with('message', 'Pembayaran tersimpan sebagai DRAFT.');
    }

    public function postRepayment(LoanRepayment $repayment, LoanService $service)
    {
        $service->postRepayment($repayment);
        return back()->with('status', 'ok')->with('message', 'Pembayaran berhasil di-POST.');
    }

    public function voidRepayment(Request $request, LoanRepayment $repayment, LoanService $service)
    {
        $data = $request->validate(['reason' => ['nullable', 'string', 'max:255']]);
        $service->voidRepayment($repayment, $data['reason'] ?? null);
        return back()->with('status', 'ok')->with('message', 'Pembayaran berhasil di-VOID.');
    }

    private function accountOptions(): array
    {
        return [
            'cashAccounts' => Account::where('is_cash', true)->where('is_active', true)->orderBy('code')->get(),
            'liabilityAccounts' => Account::where('type', 'liability')->where('is_active', true)->orderBy('code')->get(),
            'expenseAccounts' => Account::where('type', 'expense')->where('is_active', true)->orderBy('code')->get(),
        ];
    }

    private function cashAccount(int $id): bool { return Account::whereKey($id)->where('is_cash', true)->where('is_active', true)->exists(); }
    private function liabilityAccount(int $id): bool { return Account::whereKey($id)->where('type', 'liability')->where('is_active', true)->exists(); }
    private function expenseAccount(int $id): bool { return Account::whereKey($id)->where('type', 'expense')->where('is_active', true)->exists(); }
}
