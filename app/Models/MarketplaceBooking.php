<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceBooking extends Model
{
    protected $fillable = [
        'store_id', 'booking_sn', 'order_sn', 'booking_status', 'shipping_carrier',
        'tracking_number', 'package_number', 'shipping_document_status',
        'create_time', 'update_time', 'items', 'raw_json', 'meta',
    ];

    protected $casts = [
        'items'    => 'array',
        'raw_json' => 'array',
        'meta'     => 'array',
    ];

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function getOrderDataAttribute()
    {
        $sn = $this->order_sn ?: $this->booking_sn;
        if (!$sn) return null;
        return MarketplaceOrder::where('store_id', $this->store_id)
            ->where('channel_order_id', $sn)
            ->first();
    }

    /** Booking dianggap masih perlu diatur pengiriman bila belum ada tracking & status awal. */
    public function needsShipping(): bool
    {
        return blank($this->tracking_number)
            && in_array((string) $this->booking_status, ['PENDING', 'PROCESSED', 'READY_TO_SHIP', ''], true);
    }
}
