@php
    $authSeoLocales = array_values(array_filter(config('app.locales', ['en'])));
    $authSeoPathSegments = array_values(array_filter(explode('/', trim(request()->path(), '/'))));
    $authSeoForceNoindex = (bool) ($authSeoForceNoindex ?? false);
    $authSeoHasQueryString = trim((string) request()->server('QUERY_STRING', '')) !== '';
    $authSeoIsNoindex = $authSeoForceNoindex || $authSeoHasQueryString;

    if (isset($authSeoPathSegments[0]) && in_array($authSeoPathSegments[0], $authSeoLocales, true)) {
        array_shift($authSeoPathSegments);
    }

    $authSeoPathSuffix = $authSeoPathSegments === [] ? '' : '/'.implode('/', $authSeoPathSegments);
    $authSeoDefaultLocale = config('app.locale', 'en');
@endphp

@if ($authSeoIsNoindex)
    <meta name="robots" content="noindex, nofollow">
    <meta name="googlebot" content="noindex, nofollow">
@else
    <meta name="robots" content="index, follow">
    <meta name="googlebot" content="index, follow">
    <link rel="canonical" href="{{ url()->current() }}">

    @foreach ($authSeoLocales as $authSeoLocale)
        <link rel="alternate" hreflang="{{ $authSeoLocale }}" href="{{ url('/'.$authSeoLocale.$authSeoPathSuffix) }}">
    @endforeach
    <link rel="alternate" hreflang="x-default" href="{{ url('/'.$authSeoDefaultLocale.$authSeoPathSuffix) }}">
@endif
