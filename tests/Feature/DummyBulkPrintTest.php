<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Tests\TestCase;
use App\Models\User;

class DummyBulkPrintTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        // Since we are mocking dev behavior, we force local environment for tests
        app()->detectEnvironment(fn() => 'local');
    }

    public function test_dummy_endpoint_returns_json_response()
    {
        $user = User::first() ?? User::forceCreate(["name" => "Test", "email" => "test@example.com", "password" => "test", "role" => "admin", "employee_code" => "EMP001", ]);

        $payload = [
            'orders' => [
                [
                    'store_id' => 101,
                    'order_sn' => 'DUMMY-A-001',
                    'position' => 0,
                ],
                [
                    'store_id' => 101,
                    'order_sn' => 'DUMMY-A-004', // Document not ready
                    'position' => 1,
                ]
            ],
            'mode' => 'selected'
        ];

        $response = $this->actingAs($user)
            ->postJson('/dev/dummy/bulk-print', $payload);

        $response->assertStatus(200)
                 ->assertJsonStructure(['uuid']);
                 
        $uuid = $response->json('uuid');
        $cache = \Illuminate\Support\Facades\Cache::get('bulk_print_' . $uuid);
        
        $this->assertNotNull($cache);
        $this->assertEquals(1, $cache['success_count']); // DUMMY-A-001 success
        $this->assertEquals(1, $cache['failed_count']); // DUMMY-A-004 failed
        $this->assertCount(1, $cache['failed_orders']);
        $this->assertEquals('DUMMY-A-004', $cache['failed_orders'][0]['order_sn']);
    }
    
    public function test_dummy_endpoint_is_blocked_in_production()
    {
        app()->detectEnvironment(fn() => 'production');
        
        $user = User::first() ?? User::forceCreate(["name" => "Test", "email" => "test@example.com", "password" => "test", "role" => "admin", "employee_code" => "EMP001", ]);

        $payload = [
            'orders' => [
                [
                    'store_id' => 101,
                    'order_sn' => 'DUMMY-A-001',
                    'position' => 0,
                ]
            ],
            'mode' => 'selected'
        ];

        $response = $this->actingAs($user)
            ->postJson('/dev/dummy/bulk-print', $payload);

        $response->assertStatus(404);
    }
}
