<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;

class CatalogoActualizado implements ShouldBroadcast
{
    use Dispatchable, InteractsWithSockets;

    public function __construct(
        public string $tipo,
        public string $accion,
        public int $profesionalId,
        public int $id,
    ) {}

    public function broadcastOn(): array
    {
        return [new Channel('catalogo')];
    }

    public function broadcastAs(): string
    {
        return 'CatalogoActualizado';
    }

    public function broadcastWith(): array
    {
        return [
            'tipo' => $this->tipo,
            'accion' => $this->accion,
            'profesional_id' => $this->profesionalId,
            'id' => $this->id,
        ];
    }
}
