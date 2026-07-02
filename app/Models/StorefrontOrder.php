<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontOrder extends Model
{
    protected $fillable = [
        'order_number',
        'visitor_token',
        'customer_name',
        'customer_phone',
        'province',
        'city',
        'district',
        'village',
        'address_detail',
        'postal_code',
        'address_note',
        'items',
        'subtotal',
        'shipping_cost',
        'unique_code',
        'total_amount',
        'shipping_courier',
        'shipping_service',
        'payment_method',
        'payment_proof_url',
        'status',
        'wa_sent_at',
    ];

    protected $casts = [
        'items'      => 'array',
        'wa_sent_at' => 'datetime',
    ];

    public function getFormattedTotalAttribute(): string
    {
        return 'Rp' . number_format($this->total_amount, 0, ',', '.');
    }
}
