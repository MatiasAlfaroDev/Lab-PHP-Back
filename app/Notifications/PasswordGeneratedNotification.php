<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class PasswordGeneratedNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $password
    ) {}

    public function via($notifiable)
    {
        return ['mail'];
    }

    public function toMail($notifiable)
    {
        return (new MailMessage)
            ->subject('Bienvenido a la plataforma')
            ->greeting('Hola ' . $notifiable->name)
            ->line('Tu cuenta fue creada mediante Google.')
            ->line('Se generó una contraseña temporal para tu usuario.')
            ->line('Contraseña: ' . $this->password)
            ->line('Por seguridad te recomendamos cambiarla luego de iniciar sesión.');
    }
}