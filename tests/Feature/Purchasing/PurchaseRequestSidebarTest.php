<?php

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

it('shows purchase request navigation for admin users', function () {
    $admin = User::factory()->create([
        'role' => 'admin',
        'employee_code' => 'ADMIN-PR-SIDEBAR',
    ]);

    $response = $this->actingAs($admin)
        ->get(route('purchasing.purchase_requests.create'));

    $response->assertOk()
        ->assertSee('Permintaan Beli')
        ->assertSee('href="' . route('purchasing.purchase_requests.index') . '"', false)
        ->assertSee('Buat Permintaan Beli')
        ->assertSee('href="' . route('purchasing.purchase_requests.create') . '"', false)
        ->assertSee('data-bs-target="#navPurchasingAdmin"', false)
        ->assertSee('data-bs-target="#navPurchasingAdminMobile"', false);
});
