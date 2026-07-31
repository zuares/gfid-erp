<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class OpenAiConnection extends Model
{
    use HasFactory;

    protected $table = 'openai_connections';

    protected $fillable = [
        'user_id',
        'label',
        'api_key',
        'organization_id',
        'project_id',
        'model',
        'is_active',
        'last_verified_at',
        'last_error',
    ];

    protected $casts = [
        'api_key' => 'encrypted',
        'is_active' => 'boolean',
        'last_verified_at' => 'datetime',
    ];

    protected $hidden = [
        'api_key',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function maskedKey(): string
    {
        $apiKey = (string) $this->api_key;
        if ($apiKey === '') {
            return '••••';
        }

        $tail = substr($apiKey, -4);

        return '••••' . ($tail !== '' ? $tail : '');
    }
}
