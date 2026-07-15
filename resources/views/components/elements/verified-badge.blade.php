@props([
    'label' => __('global.common.verified_user'),
    'size' => 'sm',
])

@php
    $sizeClass = match ($size) {
        'xs' => 'size-3 -mt-px',
        'credit' => 'size-3.5 mt-px',
        'credit-flush' => 'size-3.5 mt-px',
        'md' => 'size-4 mt-0',
        'lg' => 'size-6 mt-0.75 ml-2',
        default => 'size-3.5 -mt-px',
    };
@endphp

<span
    {{ $attributes->class("inline-block text-center align-middle shrink-0 {$sizeClass}") }}
    title="{{ $label }}"
    x-tooltip
>
    <svg class="size-full fill-orange-600 dark:fill-orange-500 group-hover:fill-current" xmlns="http://www.w3.org/2000/svg" enable-background="new 0 0 24 24" viewBox="0 0 24 24" fill="url(#brand-gradient)">
        <g>
            <path d="M23,12l-2.44-2.79l0.34-3.69l-3.61-0.82L15.4,1.5L12,2.96L8.6,1.5L6.71,4.69L3.1,5.5L3.44,9.2L1,12l2.44,2.79l-0.34,3.7l3.61,0.82L8.6,22.5l3.4-1.47l3.4,1.46l1.89-3.19l3.61-0.82l-0.34-3.69L23,12z M9.38,16.01L7,13.61c-0.39-0.39-0.39-1.02,0-1.41l0.07-0.07c0.39-0.39,1.03-0.39,1.42,0l1.61,1.62l5.15-5.16c0.39-0.39,1.03-0.39,1.42,0l0.07,0.07c0.39,0.39,0.39,1.02,0,1.41l-5.92,5.94C10.41,16.4,9.78,16.4,9.38,16.01z"></path>
        </g>
    </svg>
</span>
