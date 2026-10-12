<?php

namespace Tests\Feature;

use App\Models\ServiceIdentity;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ServiceTokenCommandTest extends TestCase
{
    use RefreshDatabase;

    public function test_create_command_issues_only_inventory_read_token_and_revoke_removes_it(): void
    {
        $this->artisan('service-token:create', [
            'identity' => 'hermes-agent',
            'name' => 'rotation-one',
            '--days' => 30,
            '--yes' => true,
        ])->assertSuccessful()
            ->expectsOutputToContain('mcp:inventory:read')
            ->expectsOutputToContain('Copy this token now. It will not be stored or shown again:');

        $identity = ServiceIdentity::where('slug', 'hermes-agent')->firstOrFail();
        $storedToken = $identity->tokens()->where('name', 'rotation-one')->firstOrFail();

        $this->assertSame(['mcp:inventory:read'], $storedToken->abilities);
        $this->assertNotSame('*', $storedToken->abilities[0]);
        $this->assertTrue($storedToken->expires_at->isFuture());
        $this->assertSame(1, DB::table('personal_access_tokens')->count());

        $this->artisan('service-token:revoke', [
            'identity' => 'hermes-agent',
            'name' => 'rotation-one',
            '--yes' => true,
        ])->assertSuccessful();

        $this->assertSame(0, DB::table('personal_access_tokens')->count());
    }

    public function test_create_command_does_not_create_identity_when_confirmation_is_declined(): void
    {
        $this->artisan('service-token:create', [
            'identity' => 'hermes-agent',
            'name' => 'cancelled',
        ])->expectsConfirmation('Create this service token?', 'no')
            ->assertSuccessful();

        $this->assertDatabaseMissing('service_identities', ['slug' => 'hermes-agent']);
    }
}
