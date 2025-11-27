<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class PackingJob extends Model
{
    protected $fillable = [
        'code',
        'date',
        'status',
        'posted_at',
        'unposted_at',
        'channel',
        'reference',
        'notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'unposted_at' => 'datetime',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(PackingJobLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }
}
