<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureRole
{
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();
        abort_if(! $user, 403);

        $allowed = $user->roles()->whereIn('slug', $roles)->exists();

        if (! $allowed && ! in_array('super-admin', $roles, true)) {
            $allowed = $user->roles()->where('slug', 'super-admin')->exists();
        }

        abort_unless($allowed, 403);

        return $next($request);
    }
}
