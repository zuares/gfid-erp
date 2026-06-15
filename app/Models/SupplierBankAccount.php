<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SupplierBankAccount extends Model
{
    protected $fillable = [
        'supplier_id',
        'bank_name',
        'account_number',
        'account_holder',
        'notes',
    ];

    public function supplier(): BelongsTo
    {
        return $this->belongsTo(Supplier::class);
    }

    public static function bankOptions(): array
    {
        return [
            'BCA'     => 'BCA',
            'BRI'     => 'BRI',
            'BNI'     => 'BNI',
            'Mandiri' => 'Mandiri',
            'BSI'     => 'BSI',
            'CIMB'    => 'CIMB Niaga',
            'Permata' => 'Permata',
            'Danamon' => 'Danamon',
            'BTN'     => 'BTN',
            'Lainnya' => 'Lainnya',
        ];
    }
}
