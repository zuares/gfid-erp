<?php

namespace App\Services\WhatsApp;

use App\Models\WhatsAppMessage;
use App\Services\WaNotificationService;
use Illuminate\Database\Eloquent\Model;

class WhatsAppMessageService
{
    public function __construct(
        private readonly WaNotificationService $transport,
    ) {}

    /**
     * Kirim pesan teks dan simpan status pengiriman untuk semua modul.
     */
    public function sendText(
        string $phone,
        string $message,
        array $context = [],
        ?string $recipientName = null,
        ?string $templateKey = null,
    ): WhatsAppMessage {
        $log = WhatsAppMessage::create([
            'direction' => 'outbound',
            'provider' => 'fonnte',
            'recipient_phone' => $this->normalizePhone($phone),
            'recipient_name' => $recipientName,
            'message' => $message,
            'module' => $context['module'] ?? null,
            'reference_type' => $context['reference_type'] ?? null,
            'reference_id' => $context['reference_id'] ?? null,
            'reference_label' => $context['reference_label'] ?? null,
            'template_key' => $templateKey,
            'status' => 'pending',
            'sent_by' => auth()->id(),
        ]);

        try {
            $sent = $this->transport->sendMessage($phone, $message);
        } catch (\Throwable $e) {
            $sent = false;
            $errorMessage = $e->getMessage();
        }

        $log->forceFill([
            'status' => $sent ? 'sent' : 'failed',
            'sent_at' => $sent ? now() : null,
            'error_message' => $sent ? null : ($errorMessage ?? 'Provider WhatsApp mengembalikan status gagal.'),
        ])->save();

        return $log->fresh();
    }

    private function normalizePhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone) ?? '';

        return str_starts_with($phone, '0')
            ? '62' . substr($phone, 1)
            : $phone;
    }
}
