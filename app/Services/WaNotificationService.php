<?php

namespace App\Services;

use App\Models\StorefrontOrder;
use Illuminate\Support\Facades\Log;

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

    /**
     * Kirim pesan WA ke semua user dengan role 'operating'.
     */
    public function sendToOperatingRole(string $message): void
    {
        $token = env('FONNTE_TOKEN');
        if (! $token) return;

        $operatings = \App\Models\User::where('role', 'operating')
            ->whereHas('employee', fn($q) => $q->whereNotNull('phone'))
            ->with('employee')
            ->get();

        foreach ($operatings as $user) {
            $phone = $user->employee->phone ?? null;
            if ($phone) {
                $this->sendFonnte($token, $phone, $message);
            }
        }
    }

    /**
     * Kirim OTP via WhatsApp ke nomor customer.
     * Fallback ke log jika Fonnte belum dikonfigurasi.
     */
    public function sendOtp(string $phone, string $otp, string $name = ''): bool
    {
        $greeting = $name ? "Hai *{$name}*! 👋\n\n" : '';
        $message  = $greeting
            . "Kode verifikasi Greatfit kamu:\n\n"
            . "🔐 *{$otp}*\n\n"
            . "Kode berlaku 10 menit. Jangan bagikan ke siapapun.";

        $token = env('FONNTE_TOKEN');
        if ($token) {
            $sent = $this->sendFonnte($token, $phone, $message);
            if (! $sent) {
                Log::warning('OTP WhatsApp gagal dikirim via Fonnte', [
                    'target' => $this->maskPhone($phone),
                ]);
            }
            return $sent;
        } else {
            // Fallback: log ke Laravel log (dev mode) — OTP tidak ditulis ke log
            Log::info('[OTP] Dev fallback — OTP sent', [
                'target' => $this->maskPhone($phone),
                'event'  => 'otp_dev_fallback',
            ]);
            return true;
        }
    }

    private function sendFonnte(string $token, string $target, string $message): bool
    {
        $target = preg_replace('/\D/', '', $target);

        try {
            $ch = curl_init('https://api.fonnte.com/send');
            if (! $ch) {
                Log::warning('Fonnte curl_init gagal');
                return false;
            }

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

            $body = curl_exec($ch);
            $errno = curl_errno($ch);
            $error = curl_error($ch);
            $status = (int) curl_getinfo($ch, CURLINFO_HTTP_CODE);
            curl_close($ch);

            if ($errno) {
                Log::warning('Fonnte request error', [
                    'target' => $this->maskPhone($target),
                    'errno'  => $errno,
                    'error'  => $error,
                ]);
                return false;
            }

            $decoded = is_string($body) ? json_decode($body, true) : null;
            $isSuccess = $status >= 200 && $status < 300 && (
                (is_array($decoded) && (($decoded['status'] ?? null) === true || ($decoded['status'] ?? null) === 'success'))
                || (is_string($body) && str_contains(strtolower($body), 'success'))
            );

            if (! $isSuccess) {
                Log::warning('Fonnte response tidak sukses', [
                    'target' => $this->maskPhone($target),
                    'status' => $status,
                    'body'   => is_string($body) ? mb_substr($body, 0, 500) : null,
                ]);
                return false;
            }

            return true;
        } catch (\Throwable $e) {
            // Jangan pernah WA error mengganggu order flow
            Log::warning('Fonnte exception', [
                'target' => $this->maskPhone($target),
                'error'  => $e->getMessage(),
            ]);
            return false;
        }
    }

    private function maskPhone(string $phone): string
    {
        $phone = preg_replace('/\D/', '', $phone);
        if (strlen($phone) <= 6) {
            return $phone;
        }

        return substr($phone, 0, 4) . str_repeat('*', max(0, strlen($phone) - 7)) . substr($phone, -3);
    }
}
