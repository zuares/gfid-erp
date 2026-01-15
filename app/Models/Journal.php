<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class Journal extends Model
{
    protected $fillable = [
        'date',
        'description',
        'source_type',
        'source_id',
        'posted_at',
        'voided_at', // ✅ added
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime', // ✅ added
    ];

    /**
     * Lines (debit/credit) for this journal.
     */
    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class);
    }

    /**
     * If this is an opening_balance journal, the reversal journal (void) that references it.
     * opening_balance_void.source_id = opening_balance.id
     */
    public function openingVoid(): HasOne
    {
        return $this->hasOne(self::class, 'source_id')
            ->where('source_type', 'opening_balance_void');
    }

    /**
     * If this is an opening_balance_void journal, the original opening balance journal it references.
     */
    public function openingOriginal(): BelongsTo
    {
        return $this->belongsTo(self::class, 'source_id');
    }

    /**
     * Helper: is posted?
     */
    public function isPosted(): bool
    {
        return !is_null($this->posted_at);
    }

    /**
     * Helper: is voided?
     */
    public function isVoided(): bool
    {
        return !is_null($this->voided_at);
    }
}
