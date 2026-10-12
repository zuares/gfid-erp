<?php

namespace App\Models;

use Illuminate\Foundation\Auth\User as Authenticatable;
use Laravel\Sanctum\HasApiTokens;

class ServiceIdentity extends Authenticatable
{
    use HasApiTokens;

    protected $fillable = [
        'slug',
        'name',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    /**
     * Abilities allowed for this identity in the current rollout phase.
     * Keep this explicit; do not grant Sanctum's wildcard ability.
     *
     * @return array<int, string>
     */
    public function allowedAbilities(): array
    {
        return match ($this->slug) {
            'hermes-agent' => ['mcp:inventory:read'],
            default => [],
        };
    }
}
