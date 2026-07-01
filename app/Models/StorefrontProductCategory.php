<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StorefrontProductCategory extends Model
{
    protected $fillable = [
        'slug',
        'name',
        'description',
        'image_url',
        'sort_order',
        'is_active',
    ];

    protected $casts = [
        'is_active'  => 'boolean',
        'sort_order' => 'integer',
    ];

    public function products()
    {
        return $this->hasMany(StorefrontProduct::class, 'category_id');
    }
}
