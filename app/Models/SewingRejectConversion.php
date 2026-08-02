<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SewingRejectConversion extends Model
{
    protected $fillable = [
        'code',
        'date',
        'status',
        'source_reject_return_line_id',
        'source_finishing_job_line_id',
        'item_id',
        'reject_item_id',
        'cutting_job_bundle_id',
        'qty',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
        'qty' => 'decimal:3',
    ];

    public function item(): BelongsTo
    {
        return $this->belongsTo(Item::class);
    }

    public function rejectItem(): BelongsTo
    {
        return $this->belongsTo(Item::class, 'reject_item_id');
    }

    public function bundle(): BelongsTo
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function sourceRejectReturnLine(): BelongsTo
    {
        return $this->belongsTo(SewingReturnLine::class, 'source_reject_return_line_id');
    }

    public function sourceFinishingJobLine(): BelongsTo
    {
        return $this->belongsTo(FinishingJobLine::class, 'source_finishing_job_line_id');
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public static function generateCode(string $date): string
    {
        $ymd = \Carbon\Carbon::parse($date)->format('Ymd');
        $count = self::whereDate('date', \Carbon\Carbon::parse($date)->toDateString())->count();

        return 'SRJC-' . $ymd . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
