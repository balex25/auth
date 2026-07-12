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
        passwordVisible: false,
        showPasswordLabel: @js(__('auth.passwordVisibility.show')),
        hidePasswordLabel: @js(__('auth.passwordVisibility.hide')),
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
                    @if ($type === 'password')
                        x-bind:type="passwordVisible ? 'text' : 'password'"
                    @else
                        type="{{ $type }}"
                    @endif
                    x-ref="input"
                    @focus="focused()"
                    @blur="blurred()"
                    class="auth-component-input appearance-none flex w-full h-11 {{ $type === 'password' ? 'pr-11 pl-3.5' : 'px-3.5' }} text-sm rounded-md
                           bg-gray-800 dark:bg-black text-gray-100 dark:text-white
                           border border-gray-600
                           placeholder:text-gray-400 dark:placeholder:text-neutral-500
                           focus:outline-none focus:ring-1 focus:ring-zinc-200 focus:border-zinc-200
                           ring-offset-gray-800 dark:ring-offset-black
                           disabled:cursor-not-allowed disabled:opacity-50
                           @error($wireModel) @enderror"
                />

                @if ($type === 'password')
                    <button
                        type="button"
                        x-on:click="passwordVisible = ! passwordVisible"
                        x-bind:aria-pressed="passwordVisible"
                        x-bind:aria-label="passwordVisible ? hidePasswordLabel : showPasswordLabel"
                        class="absolute inset-y-0 right-0 inline-flex w-11 items-center justify-center rounded-r-md text-gray-400 transition-colors hover:text-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-inset focus-visible:ring-orange-600 dark:text-neutral-500 dark:hover:text-neutral-200 dark:focus-visible:ring-orange-500"
                    >
                        <svg x-show="! passwordVisible" xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="M2.04 12.32a1 1 0 0 1 0-.64C3.42 7.51 7.36 5 12 5s8.58 2.51 9.96 6.68a1 1 0 0 1 0 .64C20.58 16.49 16.64 19 12 19s-8.58-2.51-9.96-6.68Z" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="passwordVisible" x-cloak xmlns="http://www.w3.org/2000/svg" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" class="size-5" aria-hidden="true">
                            <path stroke-linecap="round" stroke-linejoin="round" d="m3 3 18 18M10.58 10.58a2 2 0 0 0 2.83 2.83M9.88 4.24A10.8 10.8 0 0 1 12 4c4.64 0 8.58 2.51 9.96 6.68a1 1 0 0 1 0 .64 10.7 10.7 0 0 1-2.1 3.67M6.61 6.61a10.7 10.7 0 0 0-4.57 5.07 1 1 0 0 0 0 .64C3.42 16.49 7.36 19 12 19c1.27 0 2.47-.19 3.57-.54" />
                        </svg>
                    </button>
                @endif
            </div>
        </div>
    </div>

    @error($wireModel)
        <p class="my-2 text-sm text-red-400">{{ $message }}</p>
    @enderror
</div>
