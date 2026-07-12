<?php

use Devdojo\Auth\Traits\HasConfigs;
use Devdojo\Auth\Traits\ValidatesTurnstile;
use Illuminate\Support\Facades\Password;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;

use function Laravel\Folio\name;

name('auth.password.request');

new class() extends Component
{
    use HasConfigs;
    use ValidatesTurnstile;

    #[Validate('required|email')]
    public $email = null;

    public $emailSentMessage = false;

    public function mount()
    {
        $this->loadConfigs();
    }

    public function sendResetPasswordLink()
    {
        $this->validate();
        $this->validateTurnstile('auth_password_reset_request');

        $response = Password::broker()->sendResetLink(['email' => $this->email]);

        if ($response == Password::RESET_LINK_SENT) {
            $this->emailSentMessage = trans($response);

            return;
        }

        $this->addError('email', trans($response));
    }
};

?>

<x-auth::layouts.app title="{{ __('auth.passwordResetRequest.page_title') }}">

    @volt('auth.password.reset')
        <x-auth::elements.container>

        <x-auth::elements.heading
            :text="$language->passwordResetRequest->headline"
            :description="$language->passwordResetRequest->subheadline"
            :show_subheadline="($language->passwordResetRequest->show_subheadline ?? false)"
        />

        @if ($emailSentMessage)
            <div class="p-4 mb-2 bg-green-50 rounded-md dark:bg-green-600">
                <div class="flex">
                    <div class="shrink-0">
                        <svg class="w-5 h-5 text-green-400 dark:text-white" fill="currentColor" viewBox="0 0 20 20">
                            <path fill-rule="evenodd" d="M10 18a8 8 0 100-16 8 8 0 000 16zm3.707-9.293a1 1 0 00-1.414-1.414L9 10.586 7.707 9.293a1 1 0 00-1.414 1.414l2 2a1 1 0 001.414 0l4-4z" clip-rule="evenodd"/>
                        </svg>
                    </div>

                    <div class="ml-3">
                        <p class="text-sm font-medium leading-5 text-green-800 dark:text-green-200">
                            {{ $emailSentMessage }}
                        </p>
                    </div>
                </div>
            </div>
        @else
            <form wire:submit="sendResetPasswordLink" class="space-y-5">
                <x-auth::elements.input :label="$language->passwordResetRequest->email" type="email" id="email" name="email" data-auth="email-input" wire:model="email" autofocus="true" autocomplete="email" />
                <x-auth::elements.turnstile action="auth_password_reset_request" />
                <x-auth::elements.button type="primary" data-auth="submit-button" rounded="md" submit="true">{{ $language->passwordResetRequest->button }}</x-auth::elements.button>
            </form>
        @endif
        <div class="mt-3 space-x-0.5 text-sm leading-5 text-center">
            <span class="opacity-47 text-white">{{ $language->passwordResetRequest->or }}</span>
            <x-auth::elements.text-link data-auth="login-link" href="{{ \Devdojo\Auth\Helper::authUrl('auth.login') }}">{{ $language->passwordResetRequest->return_to_login }}</x-auth::elements.text-link>
        </div>
    </x-auth::elements.container>
    @endvolt

</x-auth::layouts.app>
