<?php

namespace App\Http\Middleware;

use App\Services\Machine\ServiceIdentityAuthorizer;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureServiceAbility
{
    public function __construct(private ServiceIdentityAuthorizer $authorizer) {}

    public function handle(Request $request, Closure $next, string $ability): Response
    {
        $identity = $request->user();

        abort_unless($this->authorizer->allows($identity, $ability), 403, 'Service token does not have the required ability.');

        return $next($request);
    }
}
