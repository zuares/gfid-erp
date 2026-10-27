<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneAccountingAccount extends Model
{
    protected $table = 'standalone_accounting_accounts';

    protected $fillable = [
        'code',
        'name',
        'description',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function lines()
    {
        return $this->hasMany(StandaloneAccountingJournalLine::class, 'account_id');
    }

}
