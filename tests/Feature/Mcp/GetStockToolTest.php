<?php

namespace Tests\Feature\Mcp;

use App\Mcp\Servers\ErpMcpServer;
use App\Mcp\Tools\GetStockTool;
use App\Models\InventoryStock;
use App\Models\Item;
use App\Models\ServiceIdentity;
use App\Models\User;
use App\Models\Warehouse;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Bus;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Queue;
use Illuminate\Support\Str;
use Mockery;
use ReflectionClass;
use RuntimeException;
use Tests\TestCase;

class GetStockToolTest extends TestCase
{
    use RefreshDatabase;

    private bool|string $previousToken;

    protected function setUp(): void
    {
        parent::setUp();
        $this->previousToken = getenv('HERMES_MCP_SERVICE_TOKEN');
        putenv('HERMES_MCP_SERVICE_TOKEN');
    }

    protected function tearDown(): void
    {
        if ($this->previousToken === false) {
            putenv('HERMES_MCP_SERVICE_TOKEN');
        } else {
            putenv('HERMES_MCP_SERVICE_TOKEN='.$this->previousToken);
        }

        parent::tearDown();
    }

    public function test_server_explicitly_registers_only_read_only_get_stock(): void
    {
        $tools = ErpMcpServer::tools();
        $itemsProperty = (new ReflectionClass($tools))->getProperty('items');
        $itemsProperty->setAccessible(true);
        $items = $itemsProperty->getValue($tools);

        $this->assertCount(1, $items);
        $this->assertSame('get_stock', $items[0]['name']);
        $this->assertTrue($items[0]['annotations']['readOnlyHint']);
        $this->assertSame(['sku', 'warehouse'], array_keys($items[0]['inputSchema']['properties']));
    }

    public function test_missing_and_invalid_process_token_are_denied(): void
    {
        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
            ->assertHasErrors(['Service authentication required.']);

        putenv('HERMES_MCP_SERVICE_TOKEN=invalid-token');

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
            ->assertHasErrors(['Service authentication required.']);
    }

    public function test_expired_and_revoked_service_tokens_are_denied(): void
    {
        [, $expired] = $this->createIdentityToken(expiresAt: now()->subMinute());
        putenv('HERMES_MCP_SERVICE_TOKEN='.$expired);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
            ->assertHasErrors(['Service authentication required.']);

        DB::table('service_identities')->delete();
        [$identity, $revoked] = $this->createIdentityToken();
        $identity->tokens()->where('name', 'feature-test')->delete();
        putenv('HERMES_MCP_SERVICE_TOKEN='.$revoked);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
            ->assertHasErrors(['Service authentication required.']);
    }

    public function test_human_user_token_is_not_a_service_identity(): void
    {
        $user = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'MCP-HUMAN-TEST',
        ]);
        $secret = Str::random(40);
        $tokenId = DB::table('personal_access_tokens')->insertGetId([
            'tokenable_type' => User::class,
            'tokenable_id' => $user->id,
            'name' => 'human-token',
            'token' => hash('sha256', $secret),
            'abilities' => json_encode(['mcp:inventory:read']),
            'last_used_at' => null,
            'expires_at' => null,
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        putenv('HERMES_MCP_SERVICE_TOKEN='.$tokenId.'|'.$secret);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
            ->assertHasErrors(['Service authentication required.']);
    }

    public function test_service_identity_without_the_exact_read_ability_is_denied(): void
    {
        $identity = ServiceIdentity::create([
            'slug' => 'hermes-agent',
            'name' => 'Hermes Agent',
            'is_active' => true,
        ]);

        foreach ([
            ['mcp:inventory:other'],
            ['*'],
            ['mcp:*'],
            ['mcp:inventory:read', 'mcp:inventory:write'],
        ] as $abilities) {
            $token = $identity->createToken('ability-test', $abilities);
            putenv('HERMES_MCP_SERVICE_TOKEN='.$token->plainTextToken);

            ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
                ->assertHasErrors(['not authorized']);

            $token->accessToken->delete();
        }

        $identity->update(['is_active' => false]);
        $inactiveToken = $identity->createToken('inactive-test', ['mcp:inventory:read']);
        putenv('HERMES_MCP_SERVICE_TOKEN='.$inactiveToken->plainTextToken);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A'])
            ->assertHasErrors(['not authorized']);
    }

    public function test_get_stock_uses_the_shared_per_token_rate_limit(): void
    {
        [, $token] = $this->createIdentityToken();
        putenv('HERMES_MCP_SERVICE_TOKEN='.$token);
        $item = Item::create([
            'code' => 'SKU-LIMIT', 'name' => 'Rate limit fixture', 'unit' => 'pcs', 'type' => 'material', 'active' => true,
        ]);
        $warehouse = Warehouse::create([
            'code' => 'WH-LIMIT', 'name' => 'Limit warehouse', 'type' => 'internal', 'active' => true,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouse->id, 'item_id' => $item->id, 'qty' => 2, 'allocated_qty' => 0,
        ]);

        for ($attempt = 0; $attempt < 30; $attempt++) {
            ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-LIMIT'])->assertOk();
        }

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-LIMIT'])
            ->assertHasErrors(['Too many inventory requests.']);
    }

    public function test_unexpected_inventory_error_returns_a_fixed_message_without_internal_details(): void
    {
        [, $token] = $this->createIdentityToken();
        putenv('HERMES_MCP_SERVICE_TOKEN='.$token);
        $inventory = Mockery::mock(\App\Services\Inventory\InventoryService::class);
        $inventory->shouldReceive('getStockPositionForSku')
            ->once()
            ->andThrow(new RuntimeException('SQL credentials at /srv/private/database.php'));
        app()->instance(\App\Services\Inventory\InventoryService::class, $inventory);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-PRIVATE'])
            ->assertHasErrors(['Stock lookup failed.'])
            ->assertDontSee(['SQL credentials', '/srv/private/database.php']);
    }

    public function test_get_stock_contract_is_read_only_and_does_not_expose_private_fields(): void
    {
        [, $token] = $this->createIdentityToken();
        putenv('HERMES_MCP_SERVICE_TOKEN='.$token);
        $tokenId = (int) explode('|', $token, 2)[0];
        Http::preventStrayRequests();
        Bus::fake();
        Queue::fake();

        $item = Item::create([
            'code' => 'K7BLK',
            'name' => 'Private item name',
            'unit' => 'pcs',
            'type' => 'finished_good',
            'active' => true,
            'hpp' => 98765,
        ]);
        $warehouseA = Warehouse::create([
            'code' => 'WH-PRD', 'name' => 'Production', 'type' => 'internal', 'active' => true,
        ]);
        $warehouseB = Warehouse::create([
            'code' => 'WH-SEC', 'name' => 'Secondary', 'type' => 'internal', 'active' => true,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouseA->id, 'item_id' => $item->id, 'qty' => 150, 'allocated_qty' => 23,
        ]);
        InventoryStock::create([
            'warehouse_id' => $warehouseB->id, 'item_id' => $item->id, 'qty' => 50, 'allocated_qty' => 7,
        ]);

        $stockBefore = InventoryStock::query()->orderBy('warehouse_id')->get(['warehouse_id', 'item_id', 'qty', 'allocated_qty'])->toArray();
        $mutationCountBefore = DB::table('inventory_mutations')->count();
        $lastUsedBefore = DB::table('personal_access_tokens')->where('id', $tokenId)->value('last_used_at');
        Log::spy();

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'K7BLK'])
            ->assertOk()
            ->assertStructuredContent([
                'sku' => 'K7BLK',
                'warehouses' => [
                    ['warehouse' => 'WH-PRD', 'on_hand' => 150.0, 'allocated' => 23.0, 'available' => 127.0],
                    ['warehouse' => 'WH-SEC', 'on_hand' => 50.0, 'allocated' => 7.0, 'available' => 43.0],
                ],
                'total' => ['on_hand' => 200.0, 'allocated' => 30.0, 'available' => 170.0],
            ])
            ->assertDontSee(['Private item name', '98765', 'unit_cost', 'supplier']);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'K7BLK', 'warehouse' => 'WH-PRD'])
            ->assertOk()
            ->assertStructuredContent([
                'sku' => 'K7BLK',
                'warehouse' => 'WH-PRD',
                'on_hand' => 150.0,
                'allocated' => 23.0,
                'available' => 127.0,
            ]);

        $this->assertSame($stockBefore, InventoryStock::query()->orderBy('warehouse_id')->get(['warehouse_id', 'item_id', 'qty', 'allocated_qty'])->toArray());
        $this->assertSame($mutationCountBefore, DB::table('inventory_mutations')->count());
        $this->assertSame($lastUsedBefore, DB::table('personal_access_tokens')->where('id', $tokenId)->value('last_used_at'));
        Bus::assertNothingDispatched();
        Queue::assertNothingPushed();
        Log::shouldHaveReceived('info')->atLeast()->once()->with('machine.request', Mockery::on(
            fn (array $context): bool => $context['service_identity'] === 'hermes-agent'
                && $context['operation'] === 'get_stock'
                && $context['status'] === 200
                && $context['sku'] === 'K7BLK'
                && ! array_key_exists('token', $context)
                && ! array_key_exists('authorization', $context)
        ));
    }

    public function test_missing_sku_unknown_or_inactive_warehouse_and_invalid_input_are_safe_errors(): void
    {
        [, $token] = $this->createIdentityToken();
        putenv('HERMES_MCP_SERVICE_TOKEN='.$token);
        Item::create([
            'code' => 'SKU-A', 'name' => 'Item A', 'unit' => 'pcs', 'type' => 'material', 'active' => true,
        ]);
        Warehouse::create([
            'code' => 'WH-OFF', 'name' => 'Inactive warehouse', 'type' => 'internal', 'active' => false,
        ]);

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'MISSING'])->assertHasErrors(['SKU not found.']);
        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A', 'warehouse' => 'MISSING'])->assertHasErrors(['Warehouse not found.']);
        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A', 'warehouse' => 'WH-OFF'])->assertHasErrors(['Warehouse not found.']);
        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'bad sku'])->assertHasErrors(['Invalid input.']);
        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A', 'extra' => 'private'])->assertHasErrors(['Invalid input.']);
    }

    public function test_audit_log_records_safe_tool_context_without_credentials_or_raw_arguments(): void
    {
        [, $token] = $this->createIdentityToken();
        putenv('HERMES_MCP_SERVICE_TOKEN='.$token);
        Item::create([
            'code' => 'SKU-A', 'name' => 'Item A', 'unit' => 'pcs', 'type' => 'material', 'active' => true,
        ]);
        Log::spy();

        ErpMcpServer::tool(GetStockTool::class, ['sku' => 'SKU-A', 'warehouse' => 'WH-PRD'])
            ->assertHasErrors(['Warehouse not found.']);

        Log::shouldHaveReceived('info')->once()->with('machine.request', Mockery::on(
            fn (array $context): bool => $context['operation'] === 'get_stock'
                && $context['status'] === 404
                && $context['sku'] === 'SKU-A'
                && $context['warehouse'] === 'WH-PRD'
                && ! array_key_exists('token', $context)
                && ! array_key_exists('authorization', $context)
        ));
    }

    /** @return array{ServiceIdentity,string} */
    private function createIdentityToken(
        array $abilities = ['mcp:inventory:read'],
        mixed $expiresAt = null,
    ): array {
        $identity = ServiceIdentity::create([
            'slug' => 'hermes-agent',
            'name' => 'Hermes Agent',
            'is_active' => true,
        ]);
        $token = $identity->createToken('feature-test', $abilities, $expiresAt);

        return [$identity, $token->plainTextToken];
    }
}
