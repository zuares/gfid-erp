<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Account;
use Illuminate\Http\Request;

class AccountSuggestController extends Controller
{
    public function __invoke(Request $request)
    {
        $q = trim((string) $request->query('q', ''));

        $accounts = Account::query()
            ->where('is_active', 1)
            ->when($q !== '', function ($qq) use ($q) {
                $qq->where(function ($w) use ($q) {
                    $w->where('code', 'like', "%{$q}%")
                        ->orWhere('name', 'like', "%{$q}%");
                });
            })
            ->orderBy('code')
            ->limit(10)
            ->get([
                'id',
                'code',
                'name',
                'type',
                'is_cash',
            ]);

        return response()->json([
            'data' => $accounts,
        ]);
    }
}
