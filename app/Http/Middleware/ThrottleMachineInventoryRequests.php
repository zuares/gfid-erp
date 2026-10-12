<?php

namespace App\Http\Middleware;

use Closure;
use App\Services\Machine\MachineInventoryRateLimiter;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class ThrottleMachineInventoryRequests
{
    public function __construct(private MachineInventoryRateLimiter $limiter) {}

    public function handle(Request $request, Closure $next): Response
    {
        $identity = $request->user();
        $token = $identity?->currentAccessToken();

        // auth:sanctum runs before this middleware; key limits by both the
        // credential and its service identity, never by a human role.
        abort_unless($identity && $token, 401, 'Authenticated service token required.');

        $retryAfter = $this->limiter->consume($identity, $token);

        if ($retryAfter !== null) {
            return response()->json([
                'message' => 'Too many requests.',
            ], 429, [
                'Retry-After' => (string) $retryAfter,
            ]);
        }

        return $next($request);
    }
}
