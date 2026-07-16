<?php

use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\ViewErrorBag;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.turnstile.site_key', 'turnstile-site-key');
    config()->set('services.turnstile.secret', 'turnstile-secret-key');
});

it('eagerly loads a visible Turnstile widget without swallowing the form submission', function () {
    view()->share('errors', new ViewErrorBag);

    $html = Blade::render('<x-auth::elements.turnstile action="auth_login" :eager="true" />');

    expect($html)
        ->toContain('turnstile-site-key')
        ->toContain('api.js?render=explicit')
        ->toContain("appearance: 'always'")
        ->toContain('eager: true')
        ->toContain('auth_login')
        ->not->toContain('event.preventDefault()')
        ->not->toContain('requestSubmit()');
});

it('renders Turnstile in the locale from the auth URL', function (string $locale) {
    config()->set('app.locales', ['en', 'es', 'ru']);
    app()->instance('request', Request::create('/'.$locale.'/auth/login'));
    view()->share('errors', new ViewErrorBag);

    $html = Blade::render('<x-auth::elements.turnstile action="auth_login" />');

    expect($html)->toContain("language: '{$locale}'");
})->with([
    'English' => 'en',
    'Spanish' => 'es',
    'Russian' => 'ru',
]);

it('requires Turnstile before a password authentication attempt', function () {
    Livewire::test('auth.login')
        ->set('showPasswordField', true)
        ->set('email', 'person@example.com')
        ->set('password', 'password')
        ->call('authenticate')
        ->assertHasErrors(['turnstileToken']);
});

it('verifies the token through the configured Cloudflare endpoint', function () {
    Http::fake([
        'https://challenges.cloudflare.com/turnstile/v0/siteverify' => Http::response([
            'success' => true,
            'action' => 'auth_login',
        ]),
    ]);

    $user = User::factory()->create([
        'password' => Hash::make('password123'),
    ]);

    Livewire::test('auth.login')
        ->set('showPasswordField', true)
        ->set('email', $user->email)
        ->set('password', 'password123')
        ->set('turnstileToken', 'verified-token')
        ->call('authenticate')
        ->assertHasNoErrors(['turnstileToken'])
        ->assertRedirect()
        ->assertNotDispatched('auth-turnstile-reset');

    Http::assertSent(fn ($request): bool => $request->url() === 'https://challenges.cloudflare.com/turnstile/v0/siteverify'
        && $request['secret'] === 'turnstile-secret-key'
        && $request['response'] === 'verified-token');
});

it('does not render or enforce Turnstile when no site key is configured', function () {
    config()->set('services.turnstile.site_key', null);

    $this->get('/auth/login')
        ->assertOk()
        ->assertDontSee('api.js?render=explicit', false);
});
