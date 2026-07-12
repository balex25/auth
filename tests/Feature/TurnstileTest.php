<?php

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Livewire\Livewire;

beforeEach(function () {
    config()->set('services.turnstile.site_key', 'turnstile-site-key');
    config()->set('services.turnstile.secret', 'turnstile-secret-key');
});

it('lazy loads a visible Turnstile widget on auth pages', function () {
    Livewire::test('auth.login')
        ->set('showPasswordField', true)
        ->assertSee('turnstile-site-key')
        ->assertSee('api.js?render=explicit', false)
        ->assertSee("appearance: 'always'", false)
        ->assertSee('requestSubmit()', false)
        ->assertSee('auth_login');
});

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
