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

        // Admin memiliki akses baca saja pada Master Item. Developer tetap
        // diperlakukan sebagai owner dan tidak terkena pembatasan ini.
        if ($isAdmin && ! $user->isDeveloper()) {
            abort_unless(
                in_array($request->route()?->getName(), [
                    'master.items.index',
                    'master.items.show',
                ], true),
                403,
                'Role admin hanya memiliki akses lihat pada Master Items.'
            );
        }

        abort_unless(
            $user->isOwner() || $isAdmin || $user->canAccessModule('master'),
            403,
            'Akses Master Items belum diizinkan untuk role ini.'
        );

        return $next($request);
    }
}
