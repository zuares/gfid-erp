<?php

namespace App\Services\Machine;

use App\Models\ServiceIdentity;
use Illuminate\Http\Request;

class LocalServiceTokenAuthenticator
{
    /**
     * Authenticate the process-provided credential with Sanctum's own token
     * lookup, expiry, revocation, and tokenable handling.
     */
    public function authenticate(): ?ServiceIdentity
    {
        $plainTextToken = getenv('HERMES_MCP_SERVICE_TOKEN');

        if (! is_string($plainTextToken) || $plainTextToken === '') {
            return null;
        }

        $request = Request::create('/', 'POST', [], [], [], [
            'HTTP_AUTHORIZATION' => 'Bearer '.$plainTextToken,
        ]);

        $identity = (new ReadOnlySanctumGuard(
            app('auth'),
            config('sanctum.expiration'),
        ))($request);

        return $identity instanceof ServiceIdentity ? $identity : null;
    }
}
