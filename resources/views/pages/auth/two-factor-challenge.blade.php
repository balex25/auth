<?php

use App\Models\User;
use Devdojo\Auth\Helper;
use Devdojo\Auth\Traits\HasConfigs;
use Devdojo\Auth\Traits\ValidatesTurnstile;
use Illuminate\Auth\Events\Login;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\On;
use Livewire\Attributes\Validate;
use Livewire\Volt\Component;
use PragmaRX\Google2FA\Google2FA;

use function Laravel\Folio\middleware;
use function Laravel\Folio\name;

if (! isset($_GET['preview']) || (isset($_GET['preview']) && $_GET['preview'] != true) || ! app()->isLocal()) {
    middleware(['two-factor-challenged', 'throttle:5,1']);
}

name('auth.two-factor-challenge');

new class() extends Component
{
    use HasConfigs;
    use ValidatesTurnstile;

    public $recovery = false;

    public $google2fa;

    #[Validate('required|min:6')]
    public $auth_code;

    public $recovery_code;

    public function mount()
    {
        $this->loadConfigs();
        $this->recovery = false;
    }

    public function switchToRecovery()
    {
        $this->recovery = ! $this->recovery;
        if ($this->recovery) {
            $this->js("setTimeout(function(){ window.dispatchEvent(new CustomEvent('focus-auth-2fa-recovery-code', {})); }, 10);");
        } else {
            $this->js("setTimeout(function(){ window.dispatchEvent(new CustomEvent('focus-auth-2fa-auth-code', {})); }, 10);");
        }

    }

    #[On('submitCode')]
    public function submitCode($code)
    {
        $this->auth_code = $code;
        $this->validate();
        $this->validateTurnstile('auth_two_factor');

        $user = User::find(session()->get('login.id'));
        $secret = decrypt($user->two_factor_secret);
        $google2fa = new Google2FA();
        $valid = $google2fa->verifyKey($secret, $code);

        if ($valid) {
            $this->loginUser($user);
        } else {
            $this->addError('auth_code', __('auth.twoFactorChallenge.invalid_auth_code'));
            $this->resetTurnstile();
        }

    }

    public function submit_recovery_code()
    {
        $this->validateOnly('recovery_code');
        $this->validateTurnstile('auth_two_factor');

        $user = User::find(session()->get('login.id'));
        $valid = in_array($this->recovery_code, json_decode(decrypt($user->two_factor_recovery_codes)));

        if ($valid) {
            return $this->loginUser($user);
        } else {
            $this->addError('recovery_code', __('auth.twoFactorChallenge.invalid_recovery_code'));
            $this->resetTurnstile();
        }
    }

    public function loginUser($user)
    {
        Auth::login($user);

        // clear out the session that is used to determine if the user can visit the 2fa challenge page.
        session()->forget('login.id');

        event(new Login(auth()->guard('web'), $user, true));

        return $this->redirectAfterAuth();
    }

    protected function redirectAfterAuth()
    {
        if (session()->get('url.intended') != Helper::authUrl('logout.get')) {
            return Helper::intendedRedirect(config('devdojo.auth.settings.redirect_after_auth'));
        }

        return redirect(Helper::localizedRedirectTarget(config('devdojo.auth.settings.redirect_after_auth')));
    }
}

?>

<x-auth::layouts.app title="{{ __('auth.twoFactorChallenge.page_title') }}">
    @volt('auth.two-factor-challenge')
        <x-auth::elements.container>
            <div x-data x-on:code-input-complete.window="console.log(event); $dispatch('submitCode', [event.detail.code])" class="relative w-full h-auto">
                @if(!$recovery)
                    <x-auth::elements.heading 
                        :text="$language->twoFactorChallenge->headline_auth"
                        :description="$language->twoFactorChallenge->subheadline_auth"
                        :show_subheadline="($language->twoFactorChallenge->show_subheadline_auth ?? false)" />
                @else
                    <x-auth::elements.heading 
                        :text="$language->twoFactorChallenge->headline_recovery"
                        :description="$language->twoFactorChallenge->subheadline_recovery"
                        :show_subheadline="($language->twoFactorChallenge->show_subheadline_recovery ?? false)" />
                @endif

                <div class="space-y-5">

                    @if(!$recovery)
                        <div class="relative">
                            <x-auth::elements.input-code wire:model="auth_code" id="auth-input-code" digits="6" eventCallback="code-input-complete" type="text" :label="$language->twoFactorChallenge->code" />
                        </div>
                        @error('auth_code')
                            <p class="my-2 text-sm text-red-600">{{ $message }}</p>
                        @enderror
                        <x-auth::elements.turnstile action="auth_two_factor" />
                        <x-auth::elements.button rounded="md" submit="true" wire:click="submitCode(document.getElementById('auth-input-code').value)">{{ $language->twoFactorChallenge->button }}</x-auth::elements.button>
                    @else
                        <div class="relative">
                            <x-auth::elements.input :label="$language->twoFactorChallenge->recovery_code" type="text" wire:keydown.enter="submit_recovery_code" wire:model="recovery_code" id="auth-2fa-recovery-code" required />
                        </div>
                        <x-auth::elements.turnstile action="auth_two_factor" />
                        <x-auth::elements.button rounded="md" submit="true" wire:click="submit_recovery_code">{{ $language->twoFactorChallenge->button }}</x-auth::elements.button>
                    @endif

                    
                </div>

                <div class="mt-5 space-x-0.5 text-sm leading-5 text-left">
                    <span class="opacity-47 text-white">{{ $language->twoFactorChallenge->or_you_can }} </span>
                    <span class="font-semibold cursor-pointer text-orange-600 hover:text-orange-700 dark:text-orange-500 dark:hover:text-orange-600 no-underline hover:underline transition-all" wire:click="switchToRecovery" href="#_">
                        @if(!$recovery)
                            <span>{{ $language->twoFactorChallenge->login_using_recovery_code }}</span>
                        @else
                            <span>{{ $language->twoFactorChallenge->login_using_auth_code }}</span>
                        @endif
                    </span>
                </div>
            </div>
        </x-auth::elements.container>
    @endvolt
</x-auth::layouts.app>
