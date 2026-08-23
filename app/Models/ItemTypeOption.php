<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemTypeOption extends Model
{
    protected $fillable = [
        'code',
        'name',
        'base_type',
        'active',
        'is_system',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function items(): HasMany
    {
        return $this->hasMany(Item::class);
    }
}
