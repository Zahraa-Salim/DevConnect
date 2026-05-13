<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class ReputationUpdatedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'You earned Verified Contributor!',
            'message' => 'Your reputation has been updated to Verified Contributor.',
            'url'     => '/profile',
            'type'    => 'reputation_updated',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You earned Verified Contributor!')
            ->line('Your reputation has been updated to Verified Contributor.')
            ->action('View', url('/profile'));
    }
}
