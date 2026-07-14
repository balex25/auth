<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}">
<head>
    @include('auth::includes.theme')
    @include('auth::includes.head')
</head>
<body id="auth-body" class="overflow-hidden relative w-screen h-screen" style="background-color:{{ config('devdojo.auth.appearance.background.color') }}">
    @php
        $dyanicPageId = str_replace('/', '-', str_replace('.', '', Request::path()));
        $currentLocale = \Devdojo\Auth\Helper::currentLocale() ?? app()->getLocale();
        $locales = array_values(array_filter(config('app.locales', ['en'])));
        $localeNames = [
            'en' => 'English',
            'es' => 'Español',
            'ru' => 'Русский',
        ];
        $flagCodes = [
            'en' => 'us',
            'es' => 'es',
            'ru' => 'ru',
        ];
        $pathSegments = array_values(array_filter(explode('/', trim(request()->path(), '/'))));

        if (isset($pathSegments[0]) && in_array($pathSegments[0], $locales, true)) {
            array_shift($pathSegments);
        }

        $localePathSuffix = $pathSegments === [] ? '' : '/'.implode('/', $pathSegments);
    @endphp
    <div x-data data-auth="{{ $dyanicPageId }}" class="relative w-full h-full" x-cloak>

        <div class="hidden sm:flex fixed bottom-5 right-5 z-40 items-center gap-2" data-auth-footer-controls>
            <a href="{{ \Devdojo\Auth\Helper::localizedUrl('/') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-white/20 bg-white/10 px-2.5 py-1.5 text-xs font-medium text-white shadow-md backdrop-blur-md transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70 active:scale-95">
                <svg class="mr-1 -ml-1 size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>{{ __('auth.layout.back_to_website') }}</span>
            </a>

            <div
                class="relative"
                data-auth-language-switcher
                x-data="{
                    open: false,
                    locales: @js($locales),
                    localeUrl(locale) {
                        const url = new URL(window.location.href);
                        const segments = url.pathname.replace(/^\/+|\/+$/g, '').split('/').filter(Boolean);

                        if (segments.length && this.locales.includes(segments[0])) {
                            segments.shift();
                        }

                        url.pathname = '/' + [locale, ...segments].join('/');

                        return url.pathname + url.search + url.hash;
                    }
                }"
                x-on:keydown.escape.window="open = false"
            >
                <button type="button" class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs font-semibold text-white shadow-md backdrop-blur-md transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="menu" aria-label="{{ __('header.dropdown.language') }}">
                    <svg class="size-4" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m5 8 6 6"/><path d="m4 14 6-6 2-3"/><path d="M2 5h12"/><path d="M7 2h1"/><path d="m22 22-5-10-5 10"/><path d="M14 18h6"/></svg>
                    <span>{{ strtoupper($currentLocale) }}</span>
                    <svg class="size-3.5 transition" x-bind:class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
                </button>
                <div x-cloak x-show="open" x-on:click.outside="open = false" x-transition.origin.bottom.duration.150ms class="absolute right-0 bottom-full z-20 mb-2 w-max min-w-full max-w-[calc(100vw-2rem)] space-y-0.5 overflow-hidden rounded-xl bg-white p-1 shadow-[0_10px_40px_10px_rgba(0,0,0,0.08)] dark:bg-neutral-900 dark:shadow-[0_10px_40px_10px_rgba(0,0,0,0.2)]" role="menu">
                    @foreach ($locales as $locale)
                        @php
                            $localeLabel = $localeNames[$locale] ?? strtoupper($locale);
                            $flagCode = $flagCodes[$locale] ?? $locale;
                            $localeHref = url('/'.$locale.$localePathSuffix);
                        @endphp
                        <a href="{{ $localeHref }}" x-bind:href="localeUrl(@js($locale))" data-locale="{{ $locale }}" class="flex min-w-0 items-center gap-2 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-normal text-gray-800 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-none active:scale-95 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800 @if ($locale === $currentLocale) bg-gray-100 dark:bg-neutral-800 @endif" role="menuitem">
                            <img class="size-4 shrink-0 rounded-full bg-white outline-1 outline-white/50" src="{{ asset('images/flags/1x1/'.$flagCode.'.svg') }}" alt="{{ $localeLabel }}" loading="lazy">
                            <span class="min-w-0 flex-1 truncate">{{ $localeLabel }}</span>
                            @if ($locale === $currentLocale)
                                <svg class="size-3.5 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                            @endif
                        </a>
                    @endforeach
                </div>
            </div>

            <div
                class="relative"
                data-auth-theme-switcher
                x-data="{
                    open: false,
                    theme: ['light', 'dark', 'system'].includes(new URLSearchParams(window.location.search).get('theme'))
                        ? new URLSearchParams(window.location.search).get('theme')
                        : (localStorage.getItem('theme') || 'system'),
                    setTheme(value) {
                        this.theme = value;
                        this.open = false;
                        window.dispatchEvent(new CustomEvent('theme-change', { detail: value }));
                    }
                }"
                x-init="window.addEventListener('theme-synced', (event) => { theme = event.detail; })"
                x-on:keydown.escape.window="open = false"
            >
                <button type="button" class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs font-semibold text-white shadow-md backdrop-blur-md transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="menu" aria-label="{{ __('header.dropdown.dark_mode') }}">
                    <svg class="size-4" x-show="theme === 'light'" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                    <svg class="size-4" x-show="theme === 'dark'" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                    <svg class="size-4" x-show="theme === 'system'" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                    <svg class="size-3.5 transition" x-bind:class="open ? 'rotate-180' : ''" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m18 15-6-6-6 6"/></svg>
                </button>
                <div x-cloak x-show="open" x-on:click.outside="open = false" x-transition.origin.bottom.duration.150ms class="absolute right-0 bottom-full z-20 mb-2 w-max min-w-full max-w-[calc(100vw-2rem)] space-y-0.5 overflow-hidden rounded-xl bg-white p-1 shadow-[0_10px_40px_10px_rgba(0,0,0,0.08)] dark:bg-neutral-900 dark:shadow-[0_10px_40px_10px_rgba(0,0,0,0.2)]" role="menu">
                    <button type="button" class="flex w-full items-center gap-2 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-normal text-gray-800 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-none active:scale-95 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800" x-bind:class="theme === 'light' ? 'bg-gray-100 dark:bg-neutral-800' : ''" x-on:click="setTheme('light')" role="menuitem">
                        <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="4"/><path d="M12 2v2"/><path d="M12 20v2"/><path d="m4.93 4.93 1.41 1.41"/><path d="m17.66 17.66 1.41 1.41"/><path d="M2 12h2"/><path d="M20 12h2"/><path d="m6.34 17.66-1.41 1.41"/><path d="m19.07 4.93-1.41 1.41"/></svg>
                        <span class="flex-1 text-left">{{ __('header.dropdown.mode.light') }}</span>
                        <svg class="size-3.5 shrink-0" x-show="theme === 'light'" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button type="button" class="flex w-full items-center gap-2 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-normal text-gray-800 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-none active:scale-95 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800" x-bind:class="theme === 'system' ? 'bg-gray-100 dark:bg-neutral-800' : ''" x-on:click="setTheme('system')" role="menuitem">
                        <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect width="20" height="14" x="2" y="3" rx="2"/><line x1="8" x2="16" y1="21" y2="21"/><line x1="12" x2="12" y1="17" y2="21"/></svg>
                        <span class="flex-1 text-left">{{ __('global.common.system') }}</span>
                        <svg class="size-3.5 shrink-0" x-show="theme === 'system'" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                    <button type="button" class="flex w-full items-center gap-2 whitespace-nowrap rounded-lg px-2 py-1.5 text-sm font-normal text-gray-800 transition hover:bg-gray-100 focus:bg-gray-100 focus:outline-none active:scale-95 dark:text-neutral-300 dark:hover:bg-neutral-800 dark:focus:bg-neutral-800" x-bind:class="theme === 'dark' ? 'bg-gray-100 dark:bg-neutral-800' : ''" x-on:click="setTheme('dark')" role="menuitem">
                        <svg class="size-4 shrink-0" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M12 3a6 6 0 0 0 9 9 9 9 0 1 1-9-9Z"/></svg>
                        <span class="flex-1 text-left">{{ __('header.dropdown.mode.dark') }}</span>
                        <svg class="size-3.5 shrink-0" x-show="theme === 'dark'" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                    </button>
                </div>
            </div>
        </div>

        @if(config('devdojo.auth.appearance.background.image'))
            <img src="{{ config('devdojo.auth.appearance.background.image') }}" id="auth-background-image" class="blur-md object-cover object-[bottom_center] absolute z-10 w-screen h-screen" />
            <div id="auth-background-image-overlay" class="absolute inset-0 z-20 w-screen h-screen blur-md"></div>
        @endif

        @php
            $slotParentClasses = match(config('devdojo.auth.appearance.alignment.container')){
                'left' => 'items-start h-screen',
                'center' => 'items-stretch sm:items-center sm:py-10',
                'right' => 'items-end h-screen',
            };
        @endphp

        <main id="auth-main-content" class="flex relative z-30 flex-col justify-center w-screen min-h-screen {{ $slotParentClasses }}">
            {{ $slot }} 
        </main>

        @if(config('devdojo.auth.settings.enable_branding') && !app()->isLocal())
            <a href="https://devdojo.com/auth?utm_source=branding" target="_blank" class="flex fixed bottom-0 left-1/2 z-30 justify-center items-center px-2.5 py-1.5 w-auto text-xs font-medium rounded-t-lg border -translate-x-1/2 cursor-pointer bg-zinc-900 text-white/80 hover:text-white border-zinc-800">
                <svg class="mr-1 -ml-1 w-5 h-5" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 151 201" fill="none"><path fill="currentColor" fill-rule="evenodd" d="M75.847.132c-28.092 23.884-45.7 25-75 25v96.125c0 15.285 4.238 26.069 12.393 35.442l17.526-33.718.345-.661 5.06-9.74L76.496 35l40.323 77.58c20.95 2.616 30.894 8.93 30.894 8.93a219.818 219.818 0 0 0-24.117 1.321l-1.371.15c-1.345.158-2.69.326-4.017.502a227.52 227.52 0 0 0-41.712 9.705C50.36 141.907 30.44 153.7 18.4 161.993c9.303 8.615 22.183 16.475 38.353 26.344 5.927 3.616 12.296 7.503 19.093 11.795 6.796-4.292 13.165-8.179 19.091-11.795 16.494-10.066 29.564-18.042 38.907-26.861a205.398 205.398 0 0 0-35.223-19.64 225.71 225.71 0 0 1 30.106-6.358l10.533 20.272c7.627-9.153 11.586-19.721 11.586-34.493V25.132c-29.3 0-46.909-1.117-75-25Zm.649 112.615c-6.892.793-14.306 1.973-22.26 3.655l2.566-4.923 19.694-37.896 19.693 37.896c-6.582.089-13.155.513-19.693 1.268Z" clip-rule="evenodd"/></svg>
                <p>Secured by DevDojo</p>
            </a>
        @endif
    </div>
</body>
</html>
