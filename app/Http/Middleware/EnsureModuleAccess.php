<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureModuleAccess
{
    public function handle(Request $request, Closure $next, string $module): Response
    {
        $user = $request->user();

        abort_unless($user, 403, 'Harus login untuk mengakses halaman ini.');
        abort_unless(
            $user->canAccessModule($module),
            403,
            'Akses modul ini belum diizinkan untuk user login.'
        );

        return $next($request);
    }
}
