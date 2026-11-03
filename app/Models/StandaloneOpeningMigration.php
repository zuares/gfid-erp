<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class StandaloneOpeningMigration extends Model
{
    public $incrementing = false;

    protected $guarded = ['id'];

    protected $casts = [
        'as_of' => 'date',
        'source_from' => 'date',
        'snapshot' => 'array',
        'posted_at' => 'datetime',
    ];

    public function journal()
    {
        return $this->belongsTo(StandaloneAccountingJournal::class);
    }

    public function poster()
    {
        return $this->belongsTo(User::class, 'posted_by');
    }

    public function corrections()
    {
        return $this->hasMany(StandaloneOpeningMigrationCorrection::class, 'opening_migration_id');
    }
}
