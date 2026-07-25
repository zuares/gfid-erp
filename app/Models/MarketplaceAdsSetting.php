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
        'notes',
    ];
}
