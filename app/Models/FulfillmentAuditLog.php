<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Auth;

class FulfillmentAuditLog extends Model
{
    public $timestamps = false;

    protected $fillable = [
        'order_fulfillment_id',
        'order_fulfillment_line_id',
        'user_id',
        'action',
        'meta',
        'created_at',
    ];

    protected $casts = [
        'meta'       => 'array',
        'created_at' => 'datetime',
    ];

    // ------------------------------------------------------------------ //
    //  Relations
    // ------------------------------------------------------------------ //

    public function fulfillment(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillment::class, 'order_fulfillment_id');
    }

    public function line(): BelongsTo
    {
        return $this->belongsTo(OrderFulfillmentLine::class, 'order_fulfillment_line_id');
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(\App\Models\User::class);
    }

    // ------------------------------------------------------------------ //
    //  Static helper
    // ------------------------------------------------------------------ //

    /**
     * Record an audit event.
     *
     * @param  int         $fulfillmentId
     * @param  string      $action         e.g. 'split', 'restore_split', 'confirm', …
     * @param  array       $meta           arbitrary payload
     * @param  int|null    $lineId
     * @param  int|null    $userId         null = use authenticated user
     */
    public static function record(
        int    $fulfillmentId,
        string $action,
        array  $meta    = [],
        ?int   $lineId  = null,
        ?int   $userId  = null,
    ): void {
        try {
            static::create([
                'order_fulfillment_id'      => $fulfillmentId,
                'order_fulfillment_line_id' => $lineId,
                'user_id'                   => $userId ?? Auth::id(),
                'action'                    => $action,
                'meta'                      => $meta ?: null,
                'created_at'                => now(),
            ]);
        } catch (\Throwable) {
            // Audit failure must never break the main flow.
        }
    }

    // ------------------------------------------------------------------ //
    //  Human-readable label map
    // ------------------------------------------------------------------ //

    public static function actionLabel(string $action): string
    {
        return match ($action) {
            'create_draft'      => 'Draft dibuat',
            'scan_order'        => 'Order discan',
            'confirm'           => 'Fulfillment dikonfirmasi',
            'start_picking'     => 'Picking dimulai',
            'complete_picking'  => 'Picking selesai',
            'mark_packed'       => 'Dipacking',
            'unpack'            => 'Unpack',
            'toggle_picked'     => 'Item di-pick / un-pick',
            'flag_problem'      => 'Problem dilaporkan',
            'resolve_problem'   => 'Problem diselesaikan',
            'substitute'        => 'Item diganti',
            'split'             => 'Line di-split',
            'restore_split'     => 'Split di-restore',
            'batch_confirm'     => 'Batch dikonfirmasi',
            default             => $action,
        };
    }
}
