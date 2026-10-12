<?php

namespace App\Services\Machine;

use Illuminate\Cache\RateLimiter;
use Illuminate\Contracts\Auth\Authenticatable;

class MachineInventoryRateLimiter
{
    public function __construct(private RateLimiter $limiter) {}

    /**
     * Consume the shared machine inventory allowance and return retry seconds
     * when the token or identity has reached its limit.
     */
    public function consume(Authenticatable $identity, mixed $token): ?int
    {
        $identityKey = 'machine-inventory-identity:'.$identity::class.':'.$identity->getAuthIdentifier();
        $tokenId = is_object($token) && method_exists($token, 'getKey') ? $token->getKey() : 'unknown';
        $tokenKey = $identityKey.':token:'.$tokenId;

        if ($this->limiter->tooManyAttempts($tokenKey, 30)) {
            return $this->limiter->availableIn($tokenKey);
        }

        if ($this->limiter->tooManyAttempts($identityKey, 300)) {
            return $this->limiter->availableIn($identityKey);
        }

        $this->limiter->hit($tokenKey, 60);
        $this->limiter->hit($identityKey, 3600);

        return null;
    }
}
