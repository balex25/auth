<?php

namespace Devdojo\Auth;

use Illuminate\Http\RedirectResponse;
use Livewire\Features\SupportRedirects\Redirector as LivewireRedirector;

class Helper
{
    // Build your next great package.
    public static function activeProviders()
    {
        $providers = config('devdojo.auth.providers');
        $activeProviders = [];
        foreach ($providers as $slug => $provider) {
            if ($provider['active']) {
                $activeProviders[$slug] = (object) $provider;
            }
        }

        return $activeProviders;
    }

    public static function getProvidersFromArray($array)
    {
        $providers = config('devdojo.auth.providers');
        $providersInArray = [];
        foreach ($providers as $slug => $provider) {
            if ($provider['active'] && in_array($slug, $array)) {
                $providersInArray[$slug] = (object) $provider;
            }
        }

        return $providersInArray;
    }

    public static function convertSlugToTitle($slug)
    {
        $readable = str_replace('_', ' ', str_replace('-', ' ', $slug));

        return ucwords($readable);
    }

    public static function authUrl(string $routeName, array $parameters = [], bool $preserveQuery = false): string
    {
        $url = route($routeName, $parameters);
        $locale = self::currentLocale();

        if ($locale !== null) {
            $url = self::prefixUrlPath($url, $locale);
        }

        if ($preserveQuery) {
            $url = self::mergeQuery($url, request()->query());
        }

        return $url;
    }

    public static function localizedUrl(string $path, bool $preserveQuery = false): string
    {
        $url = url($path);
        $locale = self::currentLocale();

        if ($locale !== null) {
            $url = self::prefixUrlPath($url, $locale);
        }

        if ($preserveQuery) {
            $url = self::mergeQuery($url, request()->query());
        }

        return $url;
    }

    public static function localizedRedirectTarget(string $target): string
    {
        $targetHost = parse_url($target, PHP_URL_HOST);
        $applicationHost = parse_url(config('app.url'), PHP_URL_HOST);

        if (is_string($targetHost) && $targetHost !== '' && $targetHost !== $applicationHost) {
            return $target;
        }

        return self::localizedUrl($target);
    }

    public static function currentLocale(): ?string
    {
        $locales = config('app.locales', []);
        $locale = request()->segment(1);

        if (is_string($locale) && in_array($locale, $locales, true)) {
            return $locale;
        }

        $routeLocale = request()->route('locale');

        if (is_string($routeLocale) && in_array($routeLocale, $locales, true)) {
            return $routeLocale;
        }

        $refererLocale = self::localeFromUrl(request()->headers->get('referer'), $locales);

        if ($refererLocale !== null) {
            return $refererLocale;
        }

        $sessionLocale = session()->get('auth.locale');

        if (is_string($sessionLocale) && in_array($sessionLocale, $locales, true)) {
            return $sessionLocale;
        }

        $applicationLocale = app()->getLocale();

        if (in_array($applicationLocale, $locales, true)) {
            return $applicationLocale;
        }

        return null;
    }

    public static function intendedRedirect(string $default): RedirectResponse|LivewireRedirector
    {
        return redirect()->intended(self::localizedRedirectTarget($default));
    }

    private static function localeFromUrl(?string $url, array $locales): ?string
    {
        if ($url === null) {
            return null;
        }

        $path = parse_url($url, PHP_URL_PATH);

        if (! is_string($path)) {
            return null;
        }

        $locale = collect(explode('/', trim($path, '/')))->first();

        return is_string($locale) && in_array($locale, $locales, true) ? $locale : null;
    }

    private static function prefixUrlPath(string $url, string $locale): string
    {
        $parts = parse_url($url);
        $path = $parts['path'] ?? '/';
        $localePrefix = '/'.$locale;

        if ($path === $localePrefix || str_starts_with($path, $localePrefix.'/')) {
            return $url;
        }

        $segments = array_values(array_filter(explode('/', $path), fn ($segment) => $segment !== ''));
        $locales = config('app.locales', []);

        if (isset($segments[0]) && in_array($segments[0], $locales, true)) {
            $segments[0] = $locale;
            $parts['path'] = '/'.implode('/', $segments);

            return self::buildUrl($parts);
        }

        $parts['path'] = $localePrefix.($path === '/' ? '' : $path);

        return self::buildUrl($parts);
    }

    private static function mergeQuery(string $url, array $query): string
    {
        if ($query === []) {
            return $url;
        }

        $parts = parse_url($url);
        $currentQuery = [];

        if (isset($parts['query'])) {
            parse_str($parts['query'], $currentQuery);
        }

        $parts['query'] = http_build_query(array_merge($currentQuery, $query));

        return self::buildUrl($parts);
    }

    private static function buildUrl(array $parts): string
    {
        $scheme = isset($parts['scheme']) ? $parts['scheme'].'://' : '';
        $host = $parts['host'] ?? '';
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';
        $user = $parts['user'] ?? '';
        $pass = isset($parts['pass']) ? ':'.$parts['pass'] : '';
        $pass = ($user !== '' || $pass !== '') ? $pass.'@' : '';
        $path = $parts['path'] ?? '';
        $query = isset($parts['query']) && $parts['query'] !== '' ? '?'.$parts['query'] : '';
        $fragment = isset($parts['fragment']) ? '#'.$parts['fragment'] : '';

        return $scheme.$user.$pass.$host.$port.$path.$query.$fragment;
    }

    public static function convertHexToRGBString($hex)
    {
        // Remove the '#' character if present
        $hex = str_replace('#', '', $hex);

        // Ensure the hex string is properly formatted
        if (strlen($hex) === 3) {
            $hex = str_repeat($hex[0], 2).str_repeat($hex[1], 2).str_repeat($hex[2], 2);
        } elseif (strlen($hex) !== 6) {
            throw new \Exception('Invalid hex color length');
        }

        // Split the hex color into its RGB components
        $r = hexdec(substr($hex, 0, 2));
        $g = hexdec(substr($hex, 2, 2));
        $b = hexdec(substr($hex, 4, 2));

        // Return the RGB string
        return "$r $g $b";
    }
}
