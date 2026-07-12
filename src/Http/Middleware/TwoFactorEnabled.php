<?php

namespace Devdojo\Auth\Http\Middleware;

use Closure;
use Devdojo\Auth\Helper;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class TwoFactorEnabled
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): (Response)  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        if (! config('devdojo.auth.settings.enable_2fa')) {
            return redirect(Helper::localizedUrl('/'));
        }

        return $next($request);
    }
}
