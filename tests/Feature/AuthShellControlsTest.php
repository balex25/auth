<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

it('defines conditional auth robots canonical and hreflang rules', function () {
    $head = File::get(dirname(__DIR__, 2).'/resources/views/includes/head.blade.php');
    $seo = File::get(dirname(__DIR__, 2).'/resources/views/includes/seo.blade.php');
    $setup = File::get(dirname(__DIR__, 2).'/resources/views/components/layouts/setup.blade.php');

    expect(Blade::compileString($head))->not->toBeEmpty();
    expect(Blade::compileString($seo))->not->toBeEmpty();

    expect($head)
        ->toContain("@include('auth::includes.seo')")
        ->not->toContain('name="canonical"')
        ->not->toContain('request()->query()');

    expect($setup)->toContain("@include('auth::includes.seo', ['authSeoForceNoindex' => true])");

    expect($seo)
        ->toContain('<meta name="robots" content="index, follow">')
        ->toContain('<meta name="googlebot" content="index, follow">')
        ->toContain('<meta name="robots" content="noindex, nofollow">')
        ->toContain('<meta name="googlebot" content="noindex, nofollow">')
        ->toContain('config(\'app.locales\', [\'en\'])')
        ->toContain('request()->path()')
        ->toContain("request()->server('QUERY_STRING', '')")
        ->toContain('<link rel="canonical" href="{{ url()->current() }}">')
        ->toContain('<link rel="alternate" hreflang="{{ $authSeoLocale }}"')
        ->toContain('<link rel="alternate" hreflang="x-default"')
        ->not->toContain('request()->query()');
});

it('renders indexable canonical metadata and hreflang links for clean auth requests', function (string $url, string $pathSuffix) {
    config()->set('app.locales', ['en', 'es', 'ru']);
    config()->set('app.locale', 'ru');

    $request = Request::create('https://beamngmods.test'.$url);
    app()->instance('request', $request);
    app('url')->setRequest($request);

    $seo = File::get(dirname(__DIR__, 2).'/resources/views/includes/seo.blade.php');
    $html = Blade::render($seo);

    expect(substr_count($html, '<meta name="robots" content="index, follow">'))->toBe(1)
        ->and(substr_count($html, '<meta name="googlebot" content="index, follow">'))->toBe(1)
        ->and($html)->toContain('<link rel="canonical" href="'.url()->current().'">')
        ->and($html)->not->toContain('noindex');

    foreach (['en', 'es', 'ru'] as $locale) {
        expect($html)->toContain('<link rel="alternate" hreflang="'.$locale.'" href="'.url('/'.$locale.$pathSuffix).'">');
    }

    expect($html)->toContain('<link rel="alternate" hreflang="x-default" href="'.url('/en'.$pathSuffix).'">');
})->with([
    'clean login' => ['/en/auth/login', '/auth/login'],
    'clean registration' => ['/en/auth/register', '/auth/register'],
    'clean password reset request' => ['/en/auth/password/reset', '/auth/password/reset'],
]);

it('renders noindex metadata without canonical or hreflang links for auth requests with a query string', function (string $url) {
    config()->set('app.locales', ['en', 'es', 'ru']);
    config()->set('app.locale', 'en');

    $request = Request::create('https://beamngmods.test'.$url);
    app()->instance('request', $request);
    app('url')->setRequest($request);

    $seo = File::get(dirname(__DIR__, 2).'/resources/views/includes/seo.blade.php');
    $html = Blade::render($seo);

    expect(substr_count($html, '<meta name="robots" content="noindex, nofollow">'))->toBe(1)
        ->and(substr_count($html, '<meta name="googlebot" content="noindex, nofollow">'))->toBe(1)
        ->and($html)->not->toContain('content="index, follow"')
        ->and($html)->not->toContain('rel="canonical"')
        ->and($html)->not->toContain('rel="alternate"');
})->with([
    'registration redirect' => ['/en/auth/register?redirect=https%3A%2F%2Fbeamngmods.test%2Fru'],
    'login redirect' => ['/en/auth/login?redirect=%2Fen%2Fdashboard'],
    'theme query' => ['/en/auth/login?theme=dark'],
]);

it('always keeps auth setup pages out of search indexes', function () {
    config()->set('app.locales', ['en', 'es', 'ru']);

    $request = Request::create('https://beamngmods.test/en/auth/setup');
    app()->instance('request', $request);
    app('url')->setRequest($request);

    $seo = File::get(dirname(__DIR__, 2).'/resources/views/includes/seo.blade.php');
    $html = Blade::render($seo, ['authSeoForceNoindex' => true]);

    expect($html)
        ->toContain('<meta name="robots" content="noindex, nofollow">')
        ->toContain('<meta name="googlebot" content="noindex, nofollow">')
        ->not->toContain('rel="canonical"')
        ->not->toContain('rel="alternate"');
});

it('keeps browser autofill aligned with the auth input palette', function () {
    $styles = File::get(dirname(__DIR__, 2).'/resources/css/auth.css');

    expect($styles)
        ->toContain('input.auth-component-input:-webkit-autofill')
        ->toContain('0 0 0 1000px var(--color-gray-800) inset')
        ->toContain('.dark input.auth-component-input:-webkit-autofill')
        ->toContain('0 0 0 1000px black inset');
});

it('provides locale and theme controls beside the back link', function () {
    $layout = File::get(dirname(__DIR__, 2).'/resources/views/components/layouts/app.blade.php');
    $theme = File::get(dirname(__DIR__, 2).'/resources/views/includes/theme.blade.php');

    expect(Blade::compileString($layout))->not->toBeEmpty();

    expect($layout)
        ->toContain('data-auth-footer-controls')
        ->toContain('data-auth-language-switcher')
        ->toContain('data-auth-theme-switcher')
        ->toContain("setTheme('light')")
        ->toContain("setTheme('system')")
        ->toContain("setTheme('dark')")
        ->toContain("new URLSearchParams(window.location.search).get('theme')")
        ->toContain('url.pathname + url.search + url.hash');

    expect($theme)
        ->toContain("window.addEventListener('theme-change'")
        ->toContain("window.dispatchEvent(new CustomEvent('theme-synced'");
});

it('provides a preloaded auth background gallery with a config fallback', function () {
    $layout = File::get(dirname(__DIR__, 2).'/resources/views/components/layouts/app.blade.php');

    expect(Blade::compileString($layout))->not->toBeEmpty();

    expect($layout)
        ->toContain('data-auth-background-meta')
        ->toContain('backgroundImages: @js($authBackgroundImages)')
        ->toContain('loader.decode()')
        ->toContain('preloadNextBackground()')
        ->toContain('updateBackgroundBlur($event)')
        ->toContain("backgroundBlurred ? 'blur-xs' : 'blur-0'")
        ->toContain('nextBackgroundUrl')
        ->toContain('backgroundTransitioning')
        ->toContain('autoplayProgress')
        ->toContain("'transform: scaleX(' + autoplayProgress")
        ->toContain('rounded-tl-none rounded-r-none')
        ->toContain('shadow-[8px_-8px_0_8px_var(--auth-meta-bg)]')
        ->toContain('data-auth-background-title-link')
        ->toContain('data-auth-background-linked-title')
        ->toMatch('/data-auth-background-linked-title[\s\S]+class="shape h4 min-w-0 truncate"/')
        ->toContain('data-auth-background-author-link')
        ->toContain("'author_verified' => (bool) (\$image['author_verified'] ?? false)")
        ->toContain('currentBackground().author_verified')
        ->toContain('<x-auth::elements.verified-badge />')
        ->toContain("config('devdojo.auth.appearance.background.image')")
        ->toMatch('/window\.requestAnimationFrame\(\(\) => \{\s+this\.activeBackgroundIndex = index;\s+this\.backgroundTransitioning = true;/')
        ->not->toContain('this.backgroundMetaVisible = false')
        ->not->toContain("backgroundBlurred ? 'blur-md scale-[1.02]' : 'blur-0 scale-100'")
        ->not->toContain('https://beamngmods.test/en/profile/hana')
        ->not->toContain('Media 1-1');

    expect(substr_count($layout, 'rel="noopener noreferrer"'))->toBe(2)
        ->and(substr_count($layout, 'data-auth-background-verified-badge'))->toBe(2)
        ->and(substr_count($layout, '<path d="M15 3h6v6"/>'))->toBe(2)
        ->and(substr_count($layout, '<path d="M10 14 21 3"/>'))->toBe(2)
        ->and(substr_count($layout, '<path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/>'))->toBe(2);
});

it('renders the auth-owned verified badge with the shared visual variants', function () {
    $html = Blade::render('<x-auth::elements.verified-badge label="Verified creator" size="md" data-verified-badge />');

    expect($html)
        ->toContain('data-verified-badge')
        ->toContain('title="Verified creator"')
        ->toContain('size-4 mt-0')
        ->toContain('fill-orange-600 dark:fill-orange-500')
        ->toContain('M23,12l-2.44-2.79');
});
