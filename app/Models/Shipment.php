<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Shipment extends Model
{
    use HasFactory;

    public const TYPE_MARKETPLACE = 'marketplace';
    public const TYPE_MANUAL = 'manual';

    protected $fillable = [
        'code',
        'shipment_type',
        'scan_mode',
        'store_id',
        'warehouse_id',
        'sales_invoice_id',

        'date',
        'status',
        'total_qty',
        'notes',

        // optional: if exists in DB
        'awb',

        // audit
        'created_by',
        'submitted_at',
        'submitted_by',
        'posted_at',
        'posted_by',

        // cancel fields (if exists)
        'cancelled_at',
        'cancelled_by',
        'cancel_reason',

        // daily sales hooks (if exists)
        'daily_sales_applied_at',
        'daily_sales_reversed_at',
    ];

    protected $casts = [
        'date' => 'date',
        'submitted_at' => 'datetime',
        'posted_at' => 'datetime',

        // optional
        'cancelled_at' => 'datetime',
        'daily_sales_applied_at' => 'datetime',
        'daily_sales_reversed_at' => 'datetime',
    ];

    /* =====================
     * RELATIONS
     * ===================== */

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    // kamu pakai ShipmentLine sebagai detail scan
    public function lines()
    {
        return $this->hasMany(ShipmentLine::class);
    }

    public function orderScans()
    {
        return $this->hasMany(ShipmentOrderScan::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submitter()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function canceller()
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    public function invoice()
    {
        return $this->belongsTo(SalesInvoice::class, 'sales_invoice_id');
    }

    public function marketplaceReconciliations()
    {
        return $this->hasMany(\App\Models\MpReconciliation::class, 'shipment_id');
    }

    /* =====================
     * HELPERS
     * ===================== */

    public static function generateCode(string $prefix = 'SHP'): string
    {
        $today = now()->format('Ymd');

        $last = static::whereDate('created_at', now()->toDateString())
            ->where('code', 'like', $prefix . '-' . $today . '-%')
            ->orderByDesc('id')
            ->first();

        $seq = 1;
        if ($last && preg_match('/^' . preg_quote($prefix, '/') . '-' . $today . '-(\d+)$/', $last->code, $m)) {
            $seq = (int) $m[1] + 1;
        }

        return $prefix . '-' . $today . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
    }

    public function isCancelled(): bool
    {
        return !empty($this->cancelled_at);
    }

    public function isPosted(): bool
    {
        return !empty($this->posted_at);
    }

    public function mpReconciliations(): HasMany
    {
        return $this->hasMany(MpReconciliation::class, 'shipment_id');
    }

}
