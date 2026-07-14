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
