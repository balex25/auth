<x-auth::layouts.app title="{{ __('auth.passwordless.page_title') }}">
    <x-auth::elements.container>
        <x-auth::elements.heading
            :text="__('auth.passwordless.headline')"
            :description="__('auth.passwordless.subheadline')"
            :show_subheadline="true"
        />

        <form id="passwordless-login-form" method="POST" action="{{ request()->fullUrl() }}" class="space-y-5">
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

        <script>
            (() => {
                const form = document.getElementById('passwordless-login-form');

                if (form) {
                    window.requestAnimationFrame(() => form.submit());
                }
            })();
        </script>

        <div class="mt-4 text-center text-sm">
            <x-auth::elements.text-link href="{{ \Devdojo\Auth\Helper::authUrl('auth.login') }}">
                {{ __('auth.passwordless.cancel') }}
            </x-auth::elements.text-link>
        </div>
    </x-auth::elements.container>
</x-auth::layouts.app>
