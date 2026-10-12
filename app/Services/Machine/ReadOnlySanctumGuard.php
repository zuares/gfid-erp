<?php

namespace App\Services\Machine;

use Laravel\Sanctum\Guard;

class ReadOnlySanctumGuard extends Guard
{
    /**
     * Sanctum performs the token lookup, expiry check, and tokenable binding.
     * Skip only its last_used_at write so the MCP read path stays write-free.
     */
    protected function updateLastUsedAt($accessToken): void
    {
        // MachineRequestAuditor records each MCP invocation without DB writes.
    }
}
