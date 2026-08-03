<?php

namespace Tests\Feature;

use App\Jobs\ProcessShopeeChatWebhookJob;
use App\Models\Channel;
use App\Models\MarketplaceChatMessage;
use App\Models\MarketplaceConversation;
use App\Models\Store;
use App\Models\WebhookLog;
use App\Models\User;
use App\Services\Channels\ChannelManager;
use App\Services\Channels\Contracts\MarketplaceChannel;
use App\Services\MarketplaceChatService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class MarketplaceChatAuditTest extends TestCase
{
    use RefreshDatabase;

    private function createShopeeStore(array $overrides = []): Store
    {
        $channel = Channel::firstOrCreate(
            ['code' => 'shopee'],
            ['name' => 'Shopee']
        );

        return Store::create(array_merge([
            'channel_id' => $channel->id,
            'code' => 'S' . random_int(1000, 9999),
            'name' => 'Toko Uji',
            'status' => 'active',
            'is_active' => true,
            'external_shop_id' => '12345',
            'credentials' => [
                'partner_id'   => '2000000',
                'partner_key'  => 'dummy_key',
                'shop_id'      => '12345',
                'access_token' => 'dummy_access_token',
                'refresh_token' => 'dummy_refresh_token',
            ],
            'token_expires_at' => now()->addHours(2),
        ], $overrides));
    }

    private function bindDriver(MarketplaceChannel $driver): void
    {
        $manager = \Mockery::mock(ChannelManager::class);
        $manager->shouldReceive('driver')->andReturn($driver);
        $this->app->instance(ChannelManager::class, $manager);
    }

    public function test_webhook_message_persisted_with_raw_payload_context_and_webhook_log_link(): void
    {
        $store = $this->createShopeeStore();

        $content = [
            'conversation_id' => 'conv-1',
            'message_id' => 'msg-1',
            'message_type' => 'text',
            'from_id' => 'buyer-1',
            'to_id' => '12345',
            'content' => ['text' => 'Halo, apakah ready?'],
        ];

        $rawPayload = [
            'code' => 10,
            'shop_id' => '12345',
            'data' => [
                'type' => 'message',
                'content' => $content,
            ],
        ];

        $webhookLog = WebhookLog::create([
            'provider' => 'shopee',
            'event_type' => 'webchat_push',
            'signature_verified' => true,
            'payload' => $rawPayload,
            'ip_address' => '127.0.0.1',
        ]);

        $job = new ProcessShopeeChatWebhookJob($rawPayload, json_encode($rawPayload), $webhookLog->id);
        $job->handle(app(MarketplaceChatService::class));

        $message = MarketplaceChatMessage::first();
        $this->assertNotNull($message);
        $this->assertSame('webhook', $message->source);
        $this->assertSame('conv-1', $message->external_conversation_id);
        $this->assertSame('buyer', $message->from_role);
        $this->assertSame('msg-1', $message->external_message_id);
        $this->assertSame('Halo, apakah ready?', data_get($message->raw_payload, 'content.text'));
        $this->assertSame(10, data_get($message->raw_context, 'webhook.code'));
        $this->assertSame('message', data_get($message->raw_context, 'webhook.data.type'));
        $this->assertSame($webhookLog->id, $message->webhook_log_id);
        $this->assertSame($webhookLog->id, data_get($message->raw_context, 'webhook_log_id'));
        $this->assertSame('Halo, apakah ready?', $message->text);
        $this->assertSame(1, MarketplaceConversation::count());
        $this->assertSame(1, MarketplaceChatMessage::count());
    }

    public function test_raw_message_endpoint_returns_webhook_and_message_payloads(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'DEV']));

        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-api',
            'buyer_user_id' => 'buyer-api',
            'buyer_username' => 'buyer-api',
        ]);

        $webhookLog = WebhookLog::create([
            'provider' => 'shopee',
            'event_type' => 'webchat_push',
            'signature_verified' => true,
            'payload' => [
                'shop_id' => '12345',
                'data' => [
                    'type' => 'message',
                    'content' => [
                        'message_id' => 'msg-api',
                    ],
                ],
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $message = MarketplaceChatMessage::create([
            'marketplace_conversation_id' => $conversation->id,
            'store_id' => $store->id,
            'source' => 'webhook',
            'external_conversation_id' => 'conv-api',
            'external_message_id' => 'msg-api',
            'from_role' => 'buyer',
            'from_id' => 'buyer-api',
            'message_type' => 'text',
            'text' => 'Tes raw endpoint',
            'content' => ['text' => 'Tes raw endpoint'],
            'raw_payload' => ['message_id' => 'msg-api', 'content' => ['text' => 'Tes raw endpoint']],
            'raw_context' => ['webhook_log_id' => $webhookLog->id],
            'webhook_log_id' => $webhookLog->id,
            'sent_at' => now(),
            'is_read' => false,
        ]);

        $response = $this->getJson("/api/marketplace/chat/messages/{$message->id}/raw");

        $response->assertOk()
            ->assertJsonPath('message.id', $message->id)
            ->assertJsonPath('message.webhook_log_id', $webhookLog->id)
            ->assertJsonPath('webhook_log.id', $webhookLog->id)
            ->assertJsonPath('webhook_log.event_type', 'webchat_push')
            ->assertJsonPath('message.raw_payload.content.text', 'Tes raw endpoint');
    }

    public function test_raw_message_endpoint_synthesizes_legacy_empty_payloads(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'DEV']));

        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-legacy',
            'buyer_user_id' => 'buyer-legacy',
            'buyer_username' => 'buyer-legacy',
        ]);

        $message = MarketplaceChatMessage::create([
            'marketplace_conversation_id' => $conversation->id,
            'store_id' => $store->id,
            'source' => 'sync_api',
            'external_conversation_id' => 'conv-legacy',
            'external_message_id' => 'msg-legacy',
            'from_role' => 'buyer',
            'from_id' => 'buyer-legacy',
            'message_type' => 'bundle_message',
            'text' => 'Legacy bundle',
            'content' => [],
            'raw_payload' => null,
            'raw_context' => null,
            'sent_at' => now(),
            'is_read' => false,
        ]);

        $response = $this->getJson("/api/marketplace/chat/messages/{$message->id}/raw");

        $response->assertOk()
            ->assertJsonPath('message.id', $message->id)
            ->assertJsonPath('message.audit_state', 'synthesized')
            ->assertJsonPath('message.raw_payload.message_id', 'msg-legacy')
            ->assertJsonPath('message.raw_payload.message_type', 'bundle_message')
            ->assertJsonPath('message.raw_context.audit.mode', 'synthesized_from_row');
    }

    public function test_audit_page_filters_by_direction_source_and_webhook_log_id(): void
    {
        $this->actingAs(User::factory()->create(['role' => 'owner', 'employee_code' => 'DEV']));

        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-filter',
            'buyer_user_id' => 'buyer-filter',
            'buyer_username' => 'buyer-filter',
        ]);

        $webhookLog = WebhookLog::create([
            'provider' => 'shopee',
            'event_type' => 'webchat_push',
            'signature_verified' => true,
            'payload' => ['shop_id' => '12345'],
            'ip_address' => '127.0.0.1',
        ]);

        MarketplaceChatMessage::create([
            'marketplace_conversation_id' => $conversation->id,
            'store_id' => $store->id,
            'source' => 'webhook',
            'external_conversation_id' => 'conv-filter',
            'external_message_id' => 'msg-buyer',
            'from_role' => 'buyer',
            'from_id' => 'buyer-filter',
            'message_type' => 'text',
            'text' => 'Buyer message',
            'content' => ['text' => 'Buyer message'],
            'raw_payload' => ['message_id' => 'msg-buyer', 'content' => ['text' => 'Buyer message']],
            'raw_context' => ['webhook_log_id' => $webhookLog->id],
            'webhook_log_id' => $webhookLog->id,
            'sent_at' => now(),
            'is_read' => false,
        ]);

        MarketplaceChatMessage::create([
            'marketplace_conversation_id' => $conversation->id,
            'store_id' => $store->id,
            'source' => 'send_api',
            'external_conversation_id' => 'conv-filter',
            'external_message_id' => 'msg-seller',
            'from_role' => 'seller',
            'from_id' => '12345',
            'message_type' => 'text',
            'text' => 'Seller message',
            'content' => ['text' => 'Seller message'],
            'raw_payload' => ['message_id' => 'msg-seller', 'content' => ['text' => 'Seller message']],
            'raw_context' => ['send_api' => ['response' => ['message_id' => 'msg-seller']]],
            'sent_at' => now()->addMinute(),
            'is_read' => true,
        ]);

        $response = $this->get(route('marketplace.chat.audit', [
            'direction' => 'buyer',
            'source' => 'webhook',
            'webhook_log_id' => $webhookLog->id,
        ]));

        $response->assertOk()
            ->assertSee('msg-buyer')
            ->assertDontSee('msg-seller')
            ->assertSee(route('marketplace.toko', ['log_id' => $webhookLog->id]), false);
    }

    public function test_webhook_log_detail_endpoint_returns_specific_log(): void
    {
        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-log',
            'buyer_user_id' => 'buyer-log',
            'buyer_username' => 'buyer-log',
        ]);

        $webhookLog = WebhookLog::create([
            'provider' => 'shopee',
            'event_type' => 'webchat_push',
            'signature_verified' => true,
            'payload' => [
                'shop_id' => '12345',
                'data' => ['type' => 'message'],
            ],
            'ip_address' => '127.0.0.1',
        ]);

        $message = MarketplaceChatMessage::create([
            'marketplace_conversation_id' => $conversation->id,
            'store_id' => $store->id,
            'source' => 'webhook',
            'external_conversation_id' => 'conv-log',
            'external_message_id' => 'msg-log',
            'from_role' => 'buyer',
            'from_id' => 'buyer-log',
            'message_type' => 'text',
            'text' => 'Log related',
            'content' => ['text' => 'Log related'],
            'raw_payload' => ['message_id' => 'msg-log'],
            'raw_context' => ['webhook_log_id' => $webhookLog->id],
            'webhook_log_id' => $webhookLog->id,
            'sent_at' => now(),
            'is_read' => false,
        ]);

        $response = $this->getJson("/api/webhooks/logs/{$webhookLog->id}");

        $response->assertOk()
            ->assertJsonPath('id', $webhookLog->id)
            ->assertJsonPath('event_type', 'webchat_push')
            ->assertJsonPath('payload.data.type', 'message')
            ->assertJsonPath('related_messages.0.external_message_id', 'msg-log')
            ->assertJsonPath('related_messages.0.audit_url', route('marketplace.chat.audit', [
                'webhook_log_id' => $webhookLog->id,
                'message_id' => $message->id,
            ]) . '#chat-message-row-' . $message->id);
    }

    public function test_outbound_reply_persisted_with_request_and_response_audit(): void
    {
        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-2',
            'buyer_user_id' => 'buyer-2',
            'buyer_username' => 'buyer-two',
        ]);

        $driver = \Mockery::mock(MarketplaceChannel::class);
        $driver->shouldReceive('sendChatMessage')
            ->once()
            ->with($store, 'buyer-2', 'Baik, siap')
            ->andReturn([
                'response' => [
                    'message_id' => 'msg-900',
                    'conversation_id' => 'conv-2',
                    'created_timestamp' => 1710000000,
                ],
            ]);

        $this->bindDriver($driver);

        $service = app(MarketplaceChatService::class);
        $result = $service->sendText($store, $conversation, 'Baik, siap');

        $this->assertTrue($result['success']);

        $message = MarketplaceChatMessage::first();
        $this->assertNotNull($message);
        $this->assertSame('send_api', $message->source);
        $this->assertSame('seller', $message->from_role);
        $this->assertSame('msg-900', $message->external_message_id);
        $this->assertSame('Baik, siap', data_get($message->raw_payload, 'content.text'));
        $this->assertSame('msg-900', data_get($message->raw_context, 'send_api.response.message_id'));
        $this->assertSame('Baik, siap', $message->text);
        $this->assertSame(1, MarketplaceChatMessage::count());
    }

    public function test_sync_messages_paginates_until_history_exhausted(): void
    {
        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-3',
            'buyer_user_id' => 'buyer-3',
            'buyer_username' => 'buyer-three',
        ]);

        $driver = \Mockery::mock(MarketplaceChannel::class);
        $driver->shouldReceive('getChatMessages')
            ->once()
            ->with(\Mockery::on(fn ($arg) => $arg instanceof Store && $arg->id === $store->id), 'conv-3', 50, '')
            ->andReturn([
                'response' => [
                    'messages' => [
                        [
                            'message_id' => 'msg-1',
                            'conversation_id' => 'conv-3',
                            'from_id' => 'buyer-3',
                            'to_id' => '12345',
                            'message_type' => 'text',
                            'created_timestamp' => 1710000001,
                            'content' => ['text' => 'Pesan 1'],
                        ],
                        [
                            'message_id' => 'msg-2',
                            'conversation_id' => 'conv-3',
                            'from_id' => '12345',
                            'to_id' => 'buyer-3',
                            'message_type' => 'text',
                            'created_timestamp' => 1710000002,
                            'content' => ['text' => 'Pesan 2'],
                        ],
                    ],
                    'page_result' => [
                        'more' => true,
                        'next_offset' => 'cursor-2',
                    ],
                ],
            ]);

        $driver->shouldReceive('getChatMessages')
            ->once()
            ->with(\Mockery::on(fn ($arg) => $arg instanceof Store && $arg->id === $store->id), 'conv-3', 50, 'cursor-2')
            ->andReturn([
                'response' => [
                    'messages' => [
                        [
                            'message_id' => 'msg-3',
                            'conversation_id' => 'conv-3',
                            'from_id' => 'buyer-3',
                            'to_id' => '12345',
                            'message_type' => 'text',
                            'created_timestamp' => 1710000003,
                            'content' => ['text' => 'Pesan 3'],
                        ],
                    ],
                    'page_result' => [
                        'more' => false,
                        'next_offset' => '',
                    ],
                ],
            ]);

        $this->bindDriver($driver);

        $service = app(MarketplaceChatService::class);
        $synced = $service->syncMessages($conversation);

        $this->assertSame(3, $synced);
        $this->assertSame(3, MarketplaceChatMessage::count());
        $this->assertDatabaseHas('marketplace_chat_messages', [
            'external_message_id' => 'msg-3',
            'from_role' => 'buyer',
        ]);
    }

    public function test_empty_webhook_backfills_from_conversation_history(): void
    {
        $store = $this->createShopeeStore();

        $driver = \Mockery::mock(MarketplaceChannel::class);
        $driver->shouldReceive('getConversationList')
            ->once()
            ->andReturn([
                'response' => [
                    'conversations' => [
                        [
                            'conversation_id' => 'conv-4',
                            'to_id' => 'buyer-4',
                            'to_name' => 'buyer-four',
                            'to_avatar' => null,
                            'latest_message_type' => 'text',
                            'latest_message_content' => ['text' => 'Halo'],
                            'last_message_timestamp' => 1710000000000000000,
                            'unread_count' => 1,
                        ],
                    ],
                    'page_result' => [
                        'more' => false,
                        'next_cursor' => ['next_timestamp_nano' => ''],
                    ],
                ],
            ]);

        $driver->shouldReceive('getChatMessages')
            ->times(2)
            ->andReturn([
                'response' => [
                    'messages' => [
                        [
                            'message_id' => 'msg-4',
                            'conversation_id' => 'conv-4',
                            'from_id' => 'buyer-4',
                            'to_id' => '12345',
                            'message_type' => 'text',
                            'created_timestamp' => 1710000004,
                            'content' => ['text' => 'Halo dari buyer'],
                        ],
                    ],
                    'page_result' => [
                        'more' => false,
                        'next_offset' => '',
                    ],
                ],
            ]);

        $this->bindDriver($driver);

        $rawPayload = [
            'shop_id' => '12345',
            'data' => [
                'type' => 'message',
                'conversation_id' => 'conv-4',
                'content' => [],
            ],
        ];

        $job = new ProcessShopeeChatWebhookJob($rawPayload, json_encode($rawPayload));
        $job->handle(app(MarketplaceChatService::class));

        $this->assertSame(1, MarketplaceConversation::count());
        $this->assertSame(1, MarketplaceChatMessage::count());
        $message = MarketplaceChatMessage::first();
        $this->assertSame('sync_api', $message->source);
        $this->assertSame('msg-4', $message->external_message_id);
        $this->assertSame('Halo dari buyer', $message->text);
    }

    public function test_repair_command_backfills_missing_raw_payload_from_linked_webhook_log(): void
    {
        $store = $this->createShopeeStore();
        $conversation = MarketplaceConversation::create([
            'store_id' => $store->id,
            'conversation_id' => 'conv-repair',
            'buyer_user_id' => 'buyer-repair',
            'buyer_username' => 'buyer-repair',
        ]);

        $rawPayload = [
            'shop_id' => '12345',
            'data' => [
                'type' => 'message',
                'content' => [
                    'message_id' => 'msg-repair',
                    'conversation_id' => 'conv-repair',
                    'message_type' => 'text',
                    'content' => ['text' => 'Pesan lama'],
                ],
            ],
        ];

        $webhookLog = WebhookLog::create([
            'provider' => 'shopee',
            'event_type' => 'webchat_push',
            'signature_verified' => true,
            'payload' => $rawPayload,
            'ip_address' => '127.0.0.1',
        ]);

        $message = MarketplaceChatMessage::create([
            'marketplace_conversation_id' => $conversation->id,
            'store_id' => $store->id,
            'source' => 'webhook',
            'external_conversation_id' => 'conv-repair',
            'external_message_id' => 'msg-repair',
            'from_role' => 'buyer',
            'from_id' => 'buyer-repair',
            'message_type' => 'text',
            'text' => 'Pesan lama',
            'content' => ['text' => 'Pesan lama'],
            'webhook_log_id' => $webhookLog->id,
            'sent_at' => now()->subDays(10),
            'is_read' => false,
        ]);

        $this->artisan('marketplace:repair-chat-raw-payloads', [
            '--apply' => true,
            '--limit' => 10,
            '--store' => $store->id,
        ])->assertExitCode(0);

        $message->refresh();

        $this->assertSame($rawPayload, $message->raw_payload);
        $this->assertSame($webhookLog->id, $message->webhook_log_id);
        $this->assertSame('linked_webhook_log', data_get($message->raw_context, 'repair.mode'));
    }
}
