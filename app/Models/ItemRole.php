<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemRole extends Model
{
    protected $fillable = [
        'code',
        'name',
        'active',
        'is_stocked_default',
        'is_wip_consumable',
        'is_lot_tracked',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_stocked_default' => 'boolean',
        'is_wip_consumable' => 'boolean',
        'is_lot_tracked' => 'boolean',
    ];

    public const RM = 'RM';
    public const SUP = 'SUP';
    public const PKG = 'PKG';
    public const FG = 'FG';

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'item_role_id');
    }

    public static function idByCode(string $code): ?int
    {
        static $cache = [];
        if (!array_key_exists($code, $cache)) {
            $cache[$code] = (int) static::where('code', $code)->value('id');
        }
        return $cache[$code] ?: null;
    }

}
