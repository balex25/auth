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
    $appLayoutView = file_get_contents(__DIR__.'/../../resources/views/components/layouts/app.blade.php');
    $sessionMessageView = file_get_contents(__DIR__.'/../../resources/views/components/elements/session-message.blade.php');
    $themeView = file_get_contents(__DIR__.'/../../resources/views/includes/theme.blade.php');
    $turnstileView = file_get_contents(__DIR__.'/../../resources/views/components/elements/turnstile.blade.php');
    $authStyles = file_get_contents(__DIR__.'/../../resources/css/auth.css');
    Blade::compileString($loginView);

    expect($loginView)
        ->toContain("config('devdojo.auth.settings.passwordless_login_enabled', false)")
        ->toContain('data-auth="passwordless-login-button"')
        ->toContain(':data-auth-turnstile-bypass="$showPasswordField ? false : true"')
        ->toContain('requestPasswordlessLogin')
        ->toContain('passwordless_login_max_attempts_per_minute')
        ->toContain('@if($passwordlessLinkSent)')
        ->toContain('<x-auth::elements.session-message')
        ->toContain('type="success"')
        ->toContain("config('devdojo.auth.settings.passwordless_login_enabled', false) && ! \$showPasswordField")
        ->and(config('devdojo.auth.language.login.passwordless_button'))
        ->toBe('Continue with passwordless')
        ->and($sessionMessageView)
        ->toContain('bg-red-50 dark:bg-red-600')
        ->toContain('bg-orange-50 dark:bg-orange-600')
        ->toContain('bg-green-50 dark:bg-green-600')
        ->toContain('bg-blue-50 dark:bg-blue-600')
        ->toContain("@case('success')")
        ->toContain("@case('error')")
        ->toContain("@case('warning')")
        ->and($appLayoutView)
        ->toContain("@include('auth::includes.theme')")
        ->toContain('@livewireScripts')
        ->and($themeView)
        ->toContain("localStorage.getItem('theme')")
        ->toContain("root.classList.toggle('dark', isDark)")
        ->and($turnstileView)
        ->toContain('this.form?.addEventListener(\'submit\'')
        ->toContain("event.submitter?.matches('[data-auth-turnstile-bypass]')")
        ->toContain('this.$nextTick(() => this.loadTurnstile())')
        ->not->toContain('event.preventDefault()')
        ->and($authStyles)
        ->toContain('[x-cloak]')
        ->toContain('display: none !important');

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

it('integrates passwordless settings and preview into auth setup', function () {
    app()->detectEnvironment(fn () => 'local');

    $setupLayout = file_get_contents(__DIR__.'/../../resources/views/components/layouts/setup.blade.php');
    $settingsView = file_get_contents(__DIR__.'/../../resources/views/pages/auth/setup/settings.blade.php');
    $previewView = file_get_contents(__DIR__.'/../../resources/views/pages/auth/setup/preview/passwordless.blade.php');

    expect($setupLayout)
        ->toContain("'name' : 'Passwordless Login'")
        ->toContain("'url' : '/auth/setup/preview/passwordless'")
        ->and($settingsView)
        ->toContain("'passwordless' => ['title' => 'Passwordless Login'")
        ->toContain('is_int($settings[$key]) => (int) $value')
        ->toContain("\$key === 'passwordless_login_expires_minutes' ? 60 : null")
        ->and($previewView)
        ->toContain('<x-auth::elements.passwordless-login :auto-submit="false"')
        ->and(config('devdojo.auth.descriptions.settings.passwordless_login_enabled'))
        ->not->toBeEmpty()
        ->and(config('devdojo.auth.descriptions.settings.passwordless_login_expires_minutes'))
        ->not->toBeEmpty()
        ->and(config('devdojo.auth.descriptions.settings.passwordless_login_max_attempts_per_minute'))
        ->not->toBeEmpty();

    $this->get('/auth/setup/preview/passwordless')
        ->assertSuccessful()
        ->assertSee('auth.passwordless.confirm_button')
        ->assertDontSee('form.submit()', false);
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
