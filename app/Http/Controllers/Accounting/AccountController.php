<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\JournalLine;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AccountController extends Controller
{
    private const CASH_BASIS_CODES = [
        '1101', // Kas Tunai
        '1111', // Bank Jago
        '1112', // Bank BCA
        '1151', // Uang Muka Pembelian
        '1201', // Persediaan Bahan Baku
        '1305', // Piutang Supplier / Retur Pembelian
        '2101', // Hutang Dagang
        '3101', // Modal Pemilik
        '3301', // Prive Pemilik
        '4101', // Penjualan
        '4201', // Retur Penjualan
        '5101', // HPP
        '6101', // Biaya Operasional Umum
        '6102', // Biaya Transport / Ongkir
        '6103', // Biaya Gaji Operasional
        '6110', // Biaya Packing
        '6201', // Biaya Marketplace
        '6202', // Bonus Karyawan
    ];

    private const TECHNICAL_CODES = [
        '1151',
        '1201',
        '1202',
        '1203',
        '1301',
        '1302',
        '1305',
        '1401',
        '4201',
    ];

    private const EXCLUDED_BALANCE_SOURCES = [
        'opening_balance_void',
        'opening_balance_batch_void',
    ];

    public function index(Request $request)
    {
        $q = Account::query()->orderBy('code');
        $mode = $request->string('mode')->toString() ?: 'cash_basis';

        if ($mode === 'cash_basis') {
            $q->whereIn('code', self::CASH_BASIS_CODES);
            if (!$request->filled('active')) {
                $q->where('is_active', true);
            }
        } elseif ($mode === 'technical') {
            $q->whereIn('code', self::TECHNICAL_CODES);
        }

        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        if ($request->filled('active')) {
            $q->where('is_active', (bool) $request->boolean('active'));
        }

        $accounts = $q->withSum(['journalLines as balance' => function ($qq) {
            $qq->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
                ->whereNull('journals.voided_at')
                ->whereNotIn('journals.source_type', self::EXCLUDED_BALANCE_SOURCES);
        }], DB::raw('journal_lines.debit - journal_lines.credit'))
            ->get();

        $accountIds = $accounts->pluck('id')->all();
        $journalSourceRows = empty($accountIds)
            ? collect()
            : DB::table('journal_lines as jl')
                ->join('journals as j', 'j.id', '=', 'jl.journal_id')
                ->whereIn('jl.account_id', $accountIds)
                ->whereNull('j.voided_at')
                ->whereNotIn('j.source_type', self::EXCLUDED_BALANCE_SOURCES)
                ->groupBy('jl.account_id', 'j.source_type')
                ->selectRaw('jl.account_id, j.source_type, COUNT(*) as line_count, COALESCE(SUM(jl.debit - jl.credit), 0) as balance')
                ->orderByDesc('line_count')
                ->get();

        $journalSources = $journalSourceRows
            ->groupBy('account_id')
            ->map(fn($rows) => $rows->values());

        $journalLineCounts = $journalSourceRows
            ->groupBy('account_id')
            ->map(fn($rows) => (int) $rows->sum('line_count'));

        $allAccountsCount = Account::query()->count();
        $cashBasisCount = Account::query()
            ->whereIn('code', self::CASH_BASIS_CODES)
            ->where('is_active', true)
            ->count();
        $technicalCount = Account::query()
            ->whereIn('code', self::TECHNICAL_CODES)
            ->count();

        return view('accounting.accounts.index', compact(
            'accounts',
            'mode',
            'allAccountsCount',
            'cashBasisCount',
            'technicalCount',
            'journalSources',
            'journalLineCounts',
        ));
    }

    public function create()
    {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        return view('accounting.accounts.create', compact('types'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', 'unique:accounts,code'],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'is_cash' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_cash'] = (bool) ($data['is_cash'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $acc = Account::create($data);

        return redirect()->route('accounting.accounts.show', $acc)
            ->with('status', 'ok')->with('message', 'Account dibuat.');
    }

    public function show(Account $account)
    {
        return view('accounting.accounts.show', compact('account'));
    }

    public function edit(Account $account)
    {
        $types = ['asset', 'liability', 'equity', 'revenue', 'expense'];
        return view('accounting.accounts.edit', compact('account', 'types'));
    }

    public function update(Request $request, Account $account)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:20', "unique:accounts,code,{$account->id}"],
            'name' => ['required', 'string', 'max:255'],
            'type' => ['required', 'in:asset,liability,equity,revenue,expense'],
            'is_cash' => ['nullable', 'boolean'],
            'is_active' => ['nullable', 'boolean'],
        ]);

        $data['is_cash'] = (bool) ($data['is_cash'] ?? false);
        $data['is_active'] = (bool) ($data['is_active'] ?? true);

        $account->update($data);

        return redirect()->route('accounting.accounts.show', $account)
            ->with('status', 'ok')->with('message', 'Account diupdate.');
    }

    public function destroy(Account $account)
    {
        // lebih aman: nonaktifkan saja
        $account->update(['is_active' => false]);

        return redirect()->route('accounting.accounts.index')
            ->with('status', 'ok')->with('message', 'Account dinonaktifkan.');
    }

    public function ledger(Request $request, Account $account)
    {
        // Lines (filter by date, exclude void)
        $q = JournalLine::query()
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.account_id', $account->id)
            ->whereNull('journals.voided_at')
            ->whereNotIn('journals.source_type', self::EXCLUDED_BALANCE_SOURCES)
            ->select([
                'journal_lines.id',
                'journals.date as date',
                'journals.description as journal_description',
                'journals.source_type',
                'journals.source_id',
                'journal_lines.debit',
                'journal_lines.credit',
            ])
            ->orderBy('journals.date')
            ->orderBy('journal_lines.id');

        if ($request->filled('from')) {
            $q->whereDate('journals.date', '>=', $request->date('from'));
        }
        if ($request->filled('to')) {
            $q->whereDate('journals.date', '<=', $request->date('to'));
        }

        $lines = $q->paginate(50)->withQueryString();

        // Opening balance sebelum "from"
        $openingBalance = 0.0;
        if ($request->filled('from')) {
            $openingBalance = (float) JournalLine::query()
                ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
                ->where('journal_lines.account_id', $account->id)
                ->whereNull('journals.voided_at')
                ->whereNotIn('journals.source_type', self::EXCLUDED_BALANCE_SOURCES)
                ->whereDate('journals.date', '<', $request->date('from'))
                ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit),0) as s')
                ->value('s');
        }

        // Current balance (all-time)
        $currentBalance = (float) JournalLine::query()
            ->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
            ->where('journal_lines.account_id', $account->id)
            ->whereNull('journals.voided_at')
            ->whereNotIn('journals.source_type', self::EXCLUDED_BALANCE_SOURCES)
            ->selectRaw('COALESCE(SUM(journal_lines.debit - journal_lines.credit),0) as s')
            ->value('s');

        return view('accounting.accounts.ledger', compact(
            'account',
            'lines',
            'openingBalance',
            'currentBalance'
        ));
    }
}
