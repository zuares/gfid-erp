<?php

// app/Http/Controllers/Master/CustomerController.php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Customer;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class CustomerController extends Controller
{
    public function index(Request $request)
    {
        $query = Customer::query();

        if ($search = $request->input('q')) {
            $query->where(function ($q2) use ($search) {
                $q2->where('name', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%')
                    ->orWhere('email', 'like', '%' . $search . '%');
            });
        }

        if ($request->has('active')) {
            $active = (int) $request->input('active');
            $query->where('active', $active === 1);
        }

        $customers = $query
            ->orderBy('name')
            ->paginate(25)
            ->withQueryString();

        return view('customers.index', compact('customers'));
    }

    public function create()
    {
        $customer = null;

        return view('customers.create', compact('customer'));
    }

    public function store(Request $request)
    {
        $data = $this->validateRequest($request);

        $customer = Customer::create($data);

        return redirect()
            ->route('customers.edit', $customer)
            ->with('success', 'Customer baru berhasil dibuat.');
    }

    public function edit(Customer $customer)
    {
        return view('customers.edit', compact('customer'));
    }

    public function update(Request $request, Customer $customer)
    {
        $data = $this->validateRequest($request, $customer);

        $customer->update($data);

        return redirect()
            ->route('customers.edit', $customer)
            ->with('success', 'Customer berhasil diperbarui.');
    }

    public function destroy(Customer $customer)
    {
        $customer->delete();

        return redirect()
            ->route('customers.index')
            ->with('success', 'Customer berhasil dihapus.');
    }

    protected function validateRequest(Request $request, ?Customer $customer = null): array
    {
        $idToIgnore = $customer?->id;

        return $request->validate([
            'name' => ['required', 'string', 'max:150'],
            'phone' => ['nullable', 'string', 'max:50'],
            'email' => [
                'nullable',
                'string',
                'max:150',
                'email',
                Rule::unique('customers', 'email')->ignore($idToIgnore),
            ],
            'address' => ['nullable', 'string'],
            'active' => ['nullable'], // checkbox
        ], [
            'name.required' => 'Nama customer wajib diisi.',
        ]) + [
            'active' => $request->has('active') ? 1 : 0,
        ];
    }
}
