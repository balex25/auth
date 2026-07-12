@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'type' => 'text',
    'autofocus' => false,
])

@php $wireModel = $attributes->get('wire:model'); @endphp

<div x-data="{
        focusedOrFilled: false,
        focused(){ this.focusedOrFilled = true },
        blurred() {
            if (this.$refs.input.value == '') this.focusedOrFilled = false;
        }
    }"
    x-init="
        @if($autofocus ?? false)
            setTimeout(function(){ $refs.input.focus(); }, 1);
        @endif
        // if input already has value on load, keep label floated:
        setTimeout(() => { if ($refs.input.value !== '') focusedOrFilled = true }, 1);
    "
    class="w-full h-auto"
>
    <div class="flex relative flex-col justify-center h-11">
        <div class="flex relative">
            @if($label)
                <label
                    for="{{ $id ?? '' }}"
                    @click="$refs.input.focus()"
                    :class="{
                        'top-0 -translate-y-1 ml-2 text-xs text-gray-200' : focusedOrFilled,
                        'top-4 ml-2.5 text-[15px] text-gray-400' : !focusedOrFilled
                    }"
                    class="block absolute top-0 px-1.5 py-0 font-normal leading-normal
                           text-gray-400 dark:text-neutral-500
                           bg-gray-800 dark:bg-black
                           duration-300 ease-out cursor-text auth-component-input"
                    x-cloak
                >
                    {{ $label }}
                </label>
            @endif

            <div data-model="{{ $wireModel }}" class="mt-1.5 w-full rounded-md shadow-sm auth-component-input-container">
                <input
                    {{ $attributes }}
                    {{ $attributes->whereStartsWith('wire:model') }}
                    @focus-{{ $id }}.window="$el.focus()"
                    id="{{ $id ?? '' }}"
                    name="{{ $name ?? '' }}"
                    type="{{ $type ?? '' }}"
                    x-ref="input"
                    @focus="focused()"
                    @blur="blurred()"
                    class="auth-component-input appearance-none flex w-full h-11 px-3.5 text-sm rounded-md
                           bg-gray-800 dark:bg-black text-gray-100 dark:text-white
                           border border-gray-600
                           placeholder:text-gray-400 dark:placeholder:text-neutral-500
                           focus:outline-none focus:ring-1 focus:ring-zinc-200 focus:border-zinc-200
                           ring-offset-gray-800 dark:ring-offset-black
                           disabled:cursor-not-allowed disabled:opacity-50
                           @error($wireModel) @enderror"
                />
            </div>
        </div>
    </div>

    @error($wireModel)
        <p class="my-2 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>
