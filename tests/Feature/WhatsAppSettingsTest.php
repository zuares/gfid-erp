<?php

namespace Tests\Feature;

use App\Models\SystemSetting;
use App\Models\User;
use App\Models\WhatsAppMessage;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Mockery;
use Tests\TestCase;

class WhatsAppSettingsTest extends TestCase
{
    use RefreshDatabase;

    public function test_owner_can_open_whatsapp_settings(): void
    {
        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-WA-' . uniqid(),
        ]);

        $this->actingAs($owner)
            ->get(route('settings.whatsapp.index'))
            ->assertOk()
            ->assertSee('Pengaturan WhatsApp')
            ->assertSee('Kirim pesan test');
    }

    public function test_non_owner_cannot_open_whatsapp_settings(): void
    {
        $admin = User::factory()->create([
            'role' => 'admin',
            'employee_code' => 'ADMIN-WA-' . uniqid(),
        ]);

        $this->actingAs($admin)
            ->get(route('settings.whatsapp.index'))
            ->assertForbidden();
    }

    public function test_owner_can_send_test_message_and_settings_are_saved(): void
    {
        config(['services.fonnte.token' => 'test-token']);

        $owner = User::factory()->create([
            'role' => 'owner',
            'employee_code' => 'OWNER-WA-TEST-' . uniqid(),
        ]);
        $service = Mockery::mock(WhatsAppMessageService::class);
        $service->shouldReceive('sendText')
            ->once()
            ->with('08123456789', 'Pesan test', Mockery::type('array'), null)
            ->andReturnUsing(function () {
                $message = Mockery::mock(WhatsAppMessage::class);
                $message->shouldReceive('isSent')->andReturn(true);
                return $message;
            });
        $this->app->instance(WhatsAppMessageService::class, $service);

        $this->actingAs($owner)
            ->post(route('settings.whatsapp.test'), [
                'test_number' => '08123456789',
                'test_message' => 'Pesan test',
            ])
            ->assertRedirect(route('settings.whatsapp.index'))
            ->assertSessionHas('success');

        $this->assertSame('08123456789', SystemSetting::get(SystemSetting::KEY_WHATSAPP_TEST_NUMBER));
        $this->assertSame('Pesan test', SystemSetting::get(SystemSetting::KEY_WHATSAPP_TEST_MESSAGE));
    }
}
