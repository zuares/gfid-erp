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
        // ============================
        //  EMPLOYEE DATA
        // ============================
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
                'address' => 'Alamat Admin',
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
            $employee = Employee::create($emp);

            // ============================
            //  AUTO GENERATE USER
            // ============================
            User::create([
                'employee_id' => $employee->id,
                'employee_code' => $employee->code,
                'name' => $employee->name,
                'role' => $employee->role,
                'password' => Hash::make('123'), // default password
            ]);
        }
    }
}
