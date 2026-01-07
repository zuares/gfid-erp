<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PrdDispatchCorrection extends Model
{
    protected $fillable = [
        'stock_request_id',
        'date',
        'notes',
        'created_by_user_id',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function stockRequest()
    {
        return $this->belongsTo(StockRequest::class);
    }

    public function lines()
    {
        return $this->hasMany(PrdDispatchCorrectionLine::class);
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }
}
