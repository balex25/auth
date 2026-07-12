<?php

namespace Devdojo\Auth\Http\Controllers;

use Devdojo\Auth\Helper;
use Illuminate\Auth\Events\Verified;
use Illuminate\Foundation\Auth\EmailVerificationRequest;
use Illuminate\Http\RedirectResponse;

class VerifyEmailController
{
    /**
     * Mark the authenticated user's email address as verified.
     */
    public function __invoke(EmailVerificationRequest $request): RedirectResponse
    {
        if ($request->user()->hasVerifiedEmail()) {
            return Helper::intendedRedirect(self::verifiedRedirectTarget());
        }

        if ($request->user()->markEmailAsVerified()) {
            event(new Verified($request->user()));
        }

        return Helper::intendedRedirect(self::verifiedRedirectTarget());
    }

    private static function verifiedRedirectTarget(): string
    {
        $target = Helper::localizedRedirectTarget(config('devdojo.auth.settings.redirect_after_auth'));
        $separator = str_contains($target, '?') ? '&' : '?';

        return $target.$separator.'verified=1';
    }
}
