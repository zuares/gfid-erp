<?php
namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class PurchaseReturnLinePhoto extends Model
{
    protected $fillable = [
        'purchase_return_line_id', 'path', 'original_name',
    ];

    public function line()
    {
        return $this->belongsTo(PurchaseReturnLine::class, 'purchase_return_line_id');
    }

    /** URL publik foto (via symlink storage). */
    public function getUrlAttribute(): string
    {
        return asset('storage/' . ltrim((string) $this->path, '/'));
    }
}
