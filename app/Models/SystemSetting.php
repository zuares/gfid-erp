<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Cache;

/**
 * System Settings — key-value store untuk konfigurasi global.
 *
 * Keys baku:
 *  - system_cutoff_date  : YYYY-MM-DD — tanggal cut-off (semua modul)
 *  - system_cutoff_notes : teks bebas keterangan cut-off
 *  - shipment_scan_sound_enabled : 1/0 — default suara scan pengiriman
 *  - shipment_scan_sound_map : JSON mapping event scan ke preset/ringtone
 *  - sales_lookup_* : kebijakan lookup dan tautan order operasional penjualan
 *
 * @property string      $key
 * @property string|null $value
 * @property string|null $description
 * @property int|null    $updated_by
 */
class SystemSetting extends Model
{
    protected $primaryKey = 'key';
    protected $keyType    = 'string';
    public    $incrementing = false;

    protected $fillable = ['key', 'value', 'description', 'updated_by'];

    // ──────────────────────────────────────────────────────
    // CONSTANTS
    // ──────────────────────────────────────────────────────

    public const KEY_CUTOFF_DATE  = 'system_cutoff_date';
    public const KEY_CUTOFF_NOTES = 'system_cutoff_notes';
    public const KEY_SHIPMENT_SCAN_SOUND = 'shipment_scan_sound_enabled';
    public const KEY_SHIPMENT_SCAN_SOUND_MAP = 'shipment_scan_sound_map';
    public const KEY_SALES_LOOKUP_MODE = 'sales_lookup_mode';
    public const KEY_SALES_LOOKUP_SOURCES = 'sales_lookup_sources';
    public const KEY_SALES_LOOKUP_IDENTIFIERS = 'sales_lookup_identifiers';
    public const KEY_SALES_LOOKUP_SAME_STORE = 'sales_lookup_same_store';
    public const KEY_SALES_LOOKUP_BLOCK_DUPLICATE = 'sales_lookup_block_duplicate';
    public const KEY_SALES_ALLOW_UNLINKED_SUBMIT = 'sales_allow_unlinked_submit';
    public const KEY_SALES_ALLOW_MIXED_LINKAGE = 'sales_allow_mixed_linkage';
    public const KEY_SALES_STATUS_TIMING = 'sales_marketplace_status_timing';
    public const KEY_SALES_RECORD_ONLY_DAILY_SALES = 'sales_record_only_daily_sales';

    // ──────────────────────────────────────────────────────
    // RELATIONS
    // ──────────────────────────────────────────────────────

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    // ──────────────────────────────────────────────────────
    // STATIC HELPERS
    // ──────────────────────────────────────────────────────

    /**
     * Baca nilai setting.
     *
     * @param  string      $key
     * @param  mixed|null  $default
     * @return mixed
     */
    public static function get(string $key, mixed $default = null): mixed
    {
        $setting = static::find($key);

        return $setting?->value ?? $default;
    }

    /**
     * Simpan / update nilai setting.
     *
     * @param  string      $key
     * @param  mixed       $value
     * @param  string|null $description
     * @param  int|null    $updatedBy
     */
    public static function set(string $key, mixed $value, ?string $description = null, ?int $updatedBy = null): void
    {
        static::updateOrCreate(
            ['key' => $key],
            array_filter([
                'value'       => $value !== null ? (string) $value : null,
                'description' => $description,
                'updated_by'  => $updatedBy ?? auth()->id(),
            ], fn($v) => $v !== null || $value === null)
        );
    }

    /**
     * Hapus setting.
     */
    public static function remove(string $key): void
    {
        static::where('key', $key)->delete();
    }

    // ──────────────────────────────────────────────────────
    // CUT-OFF DATE HELPERS
    // ──────────────────────────────────────────────────────

    /**
     * Ambil cut-off date sebagai Carbon, atau null jika belum di-set.
     */
    public static function cutoffDate(): ?Carbon
    {
        $value = static::get(self::KEY_CUTOFF_DATE);

        return $value ? Carbon::parse($value)->startOfDay() : null;
    }

    /**
     * Apakah cut-off date sudah di-set?
     */
    public static function hasCutoff(): bool
    {
        return static::cutoffDate() !== null;
    }

    /**
     * Ambil cut-off date sebagai string YYYY-MM-DD, atau null.
     */
    public static function cutoffDateString(): ?string
    {
        return static::cutoffDate()?->toDateString();
    }

    /**
     * Apakah sebuah tanggal SEBELUM cut-off? (= legacy / data lama)
     *
     * @param  string|Carbon $date
     */
    public static function isLegacy(string|Carbon $date): bool
    {
        $cutoff = static::cutoffDate();

        if (! $cutoff) {
            return false; // belum ada cut-off → semua dianggap aktif
        }

        $d = $date instanceof Carbon ? $date : Carbon::parse($date);

        return $d->lt($cutoff);
    }

    /**
     * Ambil tanggal cut-off untuk dipakai sebagai default filter laporan.
     * Kalau belum di-set, kembalikan null (laporan tampilkan semua).
     *
     * Usage di controller:
     *   $from = $request->input('from') ?? SystemSetting::defaultFromDate();
     */
    public static function defaultFromDate(): ?string
    {
        return static::cutoffDateString();
    }
}
