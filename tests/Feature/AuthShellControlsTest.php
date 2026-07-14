<?php

use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\File;

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
        ->toContain("config('devdojo.auth.appearance.background.image')")
        ->toMatch('/window\.requestAnimationFrame\(\(\) => \{\s+this\.activeBackgroundIndex = index;\s+this\.backgroundTransitioning = true;/')
        ->not->toContain('this.backgroundMetaVisible = false')
        ->not->toContain("backgroundBlurred ? 'blur-md scale-[1.02]' : 'blur-0 scale-100'")
        ->not->toContain('https://beamngmods.test/en/profile/hana')
        ->not->toContain('Media 1-1');

    expect(substr_count($layout, 'rel="noopener noreferrer"'))->toBe(2)
        ->and(substr_count($layout, 'lucide-move-up-right-icon'))->toBe(2)
        ->and(substr_count($layout, '<path d="M13 5H19V11"/>'))->toBe(2)
        ->and(substr_count($layout, '<path d="M19 5L5 19"/>'))->toBe(2);
});
