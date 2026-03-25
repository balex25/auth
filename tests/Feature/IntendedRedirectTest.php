<?php

use App\Models\User;
use Illuminate\Auth\Events\Registered;
use Illuminate\Support\Facades\Auth;

beforeEach(function () {
    User::query()->delete();
});

it('stores a safe redirect from the login page and uses it after login', function () {
    $user = createUser(['password' => Hash::make('password123')]);

    $this->get('/auth/login?redirect=/dashboard?tab=security')->assertOk();

    expect(session('url.intended'))->toBe('/dashboard?tab=security');

    Livewire::test('auth.login')
        ->set('email', $user->email)
        ->set('showPasswordField', true)
        ->set('password', 'password123')
        ->call('authenticate')
        ->assertHasNoErrors()
        ->assertRedirect('/dashboard?tab=security');
});

it('stores a safe redirect from the registration page and uses it after registration', function () {
    $this->mock(Registered::class);
    config()->set('devdojo.auth.settings.registration_include_name_field', true);
    config()->set('devdojo.auth.settings.registration_require_email_verification', false);

    $this->get('/auth/register?redirect=/welcome')->assertOk();

    expect(session('url.intended'))->toBe('/welcome');

    Livewire::test('auth.register')
        ->set('email', 'user@example.com')
        ->set('password', 'secret1234')
        ->set('name', 'John Doe')
        ->call('register')
        ->assertHasNoErrors()
        ->assertRedirect('/welcome');

    expect(Auth::check())->toBeTrue();
});

it('does not store an external redirect target', function () {
    $this->get('/auth/login?redirect=https://evil.example/phish')->assertOk();

    expect(session()->has('url.intended'))->toBeFalse();
});

it('does not store a redirect target back to auth pages', function () {
    $this->get('/auth/login?redirect=/auth/register')->assertOk();

    expect(session()->has('url.intended'))->toBeFalse();
});
