<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneAccountingJournal extends Model
{
    protected $table = 'standalone_accounting_journals';

    protected $fillable = [
        'date',
        'reference',
        'description',
        'posted_at',
        'voided_at',
    ];

    protected $casts = [
        'date' => 'date',
        'posted_at' => 'datetime',
        'voided_at' => 'datetime',
    ];

    public function lines()
    {
        return $this->hasMany(StandaloneAccountingJournalLine::class, 'journal_id');
    }
}
