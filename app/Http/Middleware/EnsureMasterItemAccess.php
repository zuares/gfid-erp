<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureMasterItemAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        abort_unless($user, 403, 'Harus login untuk mengakses Master Items.');

        $isAdmin = strtolower((string) $user->role) === 'admin';

        abort_unless(
            $user->isOwner() || $isAdmin || $user->canAccessModule('master'),
            403,
            'Akses Master Items belum diizinkan untuk role ini.'
        );

        return $next($request);
    }
}
