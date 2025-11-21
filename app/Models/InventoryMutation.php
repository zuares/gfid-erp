<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class InventoryMutation extends Model
{
    protected $fillable = [
        'date',
        'warehouse_id',
        'item_id',
        'qty_change',
        'direction',
        'source_type',
        'source_id',
        'notes',
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
        if (!$this->source_type) {
            return '-';
        }

        // contoh: purchase_receipt -> PURCHASE RECEIPT
        $label = strtoupper(str_replace('_', ' ', $this->source_type));

        if ($this->source_id) {
            $label .= ' #' . $this->source_id;
        }

        return $label;
    }
}
