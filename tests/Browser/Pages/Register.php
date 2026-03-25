<?php

namespace Devdojo\Auth\Tests\Browser\Pages;

use App\Models\User;
use Devdojo\Genesis\Genesis;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Support\Facades\Mail;
use Laravel\Dusk\Browser;
use Laravel\Dusk\Page;
class Register extends Page
{
    /**
     * Get the URL for the page.
     */
    public function url(): string
    {
        return '/auth/register';
    }

    public function registerAsJohnDoe(Browser $browser)
    {
        $redirectExpectedToBe = '/';
        if (class_exists(Genesis::class)) {
            $redirectExpectedToBe = '/dashboard';
        }
        $browser
            ->type('@email-input', 'johndoe@gmail.com')
            ->type('@password-input', 'password')
            ->clickAndWaitForReload('@submit-button');

        return $browser;

    }

    public function assertUserReceivedEmail()
    {
        $user = User::where('email', 'johndoe@gmail.com')->first();
        Mail::assertSent(MailMessage::class, function ($mail) use ($user) {
            return $mail->hasTo($user->email);
        });
    }
}
