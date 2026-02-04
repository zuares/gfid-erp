<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class ProductionActivity extends Model
{

    protected $fillable = [
        'production_order_id',
        'process',
        'date',
        'qty',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function productionOrder()
    {return $this->belongsTo(ProductionOrder::class);}

}
