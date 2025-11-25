<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class CuttingJobBundle extends Model
{
    protected $fillable = [
        'cutting_job_id',
        'bundle_code',
        'bundle_no',
        'lot_id',
        'finished_item_id',
        'qty_pcs',
        'operator_id',
        'status',
        'notes',
        'operator_id',
        'qty_used_fabric',
    ];

    protected $casts = [
        'qty_pcs' => 'decimal:2',
    ];

    public function job()
    {
        return $this->belongsTo(CuttingJob::class, 'cutting_job_id');
    }

    public function lot()
    {
        return $this->belongsTo(Lot::class);
    }

    public function finishedItem()
    {
        return $this->belongsTo(Item::class, 'finished_item_id');
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    // app/Models/CuttingJob.php
// app/Models/CuttingJobBundle.php

    public function qcResults()
    {
        return $this->hasMany(QcResult::class, 'cutting_job_bundle_id');
    }

}
