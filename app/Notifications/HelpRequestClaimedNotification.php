<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class HelpRequestClaimedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'A mentor is helping you',
            'message' => 'A mentor has claimed your help request.',
            'url'     => '/help-requests',
            'type'    => 'help_claimed',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('A mentor is helping you')
            ->line('A mentor has claimed your help request.')
            ->action('View', url('/help-requests'));
    }
}
