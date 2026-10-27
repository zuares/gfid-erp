<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;

uses(RefreshDatabase::class);

it('allows admin login with employee code or email', function () {
    User::factory()->create([
        'employee_code' => 'LOGIN-TEST-1',
        'email' => 'login-test@gfid.test',
        'password' => Hash::make('secret-password'),
        'role' => 'owner',
    ]);

    $this->post(route('login.post'), [
        'employee_code' => 'login-test@gfid.test',
        'password' => 'secret-password',
    ])->assertRedirect();

    $this->post(route('logout'));

    $this->post(route('login.post'), [
        'employee_code' => 'login-test-1',
        'password' => 'secret-password',
    ])->assertRedirect();
});
