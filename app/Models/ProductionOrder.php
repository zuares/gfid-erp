<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionOrder extends Model
{
    protected $fillable = ['code', 'order_date', 'status', 'notes', 'created_by'];

    public function lines(): HasMany
    {return $this->hasMany(ProductionOrderLine::class);}

    public function issues(): HasMany
    {return $this->hasMany(ProductionIssue::class);}
    public function receipts(): HasMany
    {return $this->hasMany(ProductionReceipt::class);}
    public function activities(): HasMany
    {return $this->hasMany(ProductionActivity::class);}
}
