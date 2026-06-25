<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Notifications\Messages\MailMessage;
use App\Notifications\Channels\WebPushChannel;


class ReservaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $message,
        public string $fecha,
        public string $hora,
    ) {}

    public function via($notifiable)
    {

        return ['database', 'broadcast', 'mail', WebPushChannel::class];
    }

    public function toPush($notifiable)
    {
        return [
            'title' => $this->type,
            'body' => $this->message,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
        ];
    }

    public function toDatabase($notifiable)
    {

        return [
            'type' => $this->type,
            'message' => $this->message,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
        ];
    }

    public function toBroadcast($notifiable)
    {

        return new BroadcastMessage([
            'type' => $this->type,
            'message' => $this->message,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
        ]);
    }

    public function toMail($notifiable)
{

    return (new MailMessage)
        ->subject('Nueva notificación')
        ->greeting('Hola!')
        ->line($this->message)
        ->line('Fecha: ' . $this->fecha)
        ->line('Hora: ' . $this->hora)
        ->line('Gracias por usar nuestra aplicación.');
}


}