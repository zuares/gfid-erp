<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class MarketplaceBooking extends Model
{
    protected $fillable = [
        'store_id', 'booking_sn', 'order_sn', 'booking_status', 'shipping_carrier',
        'tracking_number', 'package_number', 'shipping_document_status',
        'create_time', 'update_time', 'items', 'raw_json', 'meta',
        'print_count', 'printed_at', // statistik cetak resi (migration 2026_07_27_060000)
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

    /**
     * Booking masih perlu diatur bila belum ada bukti dokumen pengiriman.
     * PROCESSED tanpa tracking/package/document tetap dianggap belum diatur
     * agar tidak langsung masuk tab Sedang Dikemas.
     */
    public function needsShipping(): bool
    {
        $hasShippingArtifact = filled($this->tracking_number)
            || filled($this->package_number)
            || filled($this->shipping_document_status);

        return ! $hasShippingArtifact
            && in_array((string) $this->booking_status, ['PENDING', 'READY_TO_SHIP', 'PROCESSED', ''], true);
    }
}
