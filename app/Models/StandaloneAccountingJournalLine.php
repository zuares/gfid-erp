<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneAccountingJournalLine extends Model
{
    protected $table = 'standalone_accounting_journal_lines';

    protected $fillable = [
        'journal_id',
        'account_id',
        'debit',
        'credit',
    ];

    protected $casts = [
        'debit' => 'decimal:2',
        'credit' => 'decimal:2',
    ];

    public function journal()
    {
        return $this->belongsTo(StandaloneAccountingJournal::class, 'journal_id');
    }

    public function account()
    {
        return $this->belongsTo(StandaloneAccountingAccount::class, 'account_id');
    }
}
