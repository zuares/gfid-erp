<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Supplier;
use App\Models\SupplierBankAccount;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class SupplierBankAccountController extends Controller
{
    public function index(Supplier $supplier): JsonResponse
    {
        $accounts = $supplier->bankAccounts()
            ->orderBy('created_at')
            ->get(['id', 'bank_name', 'account_number', 'account_holder', 'notes']);

        return response()->json([
            'accounts' => $accounts,
            'count'    => $accounts->count(),
        ]);
    }

    public function store(Request $request, Supplier $supplier): JsonResponse
    {
        $data = $request->validate([
            'bank_name'      => ['required', 'string', 'max:50'],
            'account_number' => ['required', 'string', 'max:100'],
            'account_holder' => ['required', 'string', 'max:255'],
            'notes'          => ['nullable', 'string', 'max:255'],
        ]);

        $data['supplier_id'] = $supplier->id;

        $account = SupplierBankAccount::create($data);

        return response()->json([
            'ok'      => true,
            'account' => $account->only(['id', 'bank_name', 'account_number', 'account_holder', 'notes']),
        ], 201);
    }

    public function destroy(Supplier $supplier, SupplierBankAccount $bankAccount): JsonResponse
    {
        abort_unless($bankAccount->supplier_id === $supplier->id, 403);

        $bankAccount->delete();

        return response()->json(['ok' => true]);
    }
}
