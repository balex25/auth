<a href="{{ \Devdojo\Auth\Helper::localizedUrl('auth/' . $slug . '/redirect', true) }}" class="flex @if(config('devdojo.auth.settings.center_align_social_provider_button_content')){{ 'justify-center' }}@endif items-center px-4 py-3 space-x-2.5 w-full h-auto text-sm rounded-md border border-white/10 text-white hover:bg-white/10 dark:border-zinc-700 dark:text-zinc-300 dark:hover:bg-zinc-800" data-auth="social-provider-button-{{ $slug }}">
    <span class="w-5 h-5">
        @if(isset($provider->svg) && !empty(trim($provider->svg)))
            @php
                $svg = $provider->svg;
                if ($slug === 'twitter') {
                    $svg = str_replace('fill="#000"', 'fill="white"', $svg);
                } elseif ($slug === 'github') {
                    $svg = str_replace('fill="#24292F"', 'fill="white"', $svg);
                }
            @endphp
            {!! $svg !!}
        @else
            <span class="block w-full h-full rounded-full bg-zinc-200"></span>
        @endif
    </span>
    <span>{{ __('auth.social.continue_with', ['provider' => $provider->name]) }}</span>
</a>
