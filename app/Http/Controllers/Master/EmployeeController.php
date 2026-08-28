<?php

namespace App\Http\Controllers\Master;

use App\Http\Controllers\Controller;
use App\Models\Employee;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;

class EmployeeController extends Controller
{
    private const ROLES = ['sewing', 'cutting', 'operating', 'admin', 'owner', 'other'];
    private const PAYMENT_TYPES = ['variable', 'fixed'];

    public function index(Request $request)
    {
        $query = Employee::query();

        if ($search = $request->input('q')) {
            $query->where(function ($q2) use ($search) {
                $q2->where('name', 'like', '%' . $search . '%')
                    ->orWhere('code', 'like', '%' . $search . '%')
                    ->orWhere('phone', 'like', '%' . $search . '%');
            });
        }

        if (($role = $request->input('role')) && in_array($role, self::ROLES, true)) {
            $query->where('role', $role);
        }

        $employees = $query
            ->orderBy('name')
            ->paginate(20)
            ->withQueryString();

        return view('master.employees.index', [
            'employees' => $employees,
            'roles' => self::ROLES,
        ]);
    }

    public function create()
    {
        return view('master.employees.create', [
            'employee' => new Employee(['active' => true, 'payment_type' => 'variable']),
            'roles' => self::ROLES,
            'paymentTypes' => self::PAYMENT_TYPES,
        ]);
    }

    public function store(Request $request)
    {
        $data = $this->validateData($request);

        Employee::create($data);

        return redirect()
            ->route('master.employees.index')
            ->with('status', 'Karyawan berhasil dibuat.');
    }

    public function edit(Employee $employee)
    {
        return view('master.employees.edit', [
            'employee' => $employee,
            'roles' => self::ROLES,
            'paymentTypes' => self::PAYMENT_TYPES,
        ]);
    }

    public function update(Request $request, Employee $employee)
    {
        $data = $this->validateData($request, $employee->id);

        $employee->update($data);

        return redirect()
            ->route('master.employees.index')
            ->with('status', 'Karyawan berhasil diupdate.');
    }

    public function destroy(Employee $employee)
    {
        $employee->delete();

        return redirect()
            ->route('master.employees.index')
            ->with('status', 'Karyawan berhasil dihapus.');
    }

    private function validateData(Request $request, ?int $ignoreId = null): array
    {
        $data = $request->validate([
            'code' => [
                'required', 'string', 'max:50',
                Rule::unique('employees', 'code')->ignore($ignoreId),
            ],
            'name' => ['required', 'string', 'max:255'],
            'role' => ['required', Rule::in(self::ROLES)],
            'payment_type' => ['required', Rule::in(self::PAYMENT_TYPES)],
            'weekly_fixed_salary' => ['nullable', 'numeric', 'min:0'],
            'daily_rate' => ['nullable', 'numeric', 'min:0'],
            'default_piece_rate' => ['nullable', 'numeric', 'min:0'],
            'phone' => ['nullable', 'string', 'max:50'],
            'address' => ['nullable', 'string'],
            'active' => ['nullable', 'boolean'],
        ]);

        $data['weekly_fixed_salary'] = $data['weekly_fixed_salary'] ?? 0;
        $data['daily_rate'] = $data['daily_rate'] ?? 0;
        $data['default_piece_rate'] = $data['default_piece_rate'] ?? 0;
        $data['active'] = $request->boolean('active');

        return $data;
    }
}
