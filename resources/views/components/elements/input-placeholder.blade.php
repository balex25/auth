@props([
    'value' => ''
])

<div data-auth="email-read-only-placeholder" {{ $attributes->merge(['class' => 'readonlyInput flex items-center justify-between px-4 py-3 text-sm text-white bg-transparent border rounded-md border-white/10 bg-white/10 hover:bg-white/10 dark:border-zinc-700 dark:text-zinc-300 dark:bg-zinc-800 dark:hover:bg-zinc-800']) }}>
    <span>{{ $value }}</span>
    {{ $slot }}
</div>
