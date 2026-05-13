<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingCancelledNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Session cancelled',
            'message' => 'Your mentoring session has been cancelled.',
            'url'     => '/mentor/dashboard',
            'type'    => 'booking_cancelled',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Session cancelled')
            ->line('Your mentoring session has been cancelled.')
            ->action('View', url('/mentor/dashboard'));
    }
}
