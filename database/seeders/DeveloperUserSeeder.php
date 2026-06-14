<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

/**
 * Buat akun developer untuk testing & trial error.
 * Jalankan: php artisan db:seed --class=DeveloperUserSeeder
 *
 * Login: employee_code = DEV, password = developer
 * Akun ini mendapat akses penuh (is_developer = true) dan bypass validasi tertentu.
 */
class DeveloperUserSeeder extends Seeder
{
    public function run(): void
    {
        User::updateOrCreate(
            ['employee_code' => 'DEV'],
            [
                'name'         => 'Developer',
                'email'        => 'dev@gfid.test',
                'role'         => 'admin',   // role 'admin' agar lolos CHECK constraint SQLite
                'is_developer' => true,
                'employee_id'  => null,
                'password'     => Hash::make('developer'),
            ]
        );

        $this->command->info('✓ Akun developer dibuat: DEV / developer');
        $this->command->info('  Email : dev@gfid.test');
        $this->command->info('  Role  : admin + is_developer = true (akses penuh)');
    }
}
