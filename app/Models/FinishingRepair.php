<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinishingRepair extends Model
{
    protected $fillable = [
        'code',
        'date',
        'status',
        'created_by_user_id',
        'notes',
    ];

    protected $casts = [
        'date' => 'date',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(FinishingRepairLine::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_user_id');
    }

    public static function generateCode(string $date): string
    {
        $ymd = \Carbon\Carbon::parse($date)->format('Ymd');
        $count = self::whereDate('date', \Carbon\Carbon::parse($date)->toDateString())->count();

        return 'FRP-' . $ymd . '-' . str_pad((string) ($count + 1), 3, '0', STR_PAD_LEFT);
    }
}
