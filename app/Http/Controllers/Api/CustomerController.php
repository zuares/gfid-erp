<?php

// app/Http/Controllers/Api/CustomerController.php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    /**
     * GET /api/customers/suggest?q=...
     */
    public function suggest(Request $request)
    {
        $q = trim($request->query('q', ''));

        $query = Customer::query();

        if ($q !== '') {
            $query->where(function ($sub) use ($q) {
                $sub->where('name', 'like', '%' . $q . '%')
                    ->orWhere('phone', 'like', '%' . $q . '%')
                    ->orWhere('email', 'like', '%' . $q . '%');
            });
        }

        $customers = $query
            ->orderBy('name')
            ->limit(20)
            ->get();

        return response()->json([
            'data' => $customers->map(function (Customer $c) {
                return [
                    'id' => $c->id,
                    'name' => $c->name,
                    'phone' => $c->phone,
                    'email' => $c->email,
                ];
            }),
        ]);
    }
}
