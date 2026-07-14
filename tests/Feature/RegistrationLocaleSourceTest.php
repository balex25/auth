<?php

use Illuminate\Support\Facades\File;

it('persists the current auth locale when the user model supports it', function () {
    $registration = File::get(dirname(__DIR__, 2).'/resources/views/pages/auth/register.blade.php');
    $socialController = File::get(dirname(__DIR__, 2).'/src/Http/Controllers/SocialController.php');
    $passwordlessManager = File::get(dirname(__DIR__, 2).'/src/PasswordlessLoginManager.php');
    $passwordInput = File::get(dirname(__DIR__, 2).'/resources/views/components/elements/input.blade.php');
    $passwordResetView = File::get(dirname(__DIR__, 2).'/resources/views/pages/auth/password/reset.blade.php');

    expect($registration)
        ->toContain("Schema::hasColumn(\$userModel->getTable(), 'locale')")
        ->toContain("\$userData['locale'] = Helper::currentLocale() ?? config('app.locale', 'en')")
        ->and($socialController)
        ->toContain("Schema::hasColumn(\$userModel->getTable(), 'locale')")
        ->toContain("\$attributes['locale'] = Helper::currentLocale() ?? config('app.locale', 'en')")
        ->toContain('restoreStoredLocale')
        ->toContain('app()->setLocale($locale)')
        ->and($passwordlessManager)
        ->toContain('if ($user instanceof HasLocalePreference)')
        ->toContain('$preferredLocale = $this->validLocale($user->preferredLocale())')
        ->toContain('$locale = $this->notificationLocale($user, $locale)')
        ->and($passwordInput)
        ->toContain("__('auth.passwordRequirements.label')")
        ->toContain("trans_choice('auth.passwordRequirements.minimum_length'")
        ->and($passwordResetView)
        ->toContain("__('auth.passwordResetRequest.sent')")
        ->not->toContain('trans(Password::RESET_LINK_SENT)');
});
