<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStandaloneAccountingAccess
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();
        $role = strtolower((string) ($user?->role ?? ''));

        abort_unless(
            $user && ($role === 'admin' || $user->isOwner()),
            403,
            'Akuntansi Baru hanya dapat diakses oleh admin atau owner.'
        );

        return $next($request);
    }
}
