<?php

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProAccount
{
    public function handle(Request $request, Closure $next): Response
    {
        abort_if(! $request->user()?->isPro(), 403);

        return $next($request);
    }
}
