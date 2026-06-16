<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PurchaseReceiptQc extends Model
{
    protected $fillable = [
        'purchase_receipt_id',
        'checked_by',
        'checked_at',
        'status',
        'qty_checked',
        'qty_ok',
        'qty_issue',
        'issue_type',
        'notes',
        'photo_path',
    ];

    protected $casts = [
        'checked_at'  => 'datetime',
        'qty_checked' => 'float',
        'qty_ok'      => 'float',
        'qty_issue'   => 'float',
    ];

    // =========================================================
    // RELATIONSHIPS
    // =========================================================

    public function purchaseReceipt(): BelongsTo
    {
        return $this->belongsTo(PurchaseReceipt::class, 'purchase_receipt_id');
    }

    public function checkedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'checked_by');
    }

    // =========================================================
    // STATUS HELPERS
    // =========================================================

    public function isDraft(): bool
    {
        return $this->status === 'draft';
    }

    public function isPassed(): bool
    {
        return $this->status === 'passed';
    }

    public function isIssue(): bool
    {
        return $this->status === 'issue';
    }

    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    /** Apakah QC ini perlu tindak lanjut (issue atau rejected) */
    public function hasIssue(): bool
    {
        return in_array($this->status, ['issue', 'rejected'], true);
    }

    /** Apakah QC masih bisa diedit */
    public function isEditable(): bool
    {
        return in_array($this->status, ['draft', 'passed', 'issue', 'rejected'], true);
    }

    // =========================================================
    // STATIC HELPERS
    // =========================================================

    public static function issueTypeLabel(?string $type): string
    {
        return match ($type) {
            'rusak'                 => 'Rusak',
            'salah_item'            => 'Salah Item',
            'salah_warna'           => 'Salah Warna',
            'kurang_qty'            => 'Kurang Qty',
            'lebih_qty'             => 'Lebih Qty',
            'kualitas_tidak_sesuai' => 'Kualitas Tidak Sesuai',
            'lainnya'               => 'Lainnya',
            default                 => $type ?? '—',
        };
    }

    public static function statusLabel(?string $status): string
    {
        return match ($status) {
            'draft'     => 'Draft',
            'passed'    => 'Lolos QC',
            'issue'     => 'Ada Masalah',
            'rejected'  => 'Ditolak',
            'cancelled' => 'Dibatalkan',
            default     => $status ?? '—',
        };
    }

    public static function issueTypes(): array
    {
        return [
            'rusak'                 => 'Rusak',
            'salah_item'            => 'Salah Item',
            'salah_warna'           => 'Salah Warna',
            'kurang_qty'            => 'Kurang Qty',
            'lebih_qty'             => 'Lebih Qty',
            'kualitas_tidak_sesuai' => 'Kualitas Tidak Sesuai',
            'lainnya'               => 'Lainnya',
        ];
    }
}
