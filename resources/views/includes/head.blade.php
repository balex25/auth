<meta charset="utf-8">
<meta name="viewport" content="width=device-width, initial-scale=1">
<meta name="csrf-token" content="{{ csrf_token() }}">

<title>{{ $title ?? 'Auth' }}</title>
<meta name="description" content="{{ __('auth.login.description') }}">
@include('auth::includes.seo')

@if(config('devdojo.auth.settings.dev_mode'))
    @vite(['packages/devdojo/auth/resources/css/auth.css', 'packages/devdojo/auth/resources/css/auth.js'])
@else
    @php
        $authStylesPath = public_path('auth/build/assets/styles.css');
        $authStylesVersion = file_exists($authStylesPath) ? filemtime($authStylesPath) : null;
    @endphp
    <!--<script src="{{ asset('/auth/build/assets/scripts.js') }}" defer></script>-->
    <link rel="stylesheet" href="{{ asset('/auth/build/assets/styles.css') }}{{ $authStylesVersion ? '?v='.$authStylesVersion : '' }}" />
@endif

<link href="{{ asset('images/auth/favicon.png') }}" rel="icon" media="(prefers-color-scheme: light)" />
<link href="{{ asset('images/auth/favicon-dark.png') }}" rel="icon" media="(prefers-color-scheme: dark)" />

@stack('devdojo-auth-head-scripts')


@if(file_exists(public_path('auth/custom-head.js')))
    <!--<script src="/auth/custom-head.js" defer></script>-->
@endif

@vite('resources/js/public.js')
