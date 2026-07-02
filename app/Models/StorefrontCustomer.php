<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class StorefrontCustomer extends Model
{
    protected $fillable = [
        'name',
        'phone',
        'email',
        'otp_code',
        'otp_expires_at',
        'phone_verified_at',
    ];

    protected $casts = [
        'otp_expires_at'    => 'datetime',
        'phone_verified_at' => 'datetime',
    ];

    protected $hidden = ['otp_code', 'remember_token'];

    // ── Relationships ────────────────────────────────────────────────────────

    public function orders(): HasMany
    {
        // Match by phone (stored 628xxx) or 08xxx variant
        return HasMany::make(StorefrontOrder::class, 'customer_phone', 'phone');
    }

    // ── Helpers ─────────────────────────────────────────────────────────────

    /** Phone display: +62 812 3456 7890 */
    public function getPhoneDisplayAttribute(): string
    {
        return '+' . $this->phone;
    }

    /** Inisial nama untuk avatar */
    public function getInitialAttribute(): string
    {
        return strtoupper(mb_substr($this->name, 0, 1));
    }

    /** Nama depan saja */
    public function getFirstNameAttribute(): string
    {
        return explode(' ', $this->name)[0];
    }

    /**
     * Role berdasarkan aktivitas:
     * - 'customer'  → punya minimal 1 pesanan
     * - 'prospect'  → terdaftar, belum pernah order
     * - 'visitor'   → baru daftar, belum ada aktivitas
     */
    public function getCustomerRoleAttribute(): string
    {
        $phone    = $this->phone;
        $phoneAlt = str_starts_with($phone, '62') ? '0' . substr($phone, 2) : $phone;

        $hasOrder = StorefrontOrder::where('customer_phone', $phone)
            ->orWhere('customer_phone', $phoneAlt)
            ->exists();

        return $hasOrder ? 'customer' : 'prospect';
    }

    // ── OTP ─────────────────────────────────────────────────────────────────

    public function generateOtp(): string
    {
        $code = str_pad(random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $this->update([
            'otp_code'       => $code,
            'otp_expires_at' => now()->addMinutes(10),
        ]);

        return $code;
    }

    public function verifyOtp(string $code): bool
    {
        if (!$this->otp_code || !$this->otp_expires_at) return false;
        if ($this->otp_expires_at->isPast()) return false;
        if (!hash_equals($this->otp_code, $code)) return false;

        $this->update([
            'otp_code'          => null,
            'otp_expires_at'    => null,
            'phone_verified_at' => now(),
        ]);

        return true;
    }

    // ── Static helpers ───────────────────────────────────────────────────────

    /** Normalisasi nomor HP ke format 628xxx */
    public static function normalizePhone(string $raw): string
    {
        $phone = preg_replace('/\D/', '', trim($raw));

        if (str_starts_with($phone, '0')) {
            return '62' . substr($phone, 1);
        }
        if (str_starts_with($phone, '8')) {
            return '62' . $phone;
        }
        return $phone; // already 62xxx
    }
}
