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
        '1205', // Persediaan Packaging
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
        '1205',
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

        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }

        $accounts = $q->withSum(['journalLines as balance' => function ($qq) {
            $qq->join('journals', 'journals.id', '=', 'journal_lines.journal_id')
                ->whereNull('journals.voided_at')
                ->whereNotIn('journals.source_type', self::EXCLUDED_BALANCE_SOURCES);
        }], DB::raw('journal_lines.debit - journal_lines.credit'))
            ->get();

        $journalLineCounts = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED_BALANCE_SOURCES)
            ->groupBy('jl.account_id')
            ->selectRaw('jl.account_id, COUNT(*) as line_count')
            ->get()
            ->pluck('line_count', 'account_id');

        return view('accounting.accounts.index', compact('accounts', 'journalLineCounts'));
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

    public function bukuBesar(Request $request)
    {
        $from = $request->filled('from')
            ? \Carbon\Carbon::parse($request->date('from'))->toDateString()
            : now()->startOfMonth()->toDateString();

        $to = $request->filled('to')
            ? \Carbon\Carbon::parse($request->date('to'))->toDateString()
            : now()->toDateString();

        $accounts = Account::where('is_active', true)->orderBy('code')->get();

        // Balance per account in date range
        $inRange = DB::table('journal_lines as jl')
            ->join('journals as j', 'j.id', '=', 'jl.journal_id')
            ->whereNull('j.voided_at')
            ->whereNotIn('j.source_type', self::EXCLUDED_BALANCE_SOURCES)
            ->whereDate('j.date', '>=', $from)
            ->whereDate('j.date', '<=', $to)
            ->groupBy('jl.account_id')
            ->selectRaw('jl.account_id,
                COALESCE(SUM(jl.debit),0) as period_debit,
                COALESCE(SUM(jl.credit),0) as period_credit,
                COUNT(*) as tx_count')
            ->get()->keyBy('account_id');

        $rows = $accounts->map(function ($acc) use ($inRange) {
            $b = $inRange[$acc->id] ?? null;
            return (object) [
                'id'           => $acc->id,
                'code'         => $acc->code,
                'name'         => $acc->name,
                'type'         => $acc->type,
                'period_debit' => $b ? (float) $b->period_debit  : 0.0,
                'period_credit'=> $b ? (float) $b->period_credit : 0.0,
                'tx_count'     => $b ? (int)   $b->tx_count      : 0,
            ];
        })->filter(fn($r) => $r->tx_count > 0);

        return view('accounting.accounts.buku_besar', compact('from', 'to', 'rows'));
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
                'journals.created_at as posted_at',
                'journals.description as journal_description',
                'journals.source_type',
                'journals.source_id',
                'journal_lines.debit',
                'journal_lines.credit',
            ])
            ->orderBy('journals.date')
            ->orderBy('journals.created_at')
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
