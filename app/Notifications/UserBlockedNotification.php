<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserBlockedNotification extends Notification
{
    use Queueable;

    public function __construct(public string $message) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Cuenta bloqueada')
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->message);
    }
}