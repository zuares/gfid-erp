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
