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
        public string $fecha,
        public string $hora,
    ) {}

    public function via($notifiable)
    {
            \Log::info('VIA EJECUTADO');

        return ['database', 'broadcast'];
    }

    public function toDatabase($notifiable)
    {
            \Log::info('TO DATABASE EJECUTADO');

        return [
            'type' => $this->type,
            'message' => $this->message,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
        ];
    }

    public function toBroadcast($notifiable)
    {
        \Log::info('TO BROADCAST EJECUTADO');

        return new BroadcastMessage([
            'type' => $this->type,
            'message' => $this->message,
            'fecha' => $this->fecha,
            'hora' => $this->hora,
        ]);
    }
}