<!DOCTYPE html>
<html lang="{{ str_replace('_', '-', app()->getLocale()) }}" @class(['dark' => request()->query('theme') === 'dark'])>
<head>
    @include('auth::includes.head')
</head>
<body class="bg-white text-gray-900 dark:bg-zinc-900 dark:text-gray-100">
    {{ $slot }}
</body>
</html>
