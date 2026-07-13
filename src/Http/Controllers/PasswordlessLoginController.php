<?php

namespace Devdojo\Auth\Http\Controllers;

use Devdojo\Auth\Helper;
use Devdojo\Auth\PasswordlessLoginManager;
use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class PasswordlessLoginController
{
    public function __invoke(Request $request, PasswordlessLoginManager $passwordlessLogins, string $token): View|RedirectResponse
    {
        abort_unless(config('devdojo.auth.settings.passwordless_login_enabled', false), 404);

        if ($request->isMethod('get')) {
            return view('auth::pages.auth.passwordless-login');
        }

        $payload = $passwordlessLogins->consume($token);

        if ($payload === null) {
            return $this->failedRedirect();
        }

        $user = $this->findUser($payload['user_id']);

        if (! $this->canUsePasswordlessLogin($user, $payload['email_fingerprint'], $passwordlessLogins)) {
            return $this->failedRedirect();
        }

        $redirectTo = $payload['redirect_to'] ?? config('devdojo.auth.settings.redirect_after_auth');

        if (config('devdojo.auth.settings.enable_2fa') && data_get($user, 'two_factor_confirmed_at') !== null) {
            $request->session()->put([
                'login.id' => $user->getAuthIdentifier(),
                'url.intended' => $redirectTo,
            ]);

            return redirect(Helper::authUrl('auth.two-factor-challenge'));
        }

        Auth::login($user);
        $request->session()->regenerate();

        return redirect(Helper::localizedRedirectTarget((string) $redirectTo));
    }

    private function canUsePasswordlessLogin(?Authenticatable $user, string $emailFingerprint, PasswordlessLoginManager $passwordlessLogins): bool
    {
        if (! $user instanceof Model || data_get($user, 'email_verified_at') === null) {
            return false;
        }

        $email = data_get($user, 'email');

        return is_string($email)
            && hash_equals($emailFingerprint, $passwordlessLogins->emailFingerprint($email));
    }

    private function failedRedirect(): RedirectResponse
    {
        return redirect(Helper::authUrl('auth.login'))
            ->with('error', __('auth.passwordless.invalid_or_expired'));
    }

    private function findUser(int|string $userId): ?Authenticatable
    {
        $userClass = config('auth.providers.users.model');

        if (! is_string($userClass) || ! is_a($userClass, Authenticatable::class, true)) {
            return null;
        }

        $user = $userClass::query()->find($userId);

        return $user instanceof Authenticatable ? $user : null;
    }
}
