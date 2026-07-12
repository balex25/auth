<?php

use Devdojo\Auth\Helper;
use Illuminate\Http\Request;

test('that authentication URLs return a 200', function ($url) {
    $this->get($url)->assertOK();
})->with('urls');

it('preserves the referring page locale during a Livewire request', function () {
    config()->set('app.locales', ['en', 'ru']);
    app()->setLocale('ru');

    $request = Request::create('/livewire/update', 'POST', server: [
        'HTTP_REFERER' => 'https://beamngmods.test/en/auth/login',
    ]);
    app()->instance('request', $request);

    expect(Helper::localizedUrl('auth/password/reset'))
        ->toEndWith('/en/auth/password/reset');
});

it('falls back to the application locale without a localized request or referrer', function () {
    config()->set('app.locales', ['en', 'ru']);
    app()->setLocale('ru');
    app()->instance('request', Request::create('/livewire/update', 'POST'));

    expect(Helper::localizedUrl('auth/register'))
        ->toEndWith('/ru/auth/register');
});
