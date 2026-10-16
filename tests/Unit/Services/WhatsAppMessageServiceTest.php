<?php

namespace Tests\Unit\Services;

use App\Models\WhatsAppMessage;
use App\Services\WaNotificationService;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppMessageServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_send_text_normalizes_phone_and_records_success(): void
    {
        $transport = Mockery::mock(WaNotificationService::class);
        $transport->shouldReceive('sendMessage')
            ->once()
            ->with('08123456789', 'Pesan umum')
            ->andReturn(true);

        $log = (new WhatsAppMessageService($transport))->sendText(
            '08123456789',
            'Pesan umum',
            ['module' => 'manual', 'reference_label' => 'Test service'],
            'Penerima Test',
            'test_template',
        );

        $this->assertInstanceOf(WhatsAppMessage::class, $log);
        $this->assertSame('sent', $log->status);
        $this->assertSame('628123456789', $log->recipient_phone);
        $this->assertSame('test_template', $log->template_key);
        $this->assertDatabaseHas('whatsapp_messages', [
            'recipient_phone' => '628123456789',
            'status' => 'sent',
            'reference_label' => 'Test service',
        ]);
    }

    public function test_send_text_records_failure_without_throwing(): void
    {
        $transport = Mockery::mock(WaNotificationService::class);
        $transport->shouldReceive('sendMessage')
            ->once()
            ->andReturn(false);

        $log = (new WhatsAppMessageService($transport))->sendText('628123456789', 'Pesan gagal');

        $this->assertSame('failed', $log->status);
        $this->assertNotNull($log->error_message);
        $this->assertDatabaseHas('whatsapp_messages', [
            'recipient_phone' => '628123456789',
            'status' => 'failed',
        ]);
    }

    public function test_send_broadcast_uses_one_transport_request_and_logs_each_recipient(): void
    {
        $transport = Mockery::mock(WaNotificationService::class);
        $transport->shouldReceive('sendMessages')
            ->once()
            ->with(['08123456789', '628987654321'], 'Pesan broadcast')
            ->andReturn(true);

        $logs = (new WhatsAppMessageService($transport))->sendBroadcast(
            ['08123456789', '628987654321'],
            'Pesan broadcast',
            ['module' => 'broadcast', 'reference_label' => 'Broadcast test'],
        );

        $this->assertCount(2, $logs);
        $this->assertSame(['628123456789', '628987654321'], collect($logs)->pluck('recipient_phone')->all());
        $this->assertTrue(collect($logs)->every(fn (WhatsAppMessage $log): bool => $log->isSent()));
        $this->assertDatabaseCount('whatsapp_messages', 2);
    }
}
