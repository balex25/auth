<x-auth::elements.link
    {{ $attributes->except('wire:navigate') }}
    class="font-semibold cursor-pointer text-orange-600 hover:text-orange-700 no-underline hover:underline transition-all">
    {{ $slot }}
</x-auth::elements.link>
