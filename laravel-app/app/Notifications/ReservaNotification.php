<?php

namespace App\Notifications;

use Illuminate\Bus\Queueable;
use Illuminate\Notifications\Notification;
use Illuminate\Notifications\Messages\BroadcastMessage;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Broadcasting\PrivateChannel;

class ReservaNotification extends Notification 
{
    use Queueable;

    public function __construct(
        public string $type, // "created" | "pending"
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
            'type' => $this->type,
            'message' => $this->message,
            'reserva_id' => $this->reservaId,
        ];
    }

    public function toBroadcast($notifiable)
    {
        return new BroadcastMessage([
            'type' => $this->type,
            'message' => $this->message,
            'reserva_id' => $this->reservaId,
        ]);
    }
}