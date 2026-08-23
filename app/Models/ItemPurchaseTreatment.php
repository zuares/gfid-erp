<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ItemPurchaseTreatment extends Model
{
    protected $fillable = [
        'code',
        'name',
        'allocation',
        'default_expense_account_id',
        'active',
        'is_system',
    ];

    protected $casts = [
        'active' => 'boolean',
        'is_system' => 'boolean',
    ];

    public function expenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_expense_account_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(Item::class, 'purchase_treatment_id');
    }
}
