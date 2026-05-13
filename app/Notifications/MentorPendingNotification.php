<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class MentorPendingNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'New mentor application',
            'message' => 'A new mentor application is awaiting your review.',
            'url'     => '/admin/mentors',
            'type'    => 'mentor_pending',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('New mentor application')
            ->line('A new mentor application is awaiting your review.')
            ->action('View', url('/admin/mentors'));
    }
}
