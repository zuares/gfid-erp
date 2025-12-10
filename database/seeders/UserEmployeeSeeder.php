<?php

namespace Database\Seeders;

use App\Models\Employee;
use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        $employees = [
            [
                'code' => 'OWN',
                'name' => 'Owner',
                'role' => 'owner',
                'active' => 1,
                'phone' => '081200000001',
                'address' => 'Alamat Owner',
            ],
            [
                'code' => 'NTA',
                'name' => 'Neng Nita',
                'role' => 'admin',
                'active' => 1,
                'phone' => '081200000002',
                'address' => 'Alamat Admin / Fulfillment',
            ],
            [
                'code' => 'ANG',
                'name' => 'Angga',
                'role' => 'operating',
                'active' => 1,
                'phone' => '081200000002',
                'address' => 'Alamat Angga (Gudang Produksi)',
            ],
            [
                'code' => 'MRF',
                'name' => 'Mang Arip',
                'role' => 'cutting',
                'active' => 1,
                'phone' => '081200000003',
                'address' => 'Operator Cutting',
            ],
            [
                'code' => 'BBI',
                'name' => 'Bi rini',
                'role' => 'sewing',
                'active' => 1,
                'phone' => '081200000004',
                'address' => 'Operator Sewing',
            ],
            [
                'code' => 'MYD',
                'name' => 'Mang Yadi',
                'role' => 'sewing',
                'active' => 1,
                'phone' => '081200000004',
                'address' => 'Operator Sewing',
            ],
            [
                'code' => 'RDN',
                'name' => 'Jang ridwan',
                'role' => 'sewing',
                'active' => 1,
                'phone' => '081200000004',
                'address' => 'Operator Sewing',
            ],
        ];

        foreach ($employees as $emp) {
            // EMPLOYEE
            $employee = Employee::updateOrCreate(
                ['code' => $emp['code']],
                [
                    'name' => $emp['name'],
                    'role' => $emp['role'],
                    'active' => $emp['active'],
                    'phone' => $emp['phone'],
                    'address' => $emp['address'],
                ]
            );

            // ROLE LOGIN
            $loginRole = match ($employee->role) {
                'owner' => 'owner',
                'admin' => 'admin',
                'operating' => 'operating',
                default => null,
            };

            if (!$loginRole) {
                continue;
            }

            // USER
            User::updateOrCreate(
                [
                    'employee_code' => $employee->code,
                ],
                [
                    'employee_id' => $employee->id,
                    'name' => $employee->name,
                    'role' => $loginRole,
                    // kalau user sudah ada, biarin password lama (jangan reset)
                    'password' => User::where('employee_code', $employee->code)->value('password') ?? Hash::make('123'),
                ]
            );
        }
    }
}
