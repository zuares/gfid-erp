<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class SewingPickup extends Model
{
    protected $fillable = [
        'code',
        'date',
        'warehouse_id',
        'operator_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'date' => 'date', // atau 'immutable_date' kalau mau
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    public function lines()
    {
        return $this->hasMany(SewingPickupLine::class);
    }

}
