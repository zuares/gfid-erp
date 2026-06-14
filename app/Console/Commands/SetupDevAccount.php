<?php

namespace App\Console\Commands;

use App\Models\User;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

class SetupDevAccount extends Command
{
    protected $signature   = 'dev:setup';
    protected $description = 'Buat/update akun developer (DEV) di database yang sedang aktif';

    public function handle(): int
    {
        $db = config('database.connections.' . config('database.default') . '.database');
        $this->line("Database aktif: <comment>{$db}</comment>");

        // 1. Tambah kolom is_developer jika belum ada
        if (! Schema::hasColumn('users', 'is_developer')) {
            $this->line('→ Menambah kolom is_developer...');
            Schema::table('users', function ($table) {
                $table->boolean('is_developer')->default(false)->after('role');
            });
            $this->info('  ✓ Kolom is_developer ditambahkan.');
        } else {
            $this->line('  Kolom is_developer sudah ada, skip.');
        }

        // 2. Buat / update user DEV
        $user = User::updateOrCreate(
            ['employee_code' => 'DEV'],
            [
                'name'         => 'Developer',
                'email'        => 'dev@gfid.test',
                'role'         => 'admin',
                'is_developer' => true,
                'employee_id'  => null,
                'password'     => Hash::make('developer'),
            ]
        );

        $action = $user->wasRecentlyCreated ? 'dibuat' : 'diperbarui';
        $this->info("✓ Akun developer {$action}.");
        $this->table(
            ['Field', 'Value'],
            [
                ['Employee Code', 'DEV'],
                ['Password', 'developer'],
                ['Role', 'admin + is_developer = true'],
                ['Email', 'dev@gfid.test'],
                ['Database', $db],
            ]
        );

        return self::SUCCESS;
    }
}
