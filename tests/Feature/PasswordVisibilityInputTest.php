<?php

use Illuminate\Support\Facades\Blade;

it('renders a visibility toggle for password inputs', function () {
    $html = Blade::render('<x-auth::elements.input type="password" label="Password" />');

    expect($html)
        ->toContain("x-bind:type=\"passwordVisible ? 'text' : 'password'\"")
        ->toContain('passwordVisible = ! passwordVisible')
        ->toContain('x-bind:aria-pressed="passwordVisible"')
        ->toContain('passwordVisible ? hidePasswordLabel : showPasswordLabel')
        ->toContain('lucide-eye-icon lucide-eye')
        ->toContain('lucide-eye-off-icon lucide-eye-off')
        ->toContain('auth.passwordVisibility.show')
        ->toContain('auth.passwordVisibility.hide');
});

it('does not render a visibility toggle for non-password inputs', function () {
    $html = Blade::render('<x-auth::elements.input type="email" label="Email" />');

    expect($html)
        ->toContain('type="email"')
        ->not->toContain('passwordVisible = ! passwordVisible');
});
