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
        $authBackgroundImages = collect($authBackgroundImages ?? [])
            ->filter(fn (mixed $image): bool => is_array($image) && filled($image['url'] ?? null))
            ->map(fn (array $image): array => [
                'url' => (string) $image['url'],
                'title' => (string) ($image['title'] ?? ''),
                'link' => filled($image['link'] ?? null) ? (string) $image['link'] : null,
                'author_name' => (string) ($image['author_name'] ?? ''),
                'author_url' => filled($image['author_url'] ?? null) ? (string) $image['author_url'] : null,
                'author_avatar' => (string) ($image['author_avatar'] ?? ''),
            ])
            ->values()
            ->all();
        $fallbackBackgroundImage = config('devdojo.auth.appearance.background.image');
        $initialBackgroundImage = filled($fallbackBackgroundImage)
            ? (string) $fallbackBackgroundImage
            : data_get($authBackgroundImages, '0.url');
    @endphp
    <div
        x-data="{
            backgroundImages: @js($authBackgroundImages),
            activeBackgroundIndex: 0,
            backgroundUrl: @js($initialBackgroundImage),
            nextBackgroundUrl: null,
            backgroundBlurred: true,
            backgroundTransitioning: false,
            backgroundMetaVisible: false,
            preloadedBackgrounds: {},
            failedBackgrounds: {},
            autoplayDuration: 5500,
            autoplayFrame: null,
            autoplayStartedAt: null,
            autoplayProgress: 0,
            prefersReducedMotion: window.matchMedia('(prefers-reduced-motion: reduce)').matches,
            currentBackground() {
                return this.backgroundImages[this.activeBackgroundIndex] || null;
            },
            initBackgroundGallery() {
                if (this.backgroundImages.length === 0) {
                    return;
                }

                this.activateBackground(0);
            },
            destroy() {
                if (this.autoplayFrame) {
                    window.cancelAnimationFrame(this.autoplayFrame);
                }
            },
            updateBackgroundBlur(event) {
                const authContainer = document.getElementById('auth-container-parent');

                if (!authContainer || window.innerWidth <= 848) {
                    this.backgroundBlurred = true;
                    return;
                }

                const rect = authContainer.getBoundingClientRect();
                this.backgroundBlurred = event.clientX >= rect.left
                    && event.clientX <= rect.right
                    && event.clientY >= rect.top
                    && event.clientY <= rect.bottom;
            },
            preloadBackground(index, onReady, onError = null) {
                const image = this.backgroundImages[index];

                if (!image) {
                    return;
                }

                if (this.preloadedBackgrounds[image.url]) {
                    onReady();
                    return;
                }

                const loader = new Image();
                const ready = () => {
                    this.preloadedBackgrounds[image.url] = true;
                    onReady();
                };

                loader.onload = () => {
                    if (typeof loader.decode !== 'function') {
                        ready();
                        return;
                    }

                    loader.decode().catch(() => {}).finally(ready);
                };
                loader.onerror = () => {
                    this.failedBackgrounds[image.url] = true;

                    if (onError) {
                        onError();
                    }
                };
                loader.src = image.url;
            },
            activateBackground(index) {
                this.preloadBackground(index, () => {
                    const image = this.backgroundImages[index];
                    const shouldCrossfade = !this.prefersReducedMotion
                        && this.backgroundUrl
                        && this.backgroundUrl !== image.url;
                    const finishSwap = () => {
                        this.activeBackgroundIndex = index;
                        this.backgroundUrl = image.url;
                        this.nextBackgroundUrl = null;
                        this.backgroundTransitioning = false;
                        this.backgroundMetaVisible = true;
                        this.preloadNextBackground();
                        this.startAutoplay();
                    };

                    this.stopAutoplay();

                    if (!shouldCrossfade) {
                        finishSwap();
                        return;
                    }

                    this.nextBackgroundUrl = image.url;
                    this.backgroundTransitioning = false;
                    this.$nextTick(() => {
                        window.requestAnimationFrame(() => {
                            this.activeBackgroundIndex = index;
                            this.backgroundTransitioning = true;
                            window.setTimeout(finishSwap, 500);
                        });
                    });
                }, () => {
                    const nextIndex = this.nextAvailableBackgroundIndex(index);

                    if (nextIndex !== null) {
                        this.activateBackground(nextIndex);
                    }
                });
            },
            nextAvailableBackgroundIndex(index) {
                for (let offset = 1; offset <= this.backgroundImages.length; offset++) {
                    const candidateIndex = (index + offset) % this.backgroundImages.length;
                    const candidate = this.backgroundImages[candidateIndex];

                    if (candidate && !this.failedBackgrounds[candidate.url]) {
                        return candidateIndex;
                    }
                }

                return null;
            },
            preloadNextBackground() {
                if (this.backgroundImages.length < 2) {
                    return;
                }

                const nextIndex = this.nextAvailableBackgroundIndex(this.activeBackgroundIndex);

                if (nextIndex !== null && nextIndex !== this.activeBackgroundIndex) {
                    this.preloadBackground(nextIndex, () => {});
                }
            },
            showNextBackground() {
                const nextIndex = this.nextAvailableBackgroundIndex(this.activeBackgroundIndex);

                if (nextIndex !== null && nextIndex !== this.activeBackgroundIndex) {
                    this.activateBackground(nextIndex);
                }
            },
            startAutoplay() {
                if (this.backgroundImages.length < 2 || this.prefersReducedMotion || this.autoplayFrame) {
                    return;
                }

                this.autoplayProgress = 0;
                this.autoplayStartedAt = performance.now();
                this.autoplayFrame = window.requestAnimationFrame(timestamp => this.tickAutoplay(timestamp));
            },
            stopAutoplay() {
                if (this.autoplayFrame) {
                    window.cancelAnimationFrame(this.autoplayFrame);
                }

                this.autoplayFrame = null;
                this.autoplayStartedAt = null;
            },
            tickAutoplay(timestamp) {
                if (this.autoplayStartedAt === null) {
                    this.autoplayStartedAt = timestamp;
                }

                this.autoplayProgress = Math.min((timestamp - this.autoplayStartedAt) / this.autoplayDuration, 1);

                if (this.autoplayProgress >= 1) {
                    this.autoplayFrame = null;
                    this.showNextBackground();
                    return;
                }

                this.autoplayFrame = window.requestAnimationFrame(nextTimestamp => this.tickAutoplay(nextTimestamp));
            }
        }"
        x-init="initBackgroundGallery()"
        x-on:pointermove.window="updateBackgroundBlur($event)"
        x-on:blur.window="backgroundBlurred = true"
        data-auth="{{ $dyanicPageId }}"
        class="relative w-full h-full"
        x-cloak
    >

        <div class="flex fixed bottom-5 right-5 z-40 items-center gap-2 max-[848px]:left-8" data-auth-footer-controls>
            <a href="{{ \Devdojo\Auth\Helper::localizedUrl('/') }}" class="inline-flex min-h-9 items-center justify-center rounded-lg border border-white/20 bg-white/10 px-2.5 py-1.5 text-xs font-medium text-white shadow-md backdrop-blur-md transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70 active:scale-95 max-[848px]:border-transparent max-[848px]:bg-transparent max-[848px]:hover:bg-white/10">
                <svg class="mr-1 -ml-1 size-5" fill="none" stroke="currentColor" stroke-width="2" viewBox="0 0 24 24" xmlns="http://www.w3.org/2000/svg" aria-hidden="true"><path stroke-linecap="round" stroke-linejoin="round" d="M10 19l-7-7m0 0l7-7m-7 7h18"></path></svg>
                <span>{{ __('auth.layout.back_to_website') }}</span>
            </a>

            <div
                class="relative max-[640px]:ml-auto"
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
                <button type="button" class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs font-semibold text-white shadow-md backdrop-blur-md transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70 max-[848px]:border-transparent max-[848px]:bg-transparent max-[848px]:hover:bg-white/10" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="menu" aria-label="{{ __('header.dropdown.language') }}">
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
                <button type="button" class="inline-flex min-h-9 cursor-pointer items-center gap-2 rounded-lg border border-white/20 bg-white/10 px-2.5 py-2 text-xs font-semibold text-white shadow-md backdrop-blur-md transition hover:bg-white/30 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500/70 max-[848px]:border-transparent max-[848px]:bg-transparent max-[848px]:hover:bg-white/10" x-on:click="open = !open" x-bind:aria-expanded="open.toString()" aria-haspopup="menu" aria-label="{{ __('header.dropdown.dark_mode') }}">
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

        @if(filled($initialBackgroundImage))
            <div id="auth-background-wrapper" class="absolute inset-0 z-10 overflow-hidden" aria-hidden="true">
                <img
                    src="{{ $initialBackgroundImage }}"
                    x-bind:src="backgroundUrl"
                    x-bind:class="backgroundBlurred ? 'blur-xs' : 'blur-0'"
                    id="auth-background-image"
                    alt=""
                    class="absolute inset-0 size-full object-cover object-[bottom_center] transition-[filter] duration-500 ease-out motion-reduce:transition-none"
                />
                <img
                    x-cloak
                    x-show="nextBackgroundUrl"
                    x-bind:src="nextBackgroundUrl"
                    x-bind:class="[
                        backgroundBlurred ? 'blur-xs' : 'blur-0',
                        backgroundTransitioning ? 'opacity-100' : 'opacity-0'
                    ]"
                    alt=""
                    class="absolute inset-0 size-full object-cover object-[bottom_center] transition-[filter,opacity] duration-500 ease-out motion-reduce:transition-none"
                />
                <div id="auth-background-image-overlay" class="pointer-events-none absolute inset-0 bg-black/5"></div>
            </div>
        @endif

        <div
            x-cloak
            x-show="backgroundMetaVisible && currentBackground()"
            x-transition.opacity.duration.200ms
            class="pointer-events-auto fixed top-0 right-0 z-40 box-border w-max max-w-[min(22rem,calc(100vw-1rem))] rounded-xl rounded-tl-none rounded-r-none bg-gray-50 p-0.5 pt-px pr-px [--auth-meta-bg:var(--color-gray-50)] max-[848px]:hidden dark:bg-neutral-900 dark:[--auth-meta-bg:var(--color-neutral-900)]"
            data-auth-background-meta
        >
            <span aria-hidden="true" class="pointer-events-none absolute -left-4 top-0 size-4 rounded-tr-xl shadow-[8px_-8px_0_8px_var(--auth-meta-bg)]"></span>
            <span aria-hidden="true" class="pointer-events-none absolute right-0 -bottom-4 size-4 rounded-tr-xl shadow-[8px_-8px_0_8px_var(--auth-meta-bg)]"></span>

            <div class="relative z-10 m-1 min-w-0 overflow-hidden rounded-lg border border-gray-200 bg-white px-3 py-2 pb-3 text-left text-gray-900 shadow-sm dark:border-neutral-700 dark:bg-neutral-800 dark:text-white">
                <a
                    x-show="currentBackground() && currentBackground().link"
                    x-bind:href="currentBackground() ? currentBackground().link : null"
                    target="_blank"
                    rel="noopener noreferrer"
                    class="inline-flex max-w-full items-center gap-1 whitespace-nowrap text-sm font-bold leading-tight transition-colors hover:text-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 sm:text-base dark:hover:text-orange-500"
                    data-auth-background-title-link
                >

                    <h3
                        class="inline-flex max-w-full min-w-0 items-center gap-1 overflow-hidden whitespace-nowrap text-sm font-bold leading-tight sm:text-base"
                        data-auth-background-linked-title
                    >
                        <span x-text="currentBackground() ? currentBackground().title : ''" class="shape h4 min-w-0 truncate"></span>
                        <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0 text-orange-600 dark:text-orange-500"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                    </h3>
                </a>
                <p class="mt-0.5 flex max-w-full min-w-0 flex-nowrap items-center gap-x-2 whitespace-nowrap text-[11px] font-semibold text-gray-600 sm:text-xs dark:text-neutral-300">

                    <a
                        x-show="currentBackground() && currentBackground().author_url"
                        x-bind:href="currentBackground() ? currentBackground().author_url : null"
                        target="_blank"
                        rel="noopener noreferrer"
                        class="group inline-flex min-w-0 items-center gap-1.5 text-gray-700 transition-colors hover:text-orange-600 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-500 dark:text-neutral-200 dark:hover:text-orange-500"
                        data-auth-background-author-link
                    >
                        <span class="inline-flex size-4 shrink-0 rounded-full bg-gray-100 ring-1 ring-gray-200 dark:bg-neutral-700 dark:ring-neutral-600 transition-transform duration-150 ease-linear group-hover:scale-110">
                            <img x-bind:src="currentBackground() ? currentBackground().author_avatar : ''" x-bind:alt="currentBackground() ? currentBackground().author_name : ''" class="size-full object-cover rounded-full overflow-hidden">
                        </span>

                        <span class="inline-flex min-w-0 items-center gap-1">
                            <span x-text="currentBackground() ? currentBackground().author_name : ''" class="max-w-40 truncate italic"></span>
                            <svg xmlns="http://www.w3.org/2000/svg" width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0 text-orange-600 dark:text-orange-500"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                        </span>
                    </a>

                    <span x-show="currentBackground() && !currentBackground().author_url" class="inline-flex min-w-0 items-center gap-1.5">
                        <span class="inline-flex size-4 shrink-0 rounded-full bg-gray-100 ring-1 ring-gray-200 dark:bg-neutral-700 dark:ring-neutral-600">
                            <img x-bind:src="currentBackground() ? currentBackground().author_avatar : ''" x-bind:alt="currentBackground() ? currentBackground().author_name : ''" class="size-full object-cover rounded-full overflow-hidden">
                        </span>
                        <span x-text="currentBackground() ? currentBackground().author_name : ''" class="max-w-40 truncate italic"></span>
                    </span>

                </p>

                <div
                    x-cloak
                    x-show="backgroundImages.length > 1 && !prefersReducedMotion"
                    class="pointer-events-none absolute inset-x-0 bottom-0 h-1 overflow-hidden bg-gray-200/70 dark:bg-neutral-700/70"
                    aria-hidden="true"
                >
                    <span
                        class="block size-full origin-left bg-orange-600 dark:bg-orange-500 will-change-transform"
                        x-bind:style="'transform: scaleX(' + autoplayProgress + '); transform-origin: left center'"
                    ></span>
                </div>
            </div>
        </div>

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
