<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class MarketplaceAdsSetting extends Model
{
    use HasFactory;

    protected $fillable = [
        'store_id',
        'target_roas',
        'target_profit_pct',
        'target_roas_mode',
        'admin_fee_mode',
        'admin_fee_pct',
        'notes',
    ];

    protected $casts = [
        'target_roas'       => 'float',
        'target_profit_pct' => 'float',
        'admin_fee_pct'     => 'float',
    ];
}
