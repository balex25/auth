<?php

use Devdojo\Auth\Helper;
use Devdojo\Auth\Notifications\PasswordlessLoginNotification;
use Devdojo\Auth\PasswordlessLoginManager;
use Devdojo\Auth\Tests\Fixtures\User;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Blade;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;

function createPasswordlessUser(array $attributes = []): User
{
    return User::query()->create(array_merge([
        'name' => 'Passwordless User',
        'email' => fake()->unique()->safeEmail(),
        'password' => Hash::make('password'),
    ], $attributes));
}

beforeEach(function () {
    $this->withoutVite();
    config()->set('devdojo.auth.settings.passwordless_login_enabled', true);
    config()->set('devdojo.auth.settings.passwordless_login_expires_minutes', 10);
    config()->set('devdojo.auth.settings.passwordless_login_max_attempts_per_minute', 3);
    config()->set('services.turnstile.site_key', null);
});

it('guards the passwordless login UI and action with the feature setting', function () {
    $loginView = file_get_contents(__DIR__.'/../../resources/views/pages/auth/login.blade.php');
    $turnstileView = file_get_contents(__DIR__.'/../../resources/views/components/elements/turnstile.blade.php');
    Blade::compileString($loginView);

    expect($loginView)
        ->toContain("config('devdojo.auth.settings.passwordless_login_enabled', false)")
        ->toContain('data-auth="passwordless-login-button"')
        ->toContain(':data-auth-turnstile-bypass="$showPasswordField ? false : true"')
        ->toContain("@if(config('devdojo.auth.settings.passwordless_login_enabled', false))")
        ->toContain('requestPasswordlessLogin')
        ->toContain('passwordless_login_max_attempts_per_minute')
        ->toContain('@if($passwordlessLinkSent)')
        ->toContain('<x-auth::elements.session-message')
        ->toContain('type="success"')
        ->and(config('devdojo.auth.language.login.passwordless_button'))
        ->toBe('Continue with passwordless')
        ->and($turnstileView)
        ->toContain("event.submitter?.matches('[data-auth-turnstile-bypass]')");

    $passwordlessButton = Str::between(
        $loginView,
        'data-auth="passwordless-login-button"',
        '{{ $language->login->passwordless_button }}',
    );

    expect($passwordlessButton)->not->toContain('<svg');
});

it('sends a one-time link to a verified user', function () {
    $user = createPasswordlessUser([
        'email_verified_at' => now(),
    ]);
    Notification::fake();

    app(PasswordlessLoginManager::class)->send($user);

    Notification::assertSentTo($user, PasswordlessLoginNotification::class, function (PasswordlessLoginNotification $notification): bool {
        return str_contains($notification->url, '/auth/passwordless/')
            && $notification->expiresInMinutes === 10;
    });

    expect(is_subclass_of(PasswordlessLoginNotification::class, ShouldQueue::class))->toBeFalse();
});

it('refuses passwordless login for an unverified account', function () {
    Notification::fake();
    $user = createPasswordlessUser(['email_verified_at' => null]);

    expect(fn () => app(PasswordlessLoginManager::class)->send($user))
        ->toThrow(RuntimeException::class);

    Notification::assertNothingSent();
});

it('does not consume a magic link on get and allows it only once on post', function () {
    $user = createPasswordlessUser(['email_verified_at' => now()]);
    Notification::fake();
    $url = app(PasswordlessLoginManager::class)->send($user);

    $this->get($url)
        ->assertSuccessful()
        ->assertSee('auth.passwordless.confirm_button')
        ->assertSee('passwordless-login-form')
        ->assertSee('form.submit()', false);
    $this->assertGuest();

    $this->post($url)
        ->assertRedirect(Helper::localizedRedirectTarget(config('devdojo.auth.settings.redirect_after_auth')));
    $this->assertAuthenticatedAs($user);

    Auth::logout();

    $this->post($url)
        ->assertRedirect(Helper::authUrl('auth.login'));
    $this->assertGuest();
});

it('authenticates with a localized one-time link', function () {
    config()->set('app.locales', ['en', 'ru']);
    $user = createPasswordlessUser(['email_verified_at' => now()]);
    Notification::fake();
    $url = app(PasswordlessLoginManager::class)->send($user, 'en');

    $this->get($url)
        ->assertSuccessful()
        ->assertSee('passwordless-login-form');

    $this->post($url)
        ->assertRedirect(Helper::localizedRedirectTarget(config('devdojo.auth.settings.redirect_after_auth')));

    $this->assertAuthenticatedAs($user);

    Auth::logout();

    $this->post($url)
        ->assertRedirect(Helper::authUrl('auth.login'))
        ->assertSessionHas('error', 'auth.passwordless.invalid_or_expired');
});

it('requires the two factor challenge after consuming a magic link', function () {
    config()->set('devdojo.auth.settings.enable_2fa', true);
    $user = createPasswordlessUser([
        'email_verified_at' => now(),
        'two_factor_confirmed_at' => now(),
    ]);
    Notification::fake();
    $url = app(PasswordlessLoginManager::class)->send($user, null, '/billing');

    $this->post($url)
        ->assertRedirect(Helper::authUrl('auth.two-factor-challenge'))
        ->assertSessionHas('login.id', $user->getKey())
        ->assertSessionHas('url.intended', '/billing');
    $this->assertGuest();
});

it('invalidates a magic link when the account email changes', function () {
    $user = createPasswordlessUser(['email_verified_at' => now()]);
    Notification::fake();
    $url = app(PasswordlessLoginManager::class)->send($user);

    $user->forceFill(['email' => 'changed@example.com'])->save();

    $this->post($url)
        ->assertRedirect(Helper::authUrl('auth.login'));
    $this->assertGuest();
});

it('returns not found for passwordless links when the feature is disabled', function () {
    $user = createPasswordlessUser(['email_verified_at' => now()]);
    Notification::fake();
    $url = app(PasswordlessLoginManager::class)->send($user);
    config()->set('devdojo.auth.settings.passwordless_login_enabled', false);

    $this->get($url)->assertNotFound();
});
