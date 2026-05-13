<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class RatingsReceivedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'You received new ratings',
            'message' => 'Team members have submitted ratings for you.',
            'url'     => '/profile',
            'type'    => 'ratings_received',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You received new ratings')
            ->line('Team members have submitted ratings for you.')
            ->action('View', url('/profile'));
    }
}
