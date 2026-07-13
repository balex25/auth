<?php

namespace Devdojo\Auth\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class PasswordlessLoginNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function __construct(
        public readonly string $url,
        public readonly int $expiresInMinutes,
    ) {}

    /** @return array<int, string> */
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject(__('auth.passwordless.email_subject'))
            ->line(__('auth.passwordless.email_intro'))
            ->action(__('auth.passwordless.email_action'), $this->url)
            ->line(__('auth.passwordless.email_expires', ['minutes' => $this->expiresInMinutes]))
            ->line(__('auth.passwordless.email_ignore'));
    }
}
