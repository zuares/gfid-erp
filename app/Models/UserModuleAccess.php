<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class UserModuleAccess extends Model
{
    public const MODULES = [
        'dashboard' => 'Dashboard',
        'master' => 'Master Data',
        'inventory' => 'Inventory',
        'production' => 'Production',
        'sales' => 'Sales',
        'purchasing' => 'Purchasing',
        'marketplace' => 'Marketplace',
        'imports' => 'Imports',
        'accounting' => 'Accounting',
        'payroll' => 'Payroll',
        'costing' => 'Costing & HPP',
    ];

    protected $fillable = [
        'user_id',
        'module',
        'can_access',
        'updated_by',
    ];

    protected $casts = [
        'can_access' => 'boolean',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
