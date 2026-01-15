<?php

namespace App\Http\Controllers\Accounting;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountController extends Controller
{
    public function index(Request $request)
    {
        $q = Account::query()->orderBy('code');

        if ($request->filled('type')) {
            $q->where('type', $request->string('type')->toString());
        }
        if ($request->filled('active')) {
            $q->where('is_active', (bool) $request->boolean('active'));
        }

        $accounts = $q->paginate(30)->withQueryString();
        return view('accounting.accounts.index', compact('accounts'));
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
}
