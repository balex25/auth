<div {{ $attributes->merge(['class' => 'flex justify-center items-center w-full uppercase text-xs']) }}>
    <span class="w-full h-px"></span>
    <span class="px-2 w-auto text-gray-300 dark:text-neutral-300">{{ $slot }}</span>
    <span class="w-full h-px"></span>
</div>
