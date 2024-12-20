<?php

namespace App\Modules\SocialMedia\Presentation\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

class CanReadPostsMiddleware
{
    /**
     * Handle an incoming request.
     */
    public function handle(Request $request, Closure $next)
    {
        return $next($request);
    }
}
