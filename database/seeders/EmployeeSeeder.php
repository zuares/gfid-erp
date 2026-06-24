<?php
namespace Database\Seeders;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class EmployeeSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('employees')->updateOrInsert(
            ['code' => 'OWN'],
            [
                'code' => 'OWN',
                'name' => 'Owner',
                'role' => 'owner',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 0,
                'active' => 1,
                'phone' => '081224889319',
                'address' => 'Alamat Owner',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'NTA'],
            [
                'code' => 'NTA',
                'name' => 'Neng Nita',
                'role' => 'admin',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 500000,
                'default_piece_rate' => 0,
                'active' => 1,
                'phone' => '+62 882-0013-14636',
                'address' => 'Alamat Admin / Fulfillment',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'ANG'],
            [
                'code' => 'ANG',
                'name' => 'Angga',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 450000,
                'default_piece_rate' => 0,
                'active' => 1,
                'phone' => '+62 831-8593-4134',
                'address' => 'Alamat Angga (Gudang Produksi)',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'MRF'],
            [
                'code' => 'MRF',
                'name' => 'Mang Arip',
                'role' => 'cutting',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 1000,
                'active' => 1,
                'phone' => '+62 896-9059-6280',
                'address' => 'Operator Cutting',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'BBI'],
            [
                'code' => 'BBI',
                'name' => 'Bi rini',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 5000,
                'active' => 1,
                'phone' => null,
                'address' => 'Operator Sewing',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'MYD'],
            [
                'code' => 'MYD',
                'name' => 'Mang Yadi',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 0,
                'active' => 1,
                'phone' => '+62 831-1512-5878',
                'address' => 'Operator Sewing',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'RDN'],
            [
                'code' => 'RDN',
                'name' => 'Jang ridwan',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 5000,
                'active' => 1,
                'phone' => '+62 882-0022-24891',
                'address' => 'Operator Sewing',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'OJN'],
            [
                'code' => 'OJN',
                'name' => 'Mang Ojon',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 5000,
                'active' => 1,
                'phone' => '62 898-9741-500',
                'address' => null,
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'TNY'],
            [
                'code' => 'TNY',
                'name' => 'Tony',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 5500,
                'active' => 1,
                'phone' => null,
                'address' => 'Jl Inpress',
            ]
        );
        DB::table('employees')->updateOrInsert(
            ['code' => 'GFR'],
            [
                'code' => 'GFR',
                'name' => 'Gofur',
                'role' => 'sewing',
                'payment_type' => 'variable',
                'weekly_fixed_salary' => 0,
                'default_piece_rate' => 5000,
                'active' => 1,
                'phone' => '089695181887',
                'address' => 'Jl Cigondewah Kaler',
            ]
        );

    }
}
