<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Cache\RateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleMachineIpRequests
{
    public function __construct(private RateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        // Use the socket peer rather than forwarding headers: forwarded IPs
        // are only trustworthy when every network proxy is configured correctly.
        $peerIp = (string) $request->server->get('REMOTE_ADDR', 'unknown');
        $key = 'machine-inventory-ip:'.hash('sha256', $peerIp);

        if ($this->limiter->tooManyAttempts($key, 60)) {
            return response()->json([
                'message' => 'Too many requests.',
            ], 429, [
                'Retry-After' => (string) $this->limiter->availableIn($key),
            ]);
        }

        $this->limiter->hit($key, 60);

        return $next($request);
    }
}
