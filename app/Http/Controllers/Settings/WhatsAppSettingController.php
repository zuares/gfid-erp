<?php

namespace App\Http\Controllers\Settings;

use App\Http\Controllers\Controller;
use App\Models\SystemSetting;
use App\Services\WhatsApp\WhatsAppMessageService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class WhatsAppSettingController extends Controller
{
    public function index(): View
    {
        return view('settings.whatsapp.index', [
            'testNumber' => SystemSetting::get(SystemSetting::KEY_WHATSAPP_TEST_NUMBER, ''),
            'testMessage' => SystemSetting::get(
                SystemSetting::KEY_WHATSAPP_TEST_MESSAGE,
                'Tes pesan WhatsApp dari Greatfit ERP.'
            ),
            'isConfigured' => filled(config('services.fonnte.token')),
        ]);
    }

    public function sendTest(Request $request, WhatsAppMessageService $whatsapp): RedirectResponse
    {
        $data = $request->validate([
            'test_number' => ['required', 'string', 'max:30'],
            'test_message' => ['required', 'string', 'max:1000'],
        ]);

        SystemSetting::set(
            SystemSetting::KEY_WHATSAPP_TEST_NUMBER,
            $data['test_number'],
            'Nomor penerima pesan test WhatsApp Fonnte',
            auth()->id()
        );
        SystemSetting::set(
            SystemSetting::KEY_WHATSAPP_TEST_MESSAGE,
            $data['test_message'],
            'Isi pesan test WhatsApp Fonnte',
            auth()->id()
        );

        if (! filled(config('services.fonnte.token'))) {
            return redirect()
                ->route('settings.whatsapp.index')
                ->withInput()
                ->with('error', 'FONNTE_TOKEN belum dikonfigurasi di file .env.');
        }

        $messageLog = $whatsapp->sendText(
            $data['test_number'],
            $data['test_message'],
            [
                'module' => 'settings',
                'reference_type' => self::class,
                'reference_label' => 'Test koneksi WhatsApp',
            ],
            null,
        );
        $sent = $messageLog->isSent();

        return redirect()
            ->route('settings.whatsapp.index')
            ->withInput()
            ->with(
                $sent ? 'success' : 'error',
                $sent
                    ? 'Pesan test berhasil dikirim melalui Fonnte.'
                    : 'Pesan test gagal dikirim. Periksa token, nomor, dan log aplikasi.'
            );
    }
}
