<?php

namespace Devdojo\Auth\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class StoreIntendedRedirect
{
    /**
     * Handle an incoming request.
     *
     * @param  Closure(Request): Response  $next
     */
    public function handle(Request $request, Closure $next): Response
    {
        $redirect = $request->query('redirect');

        if (is_string($redirect) && $redirect !== '') {
            $redirect = trim($redirect);
            $path = parse_url($redirect, PHP_URL_PATH);

            if (! $path || ! preg_match('#^/(?:[a-z]{2}/)?auth/(login|register|two-factor-challenge)#i', $path)) {
                $appHost = parse_url(config('app.url'), PHP_URL_HOST);
                $redirectHost = parse_url($redirect, PHP_URL_HOST);
                $scheme = parse_url($redirect, PHP_URL_SCHEME);

                $hostAllowed = ! $redirectHost || ($appHost && strcasecmp($redirectHost, $appHost) === 0);
                $schemeAllowed = ! $scheme || in_array(strtolower($scheme), ['http', 'https'], true);

                if ($hostAllowed && $schemeAllowed) {
                    $request->session()->put('url.intended', $redirect);
                }
            }
        }

        return $next($request);
    }
}
