<?php

namespace Devdojo\Auth;

use Devdojo\Auth\Notifications\PasswordlessLoginNotification;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Contracts\Translation\HasLocalePreference;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\URL;
use Illuminate\Support\Str;
use RuntimeException;

class PasswordlessLoginManager
{
    /**
     * @return array{user_id: int|string, email_fingerprint: string, redirect_to: string|null, locale: string|null}|null
     */
    public function consume(string $token): ?array
    {
        $tokenHash = $this->tokenHash($token);

        return Cache::lock($this->lockKey($tokenHash), 5)->block(2, function () use ($tokenHash): ?array {
            $payload = Cache::pull($this->tokenKey($tokenHash));

            if (! is_array($payload) || ! isset($payload['user_id'], $payload['email_fingerprint'])) {
                return null;
            }

            $userKey = $this->userKey($payload['user_id']);

            if (! hash_equals($tokenHash, (string) Cache::get($userKey, ''))) {
                return null;
            }

            Cache::forget($userKey);

            return $payload;
        });
    }

    public function emailFingerprint(string $email): string
    {
        return hash_hmac('sha256', Str::lower(trim($email)), (string) config('app.key'));
    }

    public function send(Authenticatable $user, ?string $locale = null, ?string $redirectTo = null): string
    {
        if (! config('devdojo.auth.settings.passwordless_login_enabled', false)) {
            throw new RuntimeException('Passwordless login is disabled.');
        }

        $email = data_get($user, 'email');

        if (
            ! is_string($email)
            || ! filter_var($email, FILTER_VALIDATE_EMAIL)
            || data_get($user, 'email_verified_at') === null
            || ! method_exists($user, 'notify')
        ) {
            throw new RuntimeException('The authenticatable user cannot receive a passwordless login notification.');
        }

        $expiresAt = now()->addMinutes($this->expiresInMinutes());
        $locale = $this->notificationLocale($user, $locale);
        $token = Str::random(64);
        $tokenHash = $this->tokenHash($token);
        $userKey = $this->userKey($user->getAuthIdentifier());
        $previousTokenHash = Cache::get($userKey);

        if (is_string($previousTokenHash) && $previousTokenHash !== '') {
            Cache::forget($this->tokenKey($previousTokenHash));
        }

        Cache::put($this->tokenKey($tokenHash), [
            'user_id' => $user->getAuthIdentifier(),
            'email_fingerprint' => $this->emailFingerprint($email),
            'redirect_to' => $this->safeRedirectPath($redirectTo),
            'locale' => $this->validLocale($locale),
        ], $expiresAt);
        Cache::put($userKey, $tokenHash, $expiresAt);

        $url = URL::temporarySignedRoute(
            $this->routeName($locale),
            $expiresAt,
            array_filter([
                'locale' => $this->validLocale($locale),
                'token' => $token,
            ], fn (mixed $value): bool => $value !== null),
        );

        $user->notify((new PasswordlessLoginNotification($url, $this->expiresInMinutes()))->locale($locale));

        return $url;
    }

    public function invalidateFor(Authenticatable $user): void
    {
        $userKey = $this->userKey($user->getAuthIdentifier());
        $tokenHash = Cache::pull($userKey);

        if (is_string($tokenHash) && $tokenHash !== '') {
            Cache::forget($this->tokenKey($tokenHash));
        }
    }

    private function expiresInMinutes(): int
    {
        return max(1, min(60, (int) config('devdojo.auth.settings.passwordless_login_expires_minutes', 10)));
    }

    private function lockKey(string $tokenHash): string
    {
        return 'devdojo-auth:passwordless:lock:'.$tokenHash;
    }

    private function routeName(?string $locale): string
    {
        return $this->validLocale($locale) === null
            ? 'auth.passwordless.login'
            : 'auth.passwordless.login.localized';
    }

    private function safeRedirectPath(?string $redirectTo): ?string
    {
        if (! is_string($redirectTo) || $redirectTo === '') {
            return null;
        }

        $parts = parse_url($redirectTo);

        if ($parts === false) {
            return null;
        }

        $host = $parts['host'] ?? null;
        $applicationHost = parse_url((string) config('app.url'), PHP_URL_HOST);

        if (is_string($host) && $host !== '' && $host !== $applicationHost) {
            return null;
        }

        $path = '/'.ltrim((string) ($parts['path'] ?? '/'), '/');

        if (str_starts_with($path, '//') || $path === '/auth/logout') {
            return null;
        }

        return $path.(isset($parts['query']) ? '?'.$parts['query'] : '');
    }

    private function tokenHash(string $token): string
    {
        return hash('sha256', $token);
    }

    private function tokenKey(string $tokenHash): string
    {
        return 'devdojo-auth:passwordless:token:'.$tokenHash;
    }

    private function userKey(int|string $userId): string
    {
        return 'devdojo-auth:passwordless:user:'.$userId;
    }

    private function validLocale(?string $locale): ?string
    {
        $locales = config('app.locales', []);

        return is_string($locale) && in_array($locale, $locales, true) ? $locale : null;
    }

    private function notificationLocale(Authenticatable $user, ?string $fallbackLocale): ?string
    {
        if ($user instanceof HasLocalePreference) {
            $preferredLocale = $this->validLocale($user->preferredLocale());

            if ($preferredLocale !== null) {
                return $preferredLocale;
            }
        }

        return $this->validLocale($fallbackLocale);
    }
}
