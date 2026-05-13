<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Notifications\Messages\MailMessage;
use Illuminate\Notifications\Notification;

class BookingConfirmedNotification extends Notification implements ShouldQueue
{
    use Queueable;

    public function via(object $notifiable): array
    {
        return ['database', 'mail'];
    }

    public function toArray(object $notifiable): array
    {
        return [
            'title'   => 'Session booked',
            'message' => 'Your mentoring session has been confirmed.',
            'url'     => '/mentor/dashboard',
            'type'    => 'booking_confirmed',
        ];
    }

    public function toMail(object $notifiable): MailMessage
    {
        return (new MailMessage)
            ->subject('Session booked')
            ->line('Your mentoring session has been confirmed.')
            ->action('View', url('/mentor/dashboard'));
    }
}
