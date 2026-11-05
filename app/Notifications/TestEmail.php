<?php

namespace App\Notifications;

use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

/** "Send test email" on the Settings page: checks the saved email settings. */
class TestEmail extends Notification
{
    public function via(object $notifiable): array
    {
        return ['mail'];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Test email')
            ->line('Your email settings work: this message was sent with the settings saved on the Settings page.')
            ->line('Password reset emails will be sent the same way.');
    }
}
