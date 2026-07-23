<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

/**
 * Grup iklan manual (lintas toko/campaign).
 */
class MarketplaceAdGroup extends Model
{
    protected $table = 'marketplace_ad_groups';

    protected $fillable = [
        'name',
        'slug',
        'color',
        'notes',
        'created_by',
    ];

    protected static function booted(): void
    {
        static::saving(function (self $group) {
            if (blank($group->slug) && filled($group->name)) {
                $group->slug = Str::slug($group->name) . '-' . Str::lower(Str::random(4));
            }
        });
    }

    public function campaigns(): HasMany
    {
        return $this->hasMany(MarketplaceAdCampaign::class, 'ad_group_id');
    }
}
