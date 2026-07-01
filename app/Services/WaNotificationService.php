<?php

namespace App\Services;

use App\Models\StorefrontOrder;

class WaNotificationService
{
    /**
     * Kirim notif WA ke admin saat order baru masuk.
     * Menggunakan Fonnte API (https://fonnte.com).
     *
     * Konfigurasi via .env:
     *   ADMIN_WA_NUMBER=628xxxxxxxxxx   (format internasional, tanpa +)
     *   FONNTE_TOKEN=xxxxxxxxxxxxxx
     */
    public function sendOrderNotification(StorefrontOrder $order): void
    {
        $adminPhone = env('ADMIN_WA_NUMBER');
        $token      = env('FONNTE_TOKEN');

        if (! $adminPhone || ! $token) {
            return; // Notif tidak dikonfigurasi — skip
        }

        $items = collect($order->items ?? [])->map(function ($i) {
            $color = $i['color'] ?? '';
            $size  = $i['size']  ?? '';
            $qty   = $i['qty']   ?? 1;
            $name  = $i['name']  ?? '-';
            return "• {$name}" . ($color || $size ? " ({$color}/{$size})" : '') . " ×{$qty}";
        })->join("\n");

        $total = 'Rp' . number_format($order->total_amount, 0, ',', '.');

        $message = "🛒 *Order Baru Masuk!*\n\n"
            . "📋 *{$order->order_number}*\n"
            . "👤 {$order->customer_name}\n"
            . "📞 {$order->customer_phone}\n"
            . "📍 {$order->city}\n"
            . "💳 {$order->payment_method}\n"
            . "💰 {$total}\n\n"
            . "*Produk:*\n{$items}\n\n"
            . "🔗 " . route('admin.crm.orders');

        $this->sendFonnte($token, $adminPhone, $message);
    }

    /**
     * Kirim pesan WA ke admin (nomor dari ADMIN_WA_NUMBER).
     */
    public function sendToAdmin(string $message): void
    {
        $adminPhone = env('ADMIN_WA_NUMBER');
        $token      = env('FONNTE_TOKEN');
        if (! $adminPhone || ! $token) return;

        $this->sendFonnte($token, $adminPhone, $message);
    }

    private function sendFonnte(string $token, string $target, string $message): void
    {
        try {
            $ch = curl_init('https://api.fonnte.com/send');
            curl_setopt_array($ch, [
                CURLOPT_POST           => true,
                CURLOPT_RETURNTRANSFER => true,
                CURLOPT_TIMEOUT        => 8,
                CURLOPT_CONNECTTIMEOUT => 5,
                CURLOPT_HTTPHEADER     => ["Authorization: {$token}"],
                CURLOPT_POSTFIELDS     => http_build_query([
                    'target'  => $target,
                    'message' => $message,
                ]),
            ]);
            curl_exec($ch);
            curl_close($ch);
        } catch (\Throwable) {
            // Jangan pernah WA error mengganggu order flow
        }
    }
}
