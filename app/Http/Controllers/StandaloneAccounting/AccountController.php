<?php

namespace App\Http\Controllers\StandaloneAccounting;

use App\Http\Controllers\Controller;
use App\Models\StandaloneAccountingAccount;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class AccountController extends Controller
{
    public function index()
    {
        $accounts = StandaloneAccountingAccount::query()
            ->orderBy('code')
            ->get();

        return view('standalone_accounting.accounts.index', compact('accounts'));
    }

    public function store(Request $request)
    {
        $data = $request->validate([
            'code' => ['required', 'string', 'max:30', 'regex:/^[A-Za-z0-9._-]+$/', Rule::unique('standalone_accounting_accounts', 'code')],
            'name' => ['required', 'string', 'max:150'],
            'description' => ['nullable', 'string', 'max:1000'],
            'type' => ['required', Rule::in(['asset', 'liability', 'equity', 'revenue', 'expense'])],
        ]);

        StandaloneAccountingAccount::create($data);

        return to_route('standalone-accounting.accounts.index')
            ->with('message', 'Akun akuntansi baru berhasil ditambahkan.');
    }
}
