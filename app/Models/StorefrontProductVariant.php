<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Facades\Storage;

class StorefrontProductVariant extends Model
{
    protected $fillable = [
        'product_id', 'color_name', 'hex_color', 'image_url',
        'price_override', 'is_default', 'sort_order', 'is_active',
    ];

    protected $casts = [
        'price_override' => 'integer',
        'is_default'     => 'boolean',
        'is_active'      => 'boolean',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(StorefrontProduct::class, 'product_id');
    }

    /** Resolve image_url ke src yang bisa dipakai di <img> */
    public function getImageSrc(): string
    {
        if (!$this->image_url) return '';
        return str_starts_with($this->image_url, 'http')
            ? $this->image_url
            : Storage::url($this->image_url);
    }
}
