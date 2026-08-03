<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class RoleMiddleware
{
    public function handle(Request $request, Closure $next, ...$roles)
    {
        $user = $request->user();

        if (!$user) {
            abort(403, 'Unauthorized');
        }

        // Developer mode diperlakukan sebagai owner untuk semua route role-gated.
        if ($user->isDeveloper()) {
            return $next($request);
        }

        // OWNER boleh akses semua route yang pakai middleware ini
        if ($user->role === 'owner') {
            return $next($request);
        }

        if (!in_array($user->role, $roles, true)) {
            abort(403, 'Anda tidak punya akses ke halaman ini.');
        }

        return $next($request);
    }
}
