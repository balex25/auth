@props(['action'])

@php
    $turnstileSiteKey = config('services.turnstile.site_key');
    $turnstileLanguage = strtolower(str_replace('_', '-', \Devdojo\Auth\Helper::currentLocale() ?? 'auto'));
@endphp

@if ($turnstileSiteKey)
    <div
        x-data="{
            siteKey: @js($turnstileSiteKey),
            action: @js($action),
            language: @js($turnstileLanguage),
            widgetId: null,
            scriptRequested: false,
            pendingSubmit: false,
            pendingButton: null,
            form: null,
            init() {
                const load = () => this.loadTurnstile();
                window.addEventListener('pointerdown', load, { once: true, passive: true });
                window.addEventListener('keydown', load, { once: true });

                this.form = this.$el.closest('form');
                this.form?.addEventListener('submit', event => {
                    if (event.submitter?.matches('[data-auth-turnstile-bypass]')) return;
                    if (this.hasToken()) return;

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.pendingSubmit = true;
                    this.loadTurnstile();
                }, true);

                this.form?.addEventListener('click', event => {
                    const button = event.target.closest('[data-auth-turnstile-submit]');
                    if (!button || !this.form.contains(button) || this.hasToken()) return;

                    event.preventDefault();
                    event.stopImmediatePropagation();
                    this.pendingButton = button;
                    this.loadTurnstile();
                }, true);
            },
            hasToken() {
                const token = this.$refs.token.value
                    || this.$refs.widget.querySelector('[name=cf-turnstile-response]')?.value
                    || '';

                if (!token) return false;

                this.pendingSubmit = false;
                this.pendingButton = null;

                if (this.$refs.token.value !== token) {
                    this.$refs.token.value = token;
                    this.$refs.token.dispatchEvent(new Event('input', { bubbles: true }));
                }

                return true;
            },
            loadTurnstile() {
                if (this.scriptRequested) return;
                this.scriptRequested = true;

                if (window.turnstile) {
                    this.renderTurnstile();
                    return;
                }

                let script = document.querySelector('script[data-auth-turnstile]');
                if (!script) {
                    script = document.createElement('script');
                    script.src = 'https://challenges.cloudflare.com/turnstile/v0/api.js?render=explicit';
                    script.async = true;
                    script.defer = true;
                    script.dataset.authTurnstile = 'true';
                    document.head.appendChild(script);
                }

                if (script.dataset.loaded === 'true') {
                    this.renderTurnstile();
                    return;
                }

                script.addEventListener('load', () => {
                    script.dataset.loaded = 'true';
                    this.renderTurnstile();
                }, { once: true });

                script.addEventListener('error', () => {
                    this.scriptRequested = false;
                }, { once: true });
            },
            renderTurnstile() {
                if (!window.turnstile || this.widgetId !== null) return;

                this.widgetId = window.turnstile.render(this.$refs.widget, {
                    sitekey: this.siteKey,
                    action: this.action,
                    appearance: 'always',
                    theme: document.documentElement.classList.contains('dark') ? 'dark' : 'light',
                    language: this.language,
                    callback: token => this.setToken(token),
                    'expired-callback': () => this.setToken(''),
                    'error-callback': () => this.setToken(''),
                });
            },
            setToken(token) {
                this.$refs.token.value = token;
                this.$refs.token.dispatchEvent(new Event('input', { bubbles: true }));

                if (token && this.pendingButton) {
                    const button = this.pendingButton;
                    this.pendingButton = null;
                    queueMicrotask(() => button.click());
                    return;
                }

                if (token && this.pendingSubmit && this.form) {
                    this.pendingSubmit = false;
                    queueMicrotask(() => this.form.requestSubmit());
                }
            },
            reset() {
                this.setToken('');
                if (this.widgetId !== null && window.turnstile) window.turnstile.reset(this.widgetId);
            },
        }"
        x-on:auth-turnstile-reset.window="reset()"
        class="space-y-2"
    >
        <div x-ref="widget" wire:ignore class="flex justify-center"></div>
        <input x-ref="token" type="hidden" wire:model.defer="turnstileToken">
        @error('turnstileToken')
            <p class="text-sm text-red-600 dark:text-red-400">{{ $message }}</p>
        @enderror
    </div>
@endif
