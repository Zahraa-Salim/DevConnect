<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorApprovedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'You are now a Mentor!',
            'message' => 'Your mentor application has been approved.',
            'url'     => '/mentor/dashboard',
            'type'    => 'mentor_approved',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('You are now a Mentor!')
            ->line('Your mentor application has been approved.')
            ->action('View', url('/mentor/dashboard'));
    }
}
