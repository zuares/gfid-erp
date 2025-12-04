<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Shipment extends Model
{
    use HasFactory;

    protected $fillable = [
        'shipment_no',
        'date',
        'warehouse_id',
        'customer_id',
        'store_id',
        'status',
        'total_items',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    /*
    |--------------------------------------------------------------------------
    | Relationships
    |--------------------------------------------------------------------------
     */

    public function lines()
    {
        return $this->hasMany(ShipmentLine::class);
    }

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function customer()
    {
        return $this->belongsTo(Customer::class);
    }

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /*
    |--------------------------------------------------------------------------
    | Scopes
    |--------------------------------------------------------------------------
     */

    public function scopeFilter(Builder $query, array $filters): Builder
    {
        return $query
            ->when($filters['warehouse_id'] ?? null, function (Builder $q, $warehouseId) {
                $q->where('warehouse_id', $warehouseId);
            })
            ->when($filters['customer_id'] ?? null, function (Builder $q, $customerId) {
                $q->where('customer_id', $customerId);
            })
            ->when($filters['store_id'] ?? null, function (Builder $q, $storeId) {
                $q->where('store_id', $storeId);
            })
            ->when($filters['date_from'] ?? null, function (Builder $q, $dateFrom) {
                $q->whereDate('date', '>=', $dateFrom);
            })
            ->when($filters['date_to'] ?? null, function (Builder $q, $dateTo) {
                $q->whereDate('date', '<=', $dateTo);
            })
            ->when($filters['q'] ?? null, function (Builder $q, $search) {
                $q->where('shipment_no', 'like', '%' . $search . '%');
            });
    }

    /*
    |--------------------------------------------------------------------------
    | Helpers
    |--------------------------------------------------------------------------
     */

    public function getTotalQtyAttribute(): int
    {
        // Kalau mau hitung dinamis
        return $this->lines->sum('qty');
    }
}
