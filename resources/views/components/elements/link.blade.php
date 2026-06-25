<a
    {{ $attributes->except('wire:navigate') }}
    wire:navigate
    class="underline cursor-pointer opacity-[67%] hover:opacity-[80%]"
>
{{ $slot }}
</a>
