<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\Journal;
use App\Models\JournalLine;
use App\Models\Supplier;
use App\Models\SupplierApOpeningBalance;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SupplierApOpeningBalanceController extends Controller
{
    public function index(Request $request)
    {
        $query = SupplierApOpeningBalance::query()
            ->with(['supplier', 'apAccount', 'offsetAccount'])
            ->orderByDesc('date')
            ->orderByDesc('id');

        if ($request->filled('supplier_id')) {
            $query->where('supplier_id', (int) $request->supplier_id);
        }
        if ($request->filled('status')) {
            $status = $request->string('status')->toString();
            if (in_array($status, ['posted', 'void'], true)) {
                $query->where('status', $status);
            }
        }
        if ($request->filled('from')) {
            $query->whereDate('date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $query->whereDate('date', '<=', $request->date('to'));
        }

        $balances = $query->paginate(25)->withQueryString();
        $suppliers = Supplier::query()->orderBy('name')->get(['id', 'name', 'code']);

        return view('accounting.supplier_ap_openings.index', compact('balances', 'suppliers'));
    }

    public function create()
    {
        $suppliers = Supplier::query()
            ->where(function ($query) {
                $query->whereNull('active')->orWhere('active', true);
            })
            ->orderBy('name')
            ->get(['id', 'name', 'code']);

        $accounts = Account::query()
            ->where('is_active', true)
            ->orderBy('code')
            ->get(['id', 'code', 'name', 'type']);

        $defaultAp = $accounts->firstWhere('code', '2101');
        $defaultOffset = $accounts->firstWhere('code', '3101')
            ?? $accounts->firstWhere('type', 'equity');

        return view('accounting.supplier_ap_openings.create', compact(
            'suppliers', 'accounts', 'defaultAp', 'defaultOffset'
        ));
    }

    public function store(Request $request)
    {
        $bulk = $request->input('bulk') === '1';
        $data = $request->validate([
            'bulk' => ['nullable', 'in:0,1'],
            'supplier_id' => ['nullable', 'integer', 'exists:suppliers,id', 'required_unless:bulk,1'],
            'date' => ['required', 'date'],
            'invoice_date' => ['nullable', 'date', 'before_or_equal:date'],
            'due_date' => ['nullable', 'date'],
            'reference_no' => ['nullable', 'string', 'max:100'],
            'amount' => ['nullable', 'string', 'required_unless:bulk,1'],
            'amounts' => ['required_if:bulk,1', 'array'],
            'amounts.*' => ['nullable', 'string'],
            'ap_account_id' => ['required', 'integer', 'exists:accounts,id'],
            'offset_account_id' => ['required', 'integer', 'exists:accounts,id', 'different:ap_account_id'],
            'notes' => ['nullable', 'string'],
        ]);

        $entries = $bulk
            ? collect($data['amounts'] ?? [])
                ->mapWithKeys(fn ($amount, $supplierId) => [(int) $supplierId => $this->toNumber($amount)])
                ->filter(fn ($amount) => $amount > 0)
                ->all()
            : [(int) $data['supplier_id'] => $this->toNumber($data['amount'])];

        if (empty($entries)) {
            throw ValidationException::withMessages([
                $bulk ? 'amounts' : 'amount' => 'Isi minimal satu nominal saldo awal yang lebih besar dari 0.',
            ]);
        }

        return DB::transaction(function () use ($request, $data, $entries, $bulk) {
            $ap = Account::query()->whereKey($data['ap_account_id'])->lockForUpdate()->firstOrFail();
            $offset = Account::query()->whereKey($data['offset_account_id'])->lockForUpdate()->firstOrFail();

            if (! $ap->is_active || $ap->type !== 'liability') {
                throw ValidationException::withMessages([
                    'ap_account_id' => 'Akun Hutang Dagang harus akun liability yang aktif.',
                ]);
            }
            if (! $offset->is_active) {
                throw ValidationException::withMessages([
                    'offset_account_id' => 'Akun lawan harus aktif.',
                ]);
            }

            $suppliers = Supplier::query()
                ->whereIn('id', array_keys($entries))
                ->where(function ($query) {
                    $query->whereNull('active')->orWhere('active', true);
                })
                ->get()
                ->keyBy('id');

            if ($suppliers->count() !== count($entries)) {
                throw ValidationException::withMessages([
                    'supplier_id' => 'Ada supplier yang tidak valid atau sudah tidak aktif.',
                ]);
            }

            foreach ($entries as $supplierId => $amount) {
                $this->postBalance(
                    request: $request,
                    data: $data,
                    supplier: $suppliers->get($supplierId),
                    amount: $amount,
                    ap: $ap,
                    offset: $offset,
                );
            }

            return redirect()
                ->route('accounting.supplier-ap-openings.index')
                ->with('status', 'ok')
                ->with('message', $bulk
                    ? count($entries) . ' saldo awal hutang supplier berhasil diposting.'
                    : 'Saldo awal hutang supplier berhasil diposting.');
        });
    }

    private function postBalance(
        Request $request,
        array $data,
        Supplier $supplier,
        float $amount,
        Account $ap,
        Account $offset
    ): SupplierApOpeningBalance {
        $amount = round($amount, 2);
        $balance = SupplierApOpeningBalance::create([
            'supplier_id' => $supplier->id,
            'date' => $data['date'],
            'invoice_date' => $data['invoice_date'] ?? $data['date'],
            'due_date' => $data['due_date'] ?? null,
            'reference_no' => $data['reference_no'] ?? null,
            'amount' => $amount,
            'ap_account_id' => $ap->id,
            'offset_account_id' => $offset->id,
            'notes' => $data['notes'] ?? null,
            'status' => 'posted',
            'posted_at' => now(),
            'posted_by' => $request->user()?->id,
            'created_by' => $request->user()?->id,
        ]);

        $journal = Journal::create([
            'date' => $data['date'],
            'description' => 'Saldo Awal Hutang Supplier - ' . $supplier->name,
            'source_type' => 'supplier_ap_opening_balance',
            'source_id' => $balance->id,
            'reference_no' => $data['reference_no'] ?? null,
            'notes' => $data['notes'] ?? null,
            'posted_at' => now(),
            'created_by' => $request->user()?->id,
        ]);

        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $offset->id,
            'debit' => $amount,
            'credit' => 0,
        ]);
        JournalLine::create([
            'journal_id' => $journal->id,
            'account_id' => $ap->id,
            'debit' => 0,
            'credit' => $amount,
        ]);

        $balance->update(['journal_id' => $journal->id]);

        return $balance;
    }

    public function void(Request $request, SupplierApOpeningBalance $supplierApOpeningBalance)
    {
        $data = $request->validate([
            'reason' => ['nullable', 'string', 'max:255'],
        ]);

        return DB::transaction(function () use ($request, $data, $supplierApOpeningBalance) {
            $balance = SupplierApOpeningBalance::query()
                ->with(['journal.lines'])
                ->whereKey($supplierApOpeningBalance->id)
                ->lockForUpdate()
                ->firstOrFail();

            if ($balance->status === 'void' || $balance->voided_at) {
                return back()->with('status', 'ok')->with('message', 'Saldo awal sudah VOID.');
            }

            $journal = $balance->journal;
            if (! $journal) {
                throw ValidationException::withMessages([
                    'journal' => 'Jurnal saldo awal tidak ditemukan.',
                ]);
            }

            $description = 'REVERSAL: ' . ($journal->description ?: 'Saldo Awal Hutang Supplier');
            if (! empty($data['reason'])) {
                $description .= ' | ' . $data['reason'];
            }

            $balance->update([
                'status' => 'void',
                'voided_at' => now(),
                'voided_by' => $request->user()?->id,
            ]);
            $journal->update(['voided_at' => now()]);

            $reversal = Journal::create([
                'date' => $journal->date,
                'description' => $description,
                'source_type' => 'supplier_ap_opening_balance_void',
                'source_id' => $balance->id,
                'reference_no' => $journal->reference_no,
                'notes' => $data['reason'] ?? null,
                'posted_at' => now(),
                'created_by' => $request->user()?->id,
            ]);

            foreach ($journal->lines as $line) {
                JournalLine::create([
                    'journal_id' => $reversal->id,
                    'account_id' => $line->account_id,
                    'debit' => $line->credit,
                    'credit' => $line->debit,
                ]);
            }

            return back()
                ->with('status', 'ok')
                ->with('message', 'Saldo awal hutang supplier berhasil di-VOID.');
        });
    }

    private function toNumber($value): float
    {
        if ($value === null || $value === '') {
            return 0.0;
        }
        $value = trim((string) $value);
        $value = str_replace(' ', '', $value);
        if (str_contains($value, ',')) {
            $value = str_replace('.', '', $value);
            $value = str_replace(',', '.', $value);
        } elseif (preg_match('/^\d{1,3}(\.\d{3})+$/', $value)) {
            $value = str_replace('.', '', $value);
        }

        return (float) $value;
    }
}
