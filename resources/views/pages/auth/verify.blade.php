<?php

use Devdojo\Auth\Helper;
use Devdojo\Auth\Traits\HasConfigs;
use Devdojo\Auth\Traits\ValidatesTurnstile;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Livewire\Volt\Component;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

middleware(['auth', 'throttle:6,1']);
name('verification.notice');

new class() extends Component
{
    use HasConfigs;
    use ValidatesTurnstile;

    public int $retryAfter = 0;

    public function mount()
    {
        $this->loadConfigs();
        $this->retryAfter = $this->verificationRetryAfter(auth()->user());
    }

    public function resend()
    {
        $user = auth()->user();
        abort_unless($user instanceof MustVerifyEmail, 403);

        if ($user->hasVerifiedEmail()) {
            return redirect(Helper::localizedUrl('/'));
        }

        $this->retryAfter = $this->verificationRetryAfter($user);

        if ($this->retryAfter > 0) {
            $this->addError('verification', __('auth.verify.resend_rate_limited'));

            return;
        }

        $this->validateTurnstile('auth_verification_resend');

        $sent = method_exists($user, 'resendEmailVerificationNotification')
            ? $user->resendEmailVerificationNotification()
            : $this->sendDefaultVerification($user);

        $this->resetTurnstile();
        $this->retryAfter = $this->verificationRetryAfter($user);
        $this->dispatch('verification-resend-cooldown', retryAfter: $this->retryAfter);

        if (! $sent) {
            $this->addError('verification', __('auth.verify.resend_rate_limited'));

            return;
        }

        $this->dispatch('resent');
        session()->flash('resent');

    }

    private function verificationRetryAfter(?object $user): int
    {
        return is_object($user) && method_exists($user, 'verificationEmailRetryAfter')
            ? max(0, (int) $user->verificationEmailRetryAfter())
            : 0;
    }

    private function sendDefaultVerification(MustVerifyEmail $user): bool
    {
        $user->sendEmailVerificationNotification();

        return true;
    }
};

?>

<x-auth::layouts.app title="{{ __('auth.verify.page_title') }}">

    @volt('auth.verify')
        <x-auth::elements.container>

            <x-auth::elements.heading
                :text="$language->verify->headline"
                :description="$language->verify->subheadline"
                :show_subheadline="($language->verify->show_subheadline ?? false)" />


                @if (session('resent'))
                    <div class="flex items-start px-4 py-3 mb-5 text-sm text-white bg-green-500 rounded-sm shadow-sm" role="alert">
                        <svg class="mr-2 w-5 h-5 fill-current" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"></path>
                        </svg>

                        <p>{{ $language->verify->new_link_sent }}</p>
                    </div>
                @endif

                @error('verification')
                    <p class="mb-4 text-sm font-medium text-orange-600 dark:text-orange-400" role="alert">{{ $message }}</p>
                @enderror

                <div class="text-sm leading-6 text-gray-700 dark:text-neutral-400 mb-4">
                    <p>
                        {{ $language->verify->description }}
                        <button
                            type="button"
                            wire:click="resend"
                            data-auth="verify-email-resend-link"
                            data-verification-resend
                            data-retry-after="{{ $retryAfter }}"
                            data-ready-label="{{ $language->verify->new_request_link }}"
                            data-waiting-label="{{ $language->verify->resend_in ?? __('auth.verify.resend_in') }}"
                            @disabled($retryAfter > 0)
                            class="text-gray-700 underline transition duration-150 ease-in-out cursor-pointer disabled:cursor-not-allowed disabled:text-gray-400 disabled:no-underline dark:text-neutral-300 hover:text-gray-600 dark:hover:text-neutral-200 dark:disabled:text-neutral-600 focus:outline-hidden focus:underline"
                        >
                            <span data-verification-resend-label>{{ $language->verify->new_request_link }}</span>
                        </button>
                    </p>
                </div>

                <x-auth::elements.turnstile action="auth_verification_resend" />



            <div class="mt-2 space-x-0.5 text-sm leading-5 text-center text-gray-600 translate-y-4 dark:text-neutral-400">
                <span>{{ $language->verify->or }}</span>
                <button onclick="event.preventDefault(); document.getElementById('logout-form').submit();" class="text-gray-500 underline cursor-pointer dark:text-neutral-400 dark:hover:text-neutral-300 hover:text-gray-800">
                  {{ $language->verify->logout }}
                </button>
                <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                    @csrf
                </form>
            </div>

        </x-auth::elements.container>
    @endvolt

</x-auth::layouts.app>
