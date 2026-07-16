@props([
    'autoSubmit' => true,
    'formAction' => null,
])

<x-auth::elements.container>
    <x-auth::elements.heading
        :text="__('auth.passwordless.headline')"
        :description="__('auth.passwordless.subheadline')"
        :show_subheadline="true"
    />

    <form id="passwordless-login-form" method="POST" action="{{ $formAction ?? request()->fullUrl() }}" class="space-y-5">
        @csrf

        <x-auth::elements.button
            type="primary"
            rounded="md"
            size="md"
            submit="true"
            disabled
            aria-disabled="true"
        >
            {{ __('auth.passwordless.confirm_button') }}
        </x-auth::elements.button>
    </form>

    @if($autoSubmit)
        <script>
            (() => {
                const form = document.getElementById('passwordless-login-form');

                if (!form) {
                    return;
                }

                const submit = () => window.requestAnimationFrame(() => form.submit());

                if (window.Livewire) {
                    submit();
                    return;
                }

                document.addEventListener('livewire:initialized', submit, { once: true });
            })();
        </script>
    @endif

    <div class="mt-4 text-center text-sm">
        <x-auth::elements.text-link href="{{ \Devdojo\Auth\Helper::authUrl('auth.login') }}">
            {{ __('auth.passwordless.cancel') }}
        </x-auth::elements.text-link>
    </div>
</x-auth::elements.container>
