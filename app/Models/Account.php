<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Account extends Model
{
    protected $fillable = [
        'code',
        'name',
        'type',
        'is_cash',
        'is_active',
    ];

    protected $casts = [
        'is_cash' => 'boolean',
        'is_active' => 'boolean',
    ];

    public function journalLines(): HasMany
    {
        return $this->hasMany(\App\Models\JournalLine::class, 'account_id');
    }

    public function marketplaceFinancialComponents(): HasMany
    {
        return $this->hasMany(MarketplaceFinancialComponent::class, 'account_id');
    }

}
