<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;

class ReservaNotification extends Notification
{
    use Queueable;

    public function __construct(
        public string $type,
        public string $message,
        public int $reservaId
    ) {}

    public function via($notifiable)
    {
        return ['database', 'broadcast'];
    }

    public function toArray($notifiable)
    {
        return [
            'type' => 'reserva',
            'message' => $this->message,
            'reserva_id' => $this->reservaId,
        ];
    }

    public function toBroadcast($notifiable)
    {
        \Log::info('TO BROADCAST EJECUTADO');

        return new BroadcastMessage([
            'type' => 'reserva',
            'message' => $this->message,
            'reserva_id' => $this->reservaId,
        ]);
    }
}