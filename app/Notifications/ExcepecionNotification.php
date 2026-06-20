<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;

class ExcepcionNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $message,
        public array $reservas = []
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast', 'mail'];
    }

    public function toDatabase($notifiable)
    {
        return [
            'type' => 'Excepción creada',
            'message' => $this->message,
            'reservas' => $this->reservas,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => 'Excepción creada',
            'message' => $this->message,
            'reservas' => $this->reservas,
        ]);
    }

    public function toMail($notifiable)
    {
        $mail = (new MailMessage)
            ->subject('Excepción creada')
            ->greeting('Hola ' . $notifiable->name)
            ->line($this->message);

        if (count($this->reservas) > 0) {

            $mail->line('Se cancelaron las siguientes reservas:');

            foreach ($this->reservas as $reserva) {
                $mail->line($reserva);
            }
        }

        return $mail;
    }
}