<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Item extends Model
{
    use HasFactory;

    protected $fillable = [
        'code',
        'sku',
        'name',
        'unit',
        'stock_unit',
        'purchase_unit',
        'purchase_conversion_factor',
        'type', // material / finished_good
        'item_type_option_id',
        'item_category_id', // sementara masih dipakai (keluarga produk / legacy)
        'product_category_id', // kalau kamu sudah tambah kolom ini (opsional)
        'item_role', // legacy string (raw_material/production_supply/...)
        'item_role_id', // FK ke item_roles
        'production_source', // in_house / outsource / buy
        'can_buy',
        'can_make',
        'default_supply_source',

        'last_purchase_price',
        'hpp',
        'base_unit_cost',

        'active',
        'rts_min_display',
        'rts_max_display',
        'affects_hpp',
        'default_allocation', // hpp / expense
        'purchase_treatment_id',
        'default_expense_account_id',

        'is_stocked',
        'allow_negative',
        'hpp_behavior',

        // optional fields yang kamu sempat pakai di model lama
        'consumption_cutting',
        'consumption_cutting_basis_qty',
    ];

    public const PRODUCTION_IN_HOUSE = 'in_house';
    public const PRODUCTION_OUTSOURCE = 'outsource';
    public const PRODUCTION_BUY = 'buy';
    public const SUPPLY_MAKE = 'make';
    public const SUPPLY_BUY = 'buy';
    public const SUPPLY_OUTSOURCE = 'outsource';

    protected $casts = [
        'last_purchase_price' => 'decimal:2',
        'hpp' => 'decimal:2',
        'base_unit_cost' => 'decimal:2',
        'purchase_conversion_factor' => 'decimal:6',

        'active' => 'boolean',
        'affects_hpp' => 'boolean',
        'is_stocked' => 'boolean',
        'allow_negative' => 'boolean',
        'can_buy' => 'boolean',
        'can_make' => 'boolean',

        'consumption_cutting' => 'decimal:4',
        'consumption_cutting_basis_qty' => 'decimal:4',
    ];

    public function stockUnit(): string
    {
        return trim((string) ($this->stock_unit ?: $this->unit ?: 'pcs'));
    }

    public function purchaseUnit(): string
    {
        return trim((string) ($this->purchase_unit ?: $this->stockUnit()));
    }

    public function purchaseConversionFactor(): float
    {
        $factor = (float) ($this->purchase_conversion_factor ?? 1);
        return $factor > 0 ? $factor : 1.0;
    }

    public function stockQtyFromPurchase(float $qty): float
    {
        return round($qty * $this->purchaseConversionFactor(), 6);
    }

    public static function productionSourceLabels(): array
    {
        return [
            self::PRODUCTION_IN_HOUSE => 'Produksi sendiri',
            self::PRODUCTION_OUTSOURCE => 'Makloon / Outsource',
            self::PRODUCTION_BUY => 'Beli jadi',
        ];
    }

    public function getProductionSourceLabelAttribute(): string
    {
        return self::productionSourceLabels()[$this->production_source] ?? 'Belum ditentukan';
    }

    public function getIsMadeInHouseAttribute(): bool
    {
        return $this->canMake();
    }

    public static function supplySourceLabels(): array
    {
        return [
            self::SUPPLY_MAKE => 'Produksi sendiri',
            self::SUPPLY_BUY => 'Beli jadi',
            self::SUPPLY_OUTSOURCE => 'Makloon / Outsource',
        ];
    }

    public function canBuy(): bool
    {
        if ((bool) $this->can_buy || (bool) $this->can_make || $this->default_supply_source !== null) {
            return (bool) $this->can_buy;
        }

        return $this->production_source === self::PRODUCTION_BUY;
    }

    public function canMake(): bool
    {
        if ((bool) $this->can_buy || (bool) $this->can_make || $this->default_supply_source !== null) {
            return (bool) $this->can_make;
        }

        return $this->production_source === self::PRODUCTION_IN_HOUSE;
    }

    public function isHybrid(): bool
    {
        return $this->canBuy() && $this->canMake();
    }

    public function getSupplyModeLabelAttribute(): string
    {
        if ($this->isHybrid()) {
            return 'Hybrid: produksi / beli jadi';
        }

        if ($this->canMake()) {
            return 'Produksi sendiri';
        }

        if ($this->canBuy()) {
            return 'Beli jadi';
        }

        return 'Belum ditentukan';
    }

    public function getDefaultSupplySourceLabelAttribute(): string
    {
        return self::supplySourceLabels()[$this->effectiveSupplySource()] ?? 'Belum ditentukan';
    }

    /**
     * Sumber default yang dipakai service ketika data lama belum memiliki policy.
     */
    public function effectiveSupplySource(): ?string
    {
        if (array_key_exists($this->default_supply_source, self::supplySourceLabels())) {
            return $this->default_supply_source;
        }

        return match ($this->production_source) {
            self::PRODUCTION_IN_HOUSE => self::SUPPLY_MAKE,
            self::PRODUCTION_OUTSOURCE => self::SUPPLY_OUTSOURCE,
            self::PRODUCTION_BUY => self::SUPPLY_BUY,
            default => $this->canMake() && !$this->canBuy() ? self::SUPPLY_MAKE : null,
        };
    }

    /* ============================================================
     * RELATIONSHIPS
     * ============================================================
     */

    public function category(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'item_category_id');
    }

    public function itemTypeOption(): BelongsTo
    {
        return $this->belongsTo(ItemTypeOption::class);
    }

    public function purchaseTreatment(): BelongsTo
    {
        return $this->belongsTo(ItemPurchaseTreatment::class);
    }

    public function boms(): HasMany
    {
        return $this->hasMany(ItemBom::class);
    }

    // Kalau kamu sudah punya product_category_id
    public function productCategory(): BelongsTo
    {
        return $this->belongsTo(ItemCategory::class, 'product_category_id');
    }

    public function role(): BelongsTo
    {
        return $this->belongsTo(ItemRole::class, 'item_role_id');
    }

    public function lots(): HasMany
    {
        return $this->hasMany(Lot::class);
    }

    public function inventoryStocks(): HasMany
    {
        return $this->hasMany(InventoryStock::class);
    }

    public function purchaseOrderLines(): HasMany
    {
        return $this->hasMany(PurchaseOrderLine::class);
    }

    public function supplierPrices(): HasMany
    {
        return $this->hasMany(SupplierPrice::class);
    }

    public function shipmentLines(): HasMany
    {
        return $this->hasMany(ShipmentLine::class);
    }

    public function finishingLines(): HasMany
    {
        return $this->hasMany(FinishingJobLine::class, 'item_id');
    }

    public function productionBatchMaterials(): HasMany
    {
        return $this->hasMany(ProductionBatchMaterial::class);
    }

    public function barcodes(): HasMany
    {
        return $this->hasMany(ItemBarcode::class);
    }

    public function suppliers(): BelongsToMany
    {
        return $this->belongsToMany(Supplier::class, 'supplier_items')
            ->withPivot(['last_price', 'is_primary', 'minimum_order_qty', 'lead_time_days', 'active'])
            ->withTimestamps();
    }

    public function costSnapshots(): HasMany
    {
        return $this->hasMany(ItemCostSnapshot::class);
    }

    public function defaultExpenseAccount(): BelongsTo
    {
        return $this->belongsTo(Account::class, 'default_expense_account_id');
    }

    public function usesExpenseAllocation(): bool
    {
        return ($this->default_allocation ?? 'hpp') === 'expense';
    }

    public function usesInventoryAllocation(): bool
    {
        return !$this->usesExpenseAllocation();
    }

    public function getAllocationLabelAttribute(): string
    {
        return $this->usesExpenseAllocation()
            ? 'Langsung biaya'
            : 'Masuk persediaan / HPP';
    }

    public function activeCostSnapshot()
    {
        return $this->hasOne(ItemCostSnapshot::class, 'item_id')
            ->where('is_active', true)
            ->orderByDesc('snapshot_date');
    }

    /* ============================================================
     * SCOPES
     * ============================================================
     */

    public function scopeInStockAtWarehouse($query, int $warehouseId)
    {
        return $query->whereHas('inventoryStocks', function ($q) use ($warehouseId) {
            $q->where('warehouse_id', $warehouseId)->where('qty', '>', 0);
        });
    }

    public function scopeCanBeMade($query)
    {
        return $query->where(function ($q) {
            $q->where('can_make', true)
                ->orWhere('production_source', self::PRODUCTION_IN_HOUSE);
        });
    }

    public function scopeCanBeBought($query)
    {
        return $query->where(function ($q) {
            $q->where('can_buy', true)
                ->orWhereIn('production_source', [self::PRODUCTION_BUY, self::PRODUCTION_OUTSOURCE]);
        });
    }

    /* ============================================================
     * ROLE HELPERS (Tahap 0: source of truth)
     * ============================================================
     */

    /**
     * Kode role yang dipakai sistem:
     * - prioritas: FK item_role_id -> item_roles.code
     * - fallback: item_role string legacy
     */
    public function getRoleCodeAttribute(): ?string
    {
        if ($this->relationLoaded('role') && $this->role) {
            return $this->role->code;
        }

        if (!empty($this->item_role_id)) {
            // lazy load ringan (tanpa eager)
            $code = ItemRole::query()->whereKey((int) $this->item_role_id)->value('code');
            if ($code) {
                return (string) $code;
            }
        }

        // fallback legacy mapping
        $legacy = strtolower((string) ($this->item_role ?? ''));
        return match ($legacy) {
            'raw_material' => ItemRole::RM,
            'production_supply' => ItemRole::SUP,
            'shipping_supply' => ItemRole::PKG,
            'finished_good' => ItemRole::FG,
            default => null,
        };
    }

    public function isRm(): bool
    {
        return $this->role_code === ItemRole::RM;
    }

    public function isSupply(): bool
    {
        return $this->role_code === ItemRole::SUP;
    }

    public function isPackaging(): bool
    {
        return $this->role_code === ItemRole::PKG;
    }

    public function isFinishedGood(): bool
    {
        // FG bisa dari role_code atau type finished_good (fallback legacy)
        return $this->role_code === ItemRole::FG || $this->type === 'finished_good';
    }

    /* ============================================================
     * TYPE HELPERS (legacy)
     * ============================================================
     */

    public function isMaterial(): bool
    {
        return $this->type === 'material';
    }

    public function isFinished(): bool
    {
        return $this->type === 'finished_good';
    }

    /* ============================================================
     * COST HELPERS
     * ============================================================
     */

    /**
     * HPP global “efektif”.
     * Urutan:
     * 1) Snapshot aktif global
     * 2) base_unit_cost
     * 3) hpp (legacy)
     */
    public function getEffectiveUnitCostAttribute(): float
    {
        $snapshot = $this->costSnapshots()
            ->where('is_active', true)
            ->orderByDesc('snapshot_date')
            ->orderByDesc('id')
            ->first();

        if ($snapshot && (float) $snapshot->unit_cost > 0) {
            return (float) $snapshot->unit_cost;
        }

        if ((float) $this->base_unit_cost > 0) {
            return (float) $this->base_unit_cost;
        }

        if ((float) $this->hpp > 0) {
            return (float) $this->hpp;
        }

        return 0.0;
    }

    /**
     * HPP aktif (unit_cost) untuk item ini (global).
     */
    public function getActiveUnitCostAttribute(): float
    {
        return $this->getActiveUnitCostForWarehouse(null);
    }

    public function getActiveUnitCostForWarehouse(?int $warehouseId = null): float
    {
        $snapshot = ItemCostSnapshot::getActiveForItem($this->id, $warehouseId);

        if ($snapshot && (float) $snapshot->unit_cost > 0) {
            return (float) $snapshot->unit_cost;
        }

        if ((float) $this->base_unit_cost > 0) {
            return (float) $this->base_unit_cost;
        }

        if ((float) $this->hpp > 0) {
            return (float) $this->hpp;
        }

        return 0.0;
    }
}
