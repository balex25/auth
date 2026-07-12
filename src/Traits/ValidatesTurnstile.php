<?php

namespace Devdojo\Auth\Traits;

use Illuminate\Support\Facades\Http;
use Illuminate\Validation\ValidationException;
use Throwable;

trait ValidatesTurnstile
{
    public ?string $turnstileToken = null;

    protected function validateTurnstile(string $action): void
    {
        if (! config('services.turnstile.site_key')) {
            return;
        }

        if (! is_string($this->turnstileToken) || $this->turnstileToken === '' || mb_strlen($this->turnstileToken) > 2048) {
            $this->rejectTurnstile(__('global.validation.turnstile_required'));
        }

        $secret = config('services.turnstile.secret');

        if (! is_string($secret) || $secret === '') {
            $this->rejectTurnstile(__('global.validation.turnstile_misconfigured'));
        }

        try {
            $result = Http::asForm()
                ->connectTimeout(3)
                ->timeout(8)
                ->post('https://challenges.cloudflare.com/turnstile/v0/siteverify', [
                    'secret' => $secret,
                    'response' => $this->turnstileToken,
                    'remoteip' => request()->ip(),
                ])
                ->json();
        } catch (Throwable) {
            $this->rejectTurnstile(__('global.validation.turnstile_failed_retry'));
        }

        $validAction = ! isset($result['action']) || $result['action'] === $action;

        if (($result['success'] ?? false) !== true || ! $validAction) {
            $this->rejectTurnstile(__('global.validation.turnstile_failed_retry'));
        }

        $this->turnstileToken = null;
        $this->dispatch('auth-turnstile-reset');
    }

    private function rejectTurnstile(string $message): never
    {
        $this->turnstileToken = null;
        $this->dispatch('auth-turnstile-reset');

        throw ValidationException::withMessages([
            'turnstileToken' => $message,
        ]);
    }
}
