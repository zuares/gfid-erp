<?php

namespace Tests\Feature;

use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ServiceIdentity;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Str;
use Mockery;
use Tests\TestCase;

class InternalInventoryReadApiTest extends TestCase
{
    use RefreshDatabase;

    private function createIdentityToken(array $abilities = ['mcp:inventory:read']): array
    {
        $identity = ServiceIdentity::create([
            'slug' => 'hermes-agent',
            'name' => 'Hermes Agent',
            'is_active' => true,
        ]);

        $token = $identity->createToken('feature-test', $abilities, now()->addDay());

        return [$identity, $token->plainTextToken];
    }

    private function stockRequest(string $token, array $query = [])
    {
        return $this->withHeader('Authorization', 'Bearer '.$token)
            ->getJson('/api/internal/inventory/stock'.($query ? '?'.http_build_query($query) : ''));
    }

    public function test_machine_inventory_requires_a_valid_service_token(): void
    {
        $this->getJson('/api/internal/inventory/stock?sku=SKU-A')->assertUnauthorized();
        $this->withHeader('Authorization', 'Bearer invalid-token')
            ->getJson('/api/internal/inventory/stock?sku=SKU-A')
            ->assertUnauthorized();
    }

    public function test_machine_token_without_inventory_ability_is_forbidden(): void
    {
        [, $token] = $this->createIdentityToken(['mcp:inventory:other']);

        $this->stockRequest($token, ['sku' => 'SKU-A'])->assertForbidden();
    }

    public function test_human_user_token_cannot_authenticate_as_a_service_identity(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'HUMAN-TOKEN-TEST',
        ]);
        $plainSecret = Str::random(40);
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'human-token',
            'token' => hash('sha256', $plainSecret),
            'abilities' => json_encode(['mcp:inventory:read']),
            'last_used_at' => null,
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $plainToken = $tokenId.'|'.$plainSecret;

        $this->stockRequest($plainToken, ['sku' => 'SKU-A'])->assertForbidden();
    }

    public function test_revoked_service_token_is_rejected(): void
    {
        [$identity, $token] = $this->createIdentityToken();
        $identity->tokens()->where('name', 'feature-test')->delete();

        $this->stockRequest($token, ['sku' => 'SKU-A'])->assertUnauthorized();
    }

    public function test_stock_position_is_read_only_clamped_per_warehouse_and_has_a_narrow_response(): void
    {
        [, $token] = $this->createIdentityToken();
        $item = Item::create([
            'code' => 'SKU-A',
            'name' => 'Private item name',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
            'hpp' => 9000,
        ]);
        $warehouseA = Warehouse::create([
            'code' => 'WH-PRD', 'name' => 'Production', 'type' => 'internal', 'active' => true,
        ]);
        $warehouseB = Warehouse::create([
            'code' => 'WH-OVER', 'name' => 'Overallocated', 'type' => 'internal', 'active' => true,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouseA->id, 'item_id' => $item->id, 'qty' => 150, 'allocated_qty' => 23,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouseB->id, 'item_id' => $item->id, 'qty' => 3, 'allocated_qty' => 8,
        ]);
        $stockCount = InventoryStock::count();
        $mutationCount = DB::table('inventory_mutations')->count();

        $this->stockRequest($token, ['sku' => 'SKU-A', 'warehouse' => 'WH-PRD'])
            ->assertOk()
            ->assertExactJson([
                'sku' => 'SKU-A',
                'warehouse' => 'WH-PRD',
                'on_hand' => 150,
                'allocated' => 23,
                'available' => 127,
            ]);

        $this->stockRequest($token, ['sku' => 'SKU-A', 'warehouse' => 'WH-OVER'])
            ->assertOk()
            ->assertJsonPath('available', 0);

        $this->stockRequest($token, ['sku' => 'SKU-A'])
            ->assertOk()
            ->assertJsonPath('total.on_hand', 153)
            ->assertJsonPath('total.allocated', 31)
            ->assertJsonPath('total.available', 127)
            ->assertJsonMissingPath('hpp')
            ->assertJsonMissingPath('name')
            ->assertJsonMissingPath('supplier');

        $this->assertSame($stockCount, InventoryStock::count());
        $this->assertSame($mutationCount, DB::table('inventory_mutations')->count());
    }

    public function test_unknown_sku_or_warehouse_returns_not_found(): void
    {
        [, $token] = $this->createIdentityToken();
        Item::create([
            'code' => 'SKU-A', 'name' => 'Item A', 'unit' => 'pcs', 'type' => 'material', 'active' => true,
        ]);

        $this->stockRequest($token, ['sku' => 'MISSING'])->assertNotFound();
        $this->stockRequest($token, ['sku' => 'SKU-A', 'warehouse' => 'MISSING'])->assertNotFound();
        $this->stockRequest($token)->assertUnprocessable();
        $this->stockRequest($token, ['sku' => "' OR 1=1 --"])->assertUnprocessable();
    }

    public function test_machine_inventory_read_does_not_accept_mutation_methods(): void
    {
        [, $token] = $this->createIdentityToken();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->postJson('/api/internal/inventory/stock', ['sku' => 'SKU-A'])
            ->assertStatus(405);
    }

    public function test_machine_request_audit_includes_correlation_id_without_secret_headers(): void
    {
        [, $token] = $this->createIdentityToken();
        Item::create([
            'code' => 'SKU-A', 'name' => 'Item A', 'unit' => 'pcs', 'type' => 'material', 'active' => true,
        ]);
        Log::spy();

        $this->withHeader('Authorization', 'Bearer '.$token)
            ->withHeader('X-Request-Id', 'request-1234')
            ->getJson('/api/internal/inventory/stock?sku=SKU-A')
            ->assertOk()
            ->assertHeader('X-Request-Id', 'request-1234');

        Log::shouldHaveReceived('info')->once()->with('machine.request', Mockery::on(
            fn (array $context): bool => $context['service_identity'] === 'hermes-agent'
                && $context['operation'] === 'api.internal.inventory.stock'
                && $context['success'] === true
                && $context['status'] === 200
                && $context['request_id'] === 'request-1234'
                && $context['sku'] === 'SKU-A'
                && ! array_key_exists('authorization', $context)
                && ! array_key_exists('token', $context)
        ));
    }
}
