@props([
    'type' => 'primary', 
    'size' => 'md', 
    'tag' => 'button',
    'href' => '/',
    'submit' => false,
    'rounded' => 'full'
])

@php
    $sizeClasses = match ($size) {
        'sm' => 'px-2.5 py-1.5 text-xs font-medium rounded-' . $rounded,
        'md' => 'px-4 py-2.5 text-sm font-medium rounded-' . $rounded,
        'lg' => 'px-5 py-3  text-sm font-medium rounded-' . $rounded,
        'xl' => 'px-6 py-3.5 text-base font-medium rounded-' . $rounded,
        '2xl' => 'px-7 py-4 text-base font-medium rounded-' . $rounded
    };
@endphp

@php
    $typeClasses = match ($type) {
        'primary' => '',
        'secondary' => 'border border-gray-200 bg-white text-gray-800 hover:bg-gray-50 dark:border-white/20 dark:bg-white/5 dark:text-white dark:hover:bg-white/10',
        'success' => 'bg-green-600 text-white hover:bg-green-600/90 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 focus:bg-green-700/90 focus:ring-green-700',
        'info' => 'bg-blue-600 text-white hover:bg-blue-600/90 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 focus:bg-blue-700/90 focus:ring-blue-700',
        'warning' => 'bg-amber-500 text-white hover:bg-amber-500/90 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 focus:bg-amber-600/90 focus:ring-amber-600',
        'danger' => 'bg-red-600 text-white hover:bg-red-600/90 focus:ring-2 focus:ring-offset-2 dark:focus:ring-offset-neutral-900 focus:bg-red-700/90 focus:ring-red-700',
    };

    $loadingTarget = $attributes['wire:target'];
@endphp

@php
switch ($tag ?? 'button') {
    case 'button':
        $tagAttr = ($submit) ? 'button type="submit"' : 'button type="button"';
        $tagClose = 'button';
        break;
    case 'a':
        $link = $href ?? '';
        $tagAttr = 'a  href="' . $link . '"';
        $tagClose = 'a';
        break;
    default:
        $tagAttr = 'button type="button"';
        $tagClose = 'button';
        break;
}
@endphp

<{!! $tagAttr !!} {!! $attributes->except(['class']) !!} class="@if($type == 'primary'){{ 'auth-component-button text-white bg-orange-600 dark:bg-orange-500 hover:bg-orange-700 dark:hover:bg-orange-600' }}@endif {{ $sizeClasses }} {{ $typeClasses }} opacity-95 hover:opacity-100 focus:ring-2 focus:ring-offset-2 cursor-pointer inline-flex items-center w-full justify-center disabled:opacity-50 font-semibold focus:outline-none">
    <svg xmlns="http://www.w3.org/2000/svg" wire:loading @if(isset($loadingTarget)) wire:target="{{ $loadingTarget }}" @endif viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="mr-1.5 w-4 h-4 animate-spin"><path d="M21 12a9 9 0 1 1-6.219-8.56"></path></svg>
    {{ $slot }}
</{{ $tagClose }}>
