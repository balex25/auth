<?php

use App\Models\User;
use Laravel\Socialite\Facades\Socialite;
use Laravel\Socialite\Two\User as SocialiteUser;

it('does not automatically link an unknown social identity by matching email', function () {
    $existingUser = User::factory()->create(['email' => 'existing@example.test']);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'unlinked-github-identity',
        'email' => $existingUser->email,
    ]));

    $this->get('/auth/github/callback')
        ->assertRedirect('/auth/login')
        ->assertSessionHas('error', __('auth.social.email_already_registered'));

    $this->assertGuest();
    expect(User::query()->count())->toBe(1)
        ->and($existingUser->socialProviders()->count())->toBe(0);
});

it('authenticates by provider identity even when the provider email changed', function () {
    $linkedUser = User::factory()->create(['email' => 'linked@example.test']);
    $otherUser = User::factory()->create(['email' => 'current-provider-email@example.test']);

    $linkedUser->socialProviders()->create([
        'provider_slug' => 'github',
        'provider_user_id' => 'linked-github-identity',
        'token' => '',
    ]);

    Socialite::fake('github', SocialiteUser::fake([
        'id' => 'linked-github-identity',
        'email' => $otherUser->email,
    ]));

    $this->get('/auth/github/callback')->assertRedirect();

    $this->assertAuthenticatedAs($linkedUser);
    expect($otherUser->socialProviders()->count())->toBe(0);
});
