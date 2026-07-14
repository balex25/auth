@props([
    'label' => null,
    'id' => null,
    'name' => null,
    'type' => 'text',
    'autofocus' => false,
    'showRequirements' => false,
])

@php $wireModel = $attributes->get('wire:model'); @endphp
@php
    $minimumPasswordLength = (int) config('devdojo.auth.settings.password_min_length', 8);
    $requiresMixedCase = (bool) config('devdojo.auth.settings.password_require_uppercase', false);
    $requiresNumber = (bool) config('devdojo.auth.settings.password_require_numeric', false);
    $requiresSymbol = (bool) config('devdojo.auth.settings.password_require_special_character', false);
    $displaysPasswordRequirements = $type === 'password'
        && $showRequirements
        && config('devdojo.auth.settings.password_show_requirements', true);
@endphp

<div x-data="{
        focusedOrFilled: false,
        passwordVisible: false,
        passwordValue: '',
        requirementsOpen: false,
        minimumPasswordLength: @js($minimumPasswordLength),
        requiresMixedCase: @js($requiresMixedCase),
        requiresNumber: @js($requiresNumber),
        requiresSymbol: @js($requiresSymbol),
        meetsMinimumLength() { return this.passwordValue.length >= this.minimumPasswordLength },
        includesLowercase() { return /[a-z]/.test(this.passwordValue) },
        includesUppercase() { return /[A-Z]/.test(this.passwordValue) },
        includesNumber() { return /[0-9]/.test(this.passwordValue) },
        includesSymbol() { return /[^A-Za-z0-9]/.test(this.passwordValue) },
        meetsPasswordRequirements() {
            return this.meetsMinimumLength()
                && (! this.requiresMixedCase || (this.includesLowercase() && this.includesUppercase()))
                && (! this.requiresNumber || this.includesNumber())
                && (! this.requiresSymbol || this.includesSymbol());
        },
        showPasswordLabel: @js(__('auth.passwordVisibility.show')),
        hidePasswordLabel: @js(__('auth.passwordVisibility.hide')),
        focused(){
            this.focusedOrFilled = true;
            @if($displaysPasswordRequirements)
                this.requirementsOpen = true;
            @endif
        },
        blurred() {
            if (this.$refs.input.value == '') this.focusedOrFilled = false;
            setTimeout(() => {
                if (! this.$el.contains(document.activeElement)) this.requirementsOpen = false;
            }, 0);
        }
    }"
    x-init="
        @if($autofocus ?? false)
            setTimeout(function(){ $refs.input.focus(); }, 1);
        @endif
        // if input already has value on load, keep label floated:
        setTimeout(() => {
            passwordValue = $refs.input.value;
            if ($refs.input.value !== '') focusedOrFilled = true;
        }, 1);
    "
    class="w-full h-auto"
>
    <div class="flex relative flex-col justify-center {{ $displaysPasswordRequirements ? 'min-h-11' : 'h-11' }}">
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
                    @if ($type === 'password')
                        @input="passwordValue = $el.value"
                    @endif
                    class="auth-component-input appearance-none flex w-full h-11 {{ $type === 'password' ? ($displaysPasswordRequirements ? 'pr-20 pl-3.5' : 'pr-11 pl-3.5') : 'px-3.5' }} text-sm rounded-md
                           bg-gray-800 dark:bg-black text-gray-100 dark:text-white
                           border border-gray-600
                           placeholder:text-gray-400 dark:placeholder:text-neutral-500
                           focus:outline-none focus:ring-1 focus:ring-zinc-200 focus:border-zinc-200
                           ring-offset-gray-800 dark:ring-offset-black
                           disabled:cursor-not-allowed disabled:opacity-50
                           @error($wireModel) @enderror"
                />

                @if ($type === 'password')
                    @if ($displaysPasswordRequirements)
                        <button
                            type="button"
                            x-on:click="requirementsOpen = ! requirementsOpen"
                            x-bind:aria-expanded="requirementsOpen"
                            aria-label="{{ __('auth.passwordRequirements.label') }}"
                            class="absolute top-3 right-10 inline-flex size-8 items-center justify-center rounded-md transition-colors hover:bg-white/5 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 dark:focus-visible:ring-orange-500"
                            x-bind:class="meetsPasswordRequirements() ? 'text-green-500' : 'text-red-500'"
                        >
                            <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                        </button>

                        <div
                            x-show="requirementsOpen"
                            x-cloak
                            x-transition:enter="transition ease-out duration-150"
                            x-transition:enter-start="opacity-0 translate-y-1"
                            x-transition:enter-end="opacity-100 translate-y-0"
                            x-transition:leave="transition ease-in duration-100"
                            x-transition:leave-start="opacity-100 translate-y-0"
                            x-transition:leave-end="opacity-0 translate-y-1"
                            class="relative mt-2 w-full rounded-md border border-gray-600 bg-gray-800 p-3 text-xs shadow-sm dark:border-neutral-700 dark:bg-neutral-900"
                        >
                            <p class="mb-2 font-medium text-gray-200 dark:text-neutral-200">{{ __('auth.passwordRequirements.must_contain') }}</p>
                            <ul class="grid gap-1.5 text-gray-400 dark:text-neutral-400 sm:grid-cols-2">
                                <li class="flex items-center gap-2" x-bind:class="meetsMinimumLength() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                    <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                    <span>{{ trans_choice('auth.passwordRequirements.minimum_length', $minimumPasswordLength, ['count' => $minimumPasswordLength]) }}</span>
                                </li>
                                @if ($requiresMixedCase)
                                    <li class="flex items-center gap-2" x-bind:class="includesLowercase() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                        <span>{{ __('auth.passwordRequirements.one_lowercase') }}</span>
                                    </li>
                                    <li class="flex items-center gap-2" x-bind:class="includesUppercase() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                        <span>{{ __('auth.passwordRequirements.one_uppercase') }}</span>
                                    </li>
                                @endif
                                @if ($requiresNumber)
                                    <li class="flex items-center gap-2" x-bind:class="includesNumber() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                        <span>{{ __('auth.passwordRequirements.one_number') }}</span>
                                    </li>
                                @endif
                                @if ($requiresSymbol)
                                    <li class="flex items-center gap-2" x-bind:class="includesSymbol() ? 'text-green-600 dark:text-green-400' : 'text-red-600 dark:text-red-400'">
                                        <svg xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-3.5 shrink-0" aria-hidden="true"><path d="M20 6 9 17l-5-5" /></svg>
                                        <span>{{ __('auth.passwordRequirements.one_special') }}</span>
                                    </li>
                                @endif
                            </ul>
                        </div>
                    @endif

                    <button
                        type="button"
                        x-on:click="passwordVisible = ! passwordVisible"
                        x-bind:aria-pressed="passwordVisible"
                        x-bind:aria-label="passwordVisible ? hidePasswordLabel : showPasswordLabel"
                        class="absolute top-3 right-1 inline-flex size-8 items-center justify-center rounded-md text-gray-400 transition-colors hover:bg-white/5 hover:text-gray-200 focus:outline-none focus-visible:ring-2 focus-visible:ring-orange-600 dark:text-neutral-500 dark:hover:text-neutral-200 dark:focus-visible:ring-orange-500"
                    >
                        <svg x-show="! passwordVisible" xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true">
                            <path d="M2.062 12.348a1 1 0 0 1 0-.696 10.75 10.75 0 0 1 19.876 0 1 1 0 0 1 0 .696 10.75 10.75 0 0 1-19.876 0" />
                            <circle cx="12" cy="12" r="3" />
                        </svg>
                        <svg x-show="passwordVisible" x-cloak xmlns="http://www.w3.org/2000/svg" width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" class="size-4 shrink-0" aria-hidden="true">
                            <path d="M10.733 5.076a10.744 10.744 0 0 1 11.205 6.575 1 1 0 0 1 0 .696 10.747 10.747 0 0 1-1.444 2.49" />
                            <path d="M14.084 14.158a3 3 0 0 1-4.242-4.242" />
                            <path d="M17.479 17.499a10.75 10.75 0 0 1-15.417-5.151 1 1 0 0 1 0-.696 10.75 10.75 0 0 1 4.446-5.143" />
                            <path d="m2 2 20 20" />
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
