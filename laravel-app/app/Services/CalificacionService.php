<?php

namespace App\Services;

use App\Models\Calificacion;
use App\Models\Reserva;
use App\Models\Servicio;

class CalificacionService
{
   public function crear($user, int $reservaId, array $data): array
    {  
        $reserva = Reserva::find($reservaId);

        if (!$reserva) {
            return [
                'success' => false,
                'message' => 'Reserva no encontrada'
            ];
        }

        if ((int)$reserva->cliente_id !== (int)$user->id) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        if ($reserva->estado !== 'finalizada') {
            return [
                'success' => false,
                'message' => 'Solo se pueden calificar reservas finalizadas'
            ];
        }

        if ($reserva->calificacion()->exists()) {
            return [
                'success' => false,
                'message' => 'La reserva ya fue calificada'
            ];
        }

        $calificacion = Calificacion::create([
            'reserva_id' => $reservaId,
            'puntuacion' => $data['puntuacion'],
            'comentario' => $data['comentario'] ?? null,
        ]);

        return [
            'success' => true,
            'data' => $calificacion
        ];
    }

    public function listarPorProfesional(int $profesionalId): array
    {
        $calificaciones = Calificacion::with([
            'reserva.servicio',
            'reserva.cliente'
        ])
        ->whereHas('reserva.servicio', function ($q) use ($profesionalId) {
            $q->where('profesional_id', $profesionalId);
        })
        ->orderByDesc('created_at')
        ->get();

        $promedio = round(
            $calificaciones->avg('puntuacion') ?? 0,
            1
        );

        return [
            'success' => true,
            'promedio' => $promedio,
            'cantidad' => $calificaciones->count(),
            'data' => $calificaciones
        ];
    }
}