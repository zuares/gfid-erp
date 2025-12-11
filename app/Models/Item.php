<?php

namespace App\Models;

use App\Models\ItemBarcode;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

// sesuaikan dengan nama model barcode kamu

class Item extends Model
{
    use HasFactory;

    // Kalau nama tabel default "items", tidak perlu $table

    protected $fillable = [
        'code',
        'name',
        'unit',
        'type',
        'item_category_id',
        'last_purchase_price',
        'hpp',
        'active',
        'consumption_cutting', // ⭐ NEW

        'consumption_cutting_basis_qty', // ⭐ NEW
        'base_unit_cost', //⭐ NEW

    ];

    protected $casts = [
        'last_purchase_price' => 'decimal:2',
        'hpp' => 'decimal:2',
        'active' => 'boolean',
        'consumption_cutting' => 'decimal:2', // ⭐ NEW
        'consumption_cutting' => 'decimal:2', // ⭐ NEW
        'base_unit_cost' => 'decimal:2', // ⭐ NEW
    ];

    /* ==========================
     *  RELATIONSHIPS
     * ==========================
     */

    /**
     * Kategori item (SJR, LJR, RIB, dll)
     */
    public function category()
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    /**
     * Relasi ke detail PO yang menggunakan item ini.
     */
    public function purchaseOrderLines()
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    /**
     * Harga historis per supplier.
     */
    public function supplierPrices()
    {
        return $this->hasMany(SupplierPrice::class);
    }

    /**
     * LOT kain / stok berbasis LOT untuk item ini (kalau LOT kamu pakai item_id).
     */
    public function lots()
    {
        return $this->hasMany(Lot::class);
    }

    /**
     * Stok per gudang (kalau di InventoryStock ada item_id).
     */
    public function inventoryStocks()
    {
        return $this->hasMany(InventoryStock::class);
    }

    /**
     * Material yang dipakai di production batch (kalau di schema kamu ada).
     */
    public function productionBatchMaterials()
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    /* ==========================
     *  HELPER
     * ==========================
     */

    public function isMaterial(): bool
    {
        return $this->type === 'material';
    }

    public function isFinished(): bool
    {
        return $this->type === 'finished';
    }

    public function isAccessory(): bool
    {
        return $this->type === 'accessory';
    }

    public function finishingLines()
    {
        return $this->hasMany(FinishingJobLine::class, 'item_id');
    }

    public function scopeInStockAtWarehouse($query, int $warehouseId)
    {
        return $query->whereHas('inventoryStocks', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)
                ->where('qty', '>', 0);
        });
    }

    public function activeCostSnapshot()
    {
        return $this->hasOne(\App\Models\ItemCostSnapshot::class, 'item_id')
            ->where('is_active', true)
            ->orderByDesc('snapshot_date');
    }

    public function shipmentLines()
    {
        return $this->hasMany(ShipmentLine::class);
    }

    /**
     * HPP global sementara.
     * Nanti kalau modul HPP sudah jadi, logic di sini bisa diganti ambil dari snapshot dsb.
     */
    public function getEffectiveUnitCostAttribute(): float
    {
        return (float) ($this->base_unit_cost ?? 0);
    }

    public function costSnapshots()
    {
        return $this->hasMany(ItemCostSnapshot::class);
    }

    /**
     * HPP aktif (unit_cost) untuk item ini, global (tanpa filter gudang).
     * Dipakai untuk tampilan cepat di Master Item.
     */
    public function getActiveUnitCostAttribute(): float
    {
        $snapshot = $this->costSnapshots()
            ->active() // scopeActive di ItemCostSnapshot
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        return $snapshot?->unit_cost ?? 0;
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ItemBarcode::class);
    }

}
