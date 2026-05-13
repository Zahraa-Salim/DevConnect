<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HelpRequestPostedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'New help request matches you',
            'message' => 'A new help request matching your skills has been posted.',
            'url'     => '/help-requests',
            'type'    => 'help_posted',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New help request matches you')
            ->line('A new help request matching your skills has been posted.')
            ->action('View', url('/help-requests'));
    }
}
