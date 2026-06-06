<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OwnerWorkLog extends Model
{
    protected $fillable = [
        'work_date',
        'title',
        'category',
        'status',
        'done_at',
        'priority',
        'page_url',
        'description',
        'technical_notes',
        'testing_notes',
        'rollback_notes',
        'created_by',
        'updated_by',
    ];

    protected $casts = [
        'work_date' => 'date',
        'done_at' => 'datetime',
        'completed_at' => 'datetime',
        'check_route' => 'boolean',
        'check_view' => 'boolean',
        'check_form' => 'boolean',
        'check_mobile' => 'boolean',
        'check_no_bak' => 'boolean',
        'check_optimize_clear' => 'boolean',
        'check_git_status' => 'boolean',
    ];

    public const CATEGORIES = [
        'Accounting',
        'Inventory',
        'Marketplace',
        'Order',
        'UI/UX',
        'Bug Fix',
        'Other',
    ];

    public const STATUSES = [
        'progress' => 'Progress',
        'done' => 'Done',
    ];

    public const PRIORITIES = [
        'low' => 'Low',
        'medium' => 'Medium',
        'high' => 'High',
    ];

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function updater(): BelongsTo
    {
        return $this->belongsTo(User::class, 'updated_by');
    }

    public function getStatusLabelAttribute(): string
    {
        return self::STATUSES[$this->status] ?? ucfirst((string) $this->status);
    }

    public function getPriorityLabelAttribute(): string
    {
        return self::PRIORITIES[$this->priority] ?? ucfirst((string) $this->priority);
    }

    public function getIsDoneAttribute(): bool
    {
        return $this->status === 'done';
    }
}
