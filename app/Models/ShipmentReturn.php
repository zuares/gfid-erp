<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class ShipmentReturn extends Model
{
    use HasFactory;

    protected $table = 'shipment_returns';

    protected $fillable = [
        'code',
        'store_id',
        'shipment_id',
        'date',
        'status',
        'reason',
        'notes',
        'total_qty',
        'submitted_at',
        'submitted_by',
        'posted_at',
        'posted_by',
        'created_by',
    ];

    protected $casts = [
        'date' => 'date',
        'submitted_at' => 'datetime',
        'posted_at' => 'datetime',
    ];

    /* ==========================
    RELATIONS
    ========================== */

    public function store()
    {
        return $this->belongsTo(Store::class);
    }

    public function shipment()
    {
        return $this->belongsTo(Shipment::class);
    }

    public function lines()
    {
        return $this->hasMany(ShipmentReturnLine::class);
    }

    public function creator()
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function submittedBy()
    {
        return $this->belongsTo(User::class, 'submitted_by');
    }

    public function postedBy()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    /* ==========================
    CODE GENERATOR
    ========================== */

    public static function generateCode(string $prefix = 'RTP'): string
    {
        $date = now()->format('Ymd');

        $last = static::where('code', 'like', "{$prefix}-{$date}-%")
            ->orderByDesc('id')
            ->first();

        $seq = 1;

        if ($last) {
            $lastSeq = (int) substr($last->code, -3);
            $seq = $lastSeq + 1;
        }

        return sprintf('%s-%s-%03d', $prefix, $date, $seq);
    }
}
