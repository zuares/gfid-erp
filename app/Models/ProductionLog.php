<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

/**
 * Audit trail append-only event produksi non-ledger.
 * Lihat migration create_production_logs_table.
 */
class ProductionLog extends Model
{
    protected $fillable = [
        'event',
        'actor_id',
        'source_type',
        'source_id',
        'reference',
        'summary',
        'meta',
    ];

    protected $casts = [
        'meta' => 'array',
    ];

    public function actor(): BelongsTo
    {
        return $this->belongsTo(User::class, 'actor_id');
    }

    /**
     * Catat satu event. Aman dipanggil kapan pun: kalau tabel belum ada
     * (migration belum jalan) atau gagal, hanya di-log, tidak melempar —
     * supaya pencatatan audit tidak pernah menggagalkan operasi utama.
     */
    public static function record(
        string $event,
        ?string $summary = null,
        array $meta = [],
        ?string $sourceType = null,
        ?int $sourceId = null,
        ?string $reference = null,
    ): void {
        try {
            if (! Schema::hasTable('production_logs')) {
                return;
            }
            static::create([
                'event' => $event,
                'actor_id' => auth()->id(),
                'source_type' => $sourceType,
                'source_id' => $sourceId,
                'reference' => $reference,
                'summary' => $summary,
                'meta' => $meta ?: null,
            ]);
        } catch (\Throwable $e) {
            Log::error('production_log record gagal: ' . $e->getMessage(), ['event' => $event]);
        }
    }
}
