<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\MailMessage;

class EmailVerificationCodeNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $code
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Verifica tu correo electrónico')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Gracias por registrarte en la plataforma.')
            ->line('Tu código de verificación es:')
            ->line('**' . $this->code . '**')
            ->line('Este código vence en 15 minutos.')
            ->line('Si no creaste esta cuenta, puedes ignorar este mensaje.');
    }
}
