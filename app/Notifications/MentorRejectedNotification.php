<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorRejectedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Mentor Application Update',
            'message' => 'Your mentor application was not approved at this time. You may apply again after improving your profile.',
            'url'     => '/mentor/apply',
            'type'    => 'mentor_rejected',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Mentor Application Update')
            ->line('Your mentor application was not approved at this time.')
            ->line('You may apply again after improving your GitHub profile or experience.')
            ->action('Learn More', url('/mentor/apply'));
    }
}
