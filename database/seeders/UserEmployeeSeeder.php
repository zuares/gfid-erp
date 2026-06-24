<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserEmployeeSeeder extends Seeder
{
    public function run(): void
    {
        // User: Owner (owner)
        $empId = DB::table('employees')->where('code', 'OWN')->value('id');
        if (!DB::table('users')->where('employee_code', 'OWN')->exists()) {
            DB::table('users')->insert([
                'name' => 'Owner',
                'employee_code' => 'OWN',
                'role' => 'owner',
                'employee_id' => $empId,
                'is_developer' => 0,
                'password' => Hash::make('password'),
                'email' => null,
            ]);
        } else {
            DB::table('users')->where('employee_code', 'OWN')->update([
                'name' => 'Owner',
                'role' => 'owner',
                'employee_id' => $empId,
                'is_developer' => 0,
            ]);
        }
        // User: Neng Nita (admin)
        $empId = DB::table('employees')->where('code', 'NTA')->value('id');
        if (!DB::table('users')->where('employee_code', 'NTA')->exists()) {
            DB::table('users')->insert([
                'name' => 'Neng Nita',
                'employee_code' => 'NTA',
                'role' => 'admin',
                'employee_id' => $empId,
                'is_developer' => 0,
                'password' => Hash::make('password'),
                'email' => null,
            ]);
        } else {
            DB::table('users')->where('employee_code', 'NTA')->update([
                'name' => 'Neng Nita',
                'role' => 'admin',
                'employee_id' => $empId,
                'is_developer' => 0,
            ]);
        }
        // User: Angga (operating)
        $empId = DB::table('employees')->where('code', 'ANG')->value('id');
        if (!DB::table('users')->where('employee_code', 'ANG')->exists()) {
            DB::table('users')->insert([
                'name' => 'Angga',
                'employee_code' => 'ANG',
                'role' => 'operating',
                'employee_id' => $empId,
                'is_developer' => 0,
                'password' => Hash::make('password'),
                'email' => null,
            ]);
        } else {
            DB::table('users')->where('employee_code', 'ANG')->update([
                'name' => 'Angga',
                'role' => 'operating',
                'employee_id' => $empId,
                'is_developer' => 0,
            ]);
        }

    }
}
