<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class UserBlockedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public array $reservas = []
    ) {}
    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Estado de cuenta')
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->message);

        if (count($this->reservas) > 0) {

            $mail->line('Se cancelaron las siguientes reservas futuras:');

            foreach ($this->reservas as $reserva) {
                $mail->line($reserva);
            }
        }

        return $mail;
    }
}