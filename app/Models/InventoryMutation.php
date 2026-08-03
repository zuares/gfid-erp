<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMutation extends Model
{
    protected $table = 'inventory_mutations';
    protected $fillable = [
        'date',
        'warehouse_id',
        'item_id',
        'qty_change',
        'direction',
        'source_type',
        'source_id',
        'cutting_job_bundle_id',
        'notes',
        'lot_id', // ⭐ WAJIB
        'unit_cost', // ⭐ WAJIB
        'total_cost', // ⭐ WAJIB
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'qty_change' => 'decimal:3',
    ];

    public function warehouse()
    {
        return $this->belongsTo(Warehouse::class);
    }

    public function item()
    {
        return $this->belongsTo(Item::class);
    }

    /**
     * URL ke dokumen sumber (GRN, Transfer, dll) jika diketahui.
     */
    public function getSourceUrlAttribute(): ?string
    {
        if (!$this->source_type || !$this->source_id) {
            return null;
        }

        try {
            return match ($this->source_type) {
                // GRN / Goods Receipt
                'purchase_receipt',
                'purchase_receipt_reverse' =>
                route('purchasing.purchase_receipts.show', $this->source_id),

                // Transfer stok antar gudang
                'inventory_transfer',
                'transfer_out',
                'transfer_in' =>
                route('inventory.transfers.show', $this->source_id),

            // bisa ditambah mapping lain di sini
                default => null,
            };
        } catch (\Throwable $e) {
            // kalau route tidak ada / error, jangan bikin fatal
            return null;
        }
    }

    /**
     * Label singkat untuk ditampilkan di kartu stok.
     */
    public function getSourceLabelAttribute(): string
    {
        if (array_key_exists('source_label', $this->attributes)) {
            $label = trim((string) ($this->attributes['source_label'] ?? ''));
            if ($label !== '') {
                return $label;
            }
        }

        if (!$this->source_type) {
            return '-';
        }

        $label = str_contains($this->source_type, '\\')
            ? class_basename($this->source_type)
            : $this->source_type;
        $label = preg_replace('/([a-z])([A-Z])/', '$1 $2', $label) ?? $label;
        $label = str_replace(['_', '-'], ' ', $label);
        $label = trim(preg_replace('/\s+/', ' ', $label) ?? $label);

        if ($this->source_id) {
            $label .= ' #' . $this->source_id;
        }

        return \Illuminate\Support\Str::headline($label);
    }
    public function lot()
    {
        return $this->belongsTo(\App\Models\Lot::class, 'lot_id');
    }

    public function createdBy()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

}
