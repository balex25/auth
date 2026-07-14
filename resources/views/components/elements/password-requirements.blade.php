@php
$minLength = config('devdojo.auth.settings.password_min_length', 8);
$requireUppercase = config('devdojo.auth.settings.password_require_uppercase', false);
$requireNumeric = config('devdojo.auth.settings.password_require_numeric', false);
$requireSpecial = config('devdojo.auth.settings.password_require_special_character', false);
$requireUncompromised = config('devdojo.auth.settings.password_require_uncompromised', false);

// Only show if there are requirements beyond the default
$hasRequirements = $requireUppercase || $requireNumeric || $requireSpecial || $requireUncompromised || $minLength != 8;
@endphp

@if(config('devdojo.auth.settings.password_show_requirements', true) && $hasRequirements)
<div class="text-xs space-y-1 mt-1" style="color: {{ config('devdojo.auth.appearance.color.text') }}; opacity: 0.6;">
    <p class="font-medium">{{ __('auth.passwordRequirements.must') }}</p>
    <ul class="list-disc list-inside space-y-0.5 pl-1">
        @if($minLength > 0)
        <li>{{ trans_choice('auth.passwordRequirements.minimum_length', $minLength, ['count' => $minLength]) }}</li>
        @endif
        @if($requireUppercase)
        <li>{{ __('auth.passwordRequirements.mixed_case') }}</li>
        @endif
        @if($requireNumeric)
        <li>{{ __('auth.passwordRequirements.one_number') }}</li>
        @endif
        @if($requireSpecial)
        <li>{{ __('auth.passwordRequirements.one_special') }}</li>
        @endif
        @if($requireUncompromised)
        <li>{{ __('auth.passwordRequirements.uncompromised') }}</li>
        @endif
    </ul>
</div>
@endif
