<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneOpeningMigrationCorrection extends Model
{
    protected $guarded = ['id'];

    public function journal()
    {
        return $this->belongsTo(StandaloneAccountingJournal::class);
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }
}
