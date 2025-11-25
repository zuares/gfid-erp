<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class QcResult extends Model
{
    protected $fillable = [
        'stage',
        'cutting_job_id',
        'cutting_job_bundle_id',
        'sewing_job_id',
        'qc_date',
        'qty_ok',
        'qty_reject',
        'operator_id',
        'status',
        'notes',
    ];

    protected $casts = [
        'qc_date' => 'date',
        'qty_ok' => 'float',
        'qty_reject' => 'float',
    ];

    public function cuttingJob()
    {
        return $this->belongsTo(CuttingJob::class);
    }

    public function cuttingBundle()
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

    public function sewingJob()
    {
        return $this->belongsTo(SewingJob::class);
    }

    public function operator()
    {
        return $this->belongsTo(Employee::class, 'operator_id');
    }

    // 🔹 Relasi ke CuttingJob (header)

    // 🔹 Relasi ke CuttingJobBundle (detail/bundle)
    public function cuttingJobBundle()
    {
        return $this->belongsTo(CuttingJobBundle::class, 'cutting_job_bundle_id');
    }

}
