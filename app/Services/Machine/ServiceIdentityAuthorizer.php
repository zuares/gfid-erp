<?php

namespace App\Services\Machine;

use App\Models\ServiceIdentity;
use Laravel\Sanctum\PersonalAccessToken;

class ServiceIdentityAuthorizer
{
    public function allows(mixed $identity, string $ability): bool
    {
        if (! $identity instanceof ServiceIdentity || ! $identity->is_active) {
            return false;
        }

        $token = $identity->currentAccessToken();

        if (! $token instanceof PersonalAccessToken) {
            return false;
        }

        $allowedAbilities = $identity->allowedAbilities();
        $tokenAbilities = (array) ($token->abilities ?? []);

        return in_array($ability, $allowedAbilities, true)
            && in_array($ability, $tokenAbilities, true)
            && ! in_array('*', $tokenAbilities, true)
            && ! in_array('mcp:*', $tokenAbilities, true)
            && count(array_diff($tokenAbilities, $allowedAbilities)) === 0;
    }
}
