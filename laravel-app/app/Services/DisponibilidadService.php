<?php

namespace App\Services;

use App\Helpers\FeriadoHelper;
use App\Models\Disponibilidad;
use App\Models\Reserva;
use App\Models\Servicio;
use Carbon\Carbon;

class DisponibilidadService
{
    private array $dayMap = [
        0 => 'domingo',
        1 => 'lunes',
        2 => 'martes',
        3 => 'miercoles',
        4 => 'jueves',
        5 => 'viernes',
        6 => 'sabado',
    ];

    // Devuelve qué días de la semana tiene configurados un servicio
    public function getDiasConDisponibilidad(int $servicioId): array
    {
        $dias = Disponibilidad::where('servicio_id', $servicioId)
            ->distinct()
            ->pluck('dia_semana')
            ->toArray();

        return ['success' => true, 'data' => $dias];
    }

    // Devuelve todas las disponibilidades de un servicio (para la vista del profesional)
    public function getByServicio(int $servicioId): array
    {
        $disponibilidades = Disponibilidad::where('servicio_id', $servicioId)->get();
        return ['success' => true, 'data' => $disponibilidades];
    }

    // Reemplaza todas las disponibilidades de un servicio (bulk save)
    public function bulkUpdate(int $servicioId, array $nuevas, $user): array
    {
        $servicio = Servicio::find($servicioId);
        if (!$servicio) {
            return ['success' => false, 'message' => 'Servicio no encontrado'];
        }

        if ((int)$servicio->profesional_id !== (int)$user->id) {
            return ['success' => false, 'message' => 'No tenés permiso para modificar este servicio'];
        }
        $otrosServicios = Servicio::where(
            'profesional_id',
            $servicio->profesional_id
        )
        ->where('servicio_id', '!=', $servicioId)
        ->pluck('servicio_id');
        foreach ($nuevas as $d) {
            $solapada = Disponibilidad::whereIn('servicio_id', $otrosServicios)
                ->where('dia_semana', $d['dia_semana'])
                ->where('hora_inicio', '<', $d['hora_fin'])
                ->where('hora_fin', '>', $d['hora_inicio'])
                ->exists();

            if ($solapada) {
                return [
                    'success' => false,
                    'message' => 'Ya existe una disponibilidad en ese horario para otro servicio'
                ];
            }
        }

        Disponibilidad::where('servicio_id', $servicioId)->delete();
        $modalidadServicio = $servicio->modalidad;

        foreach ($nuevas as $d) {
            $modalidadDisponibilidad =
            $modalidadServicio === 'hibrido'
                ? $d['modalidad']
                : $modalidadServicio;
            Disponibilidad::create([
                'servicio_id' => $servicioId,
                'dia_semana'  => $d['dia_semana'],
                'hora_inicio' => $d['hora_inicio'],
                'hora_fin'    => $d['hora_fin'],
                'modalidad'   => $modalidadDisponibilidad
            ]);
        }

        return ['success' => true, 'message' => 'Disponibilidad actualizada'];
    }

    // Calcula los slots disponibles para un servicio en una fecha concreta
    public function getSlotsDisponibles(int $servicioId, string $fecha): array
    {
        $servicio = Servicio::find($servicioId);
        if (!$servicio) {
            return ['success' => false, 'message' => 'Servicio no encontrado'];
        }

        $carbon    = Carbon::parse($fecha);
        $diaSemana = $this->dayMap[$carbon->dayOfWeek];

        $bloques = Disponibilidad::where('servicio_id', $servicioId)
            ->where('dia_semana', $diaSemana)
            ->get();

        if ($bloques->isEmpty()) {
            return ['success' => true, 'data' => []];
        }

        // Reservas existentes (no canceladas) para este servicio en esta fecha
        $reservadas = Reserva::where('servicio_id', $servicioId)
            ->where('fecha', $fecha)
            ->whereNotIn('estado', ['cancelada'])
            ->pluck('hora')
            ->map(fn($h) => substr($h, 0, 5))
            ->toArray();

        $duracion = (int)$servicio->duracion;
        $pausa    = (int)$servicio->pausa;
        $slots    = [];

        foreach ($bloques as $bloque) {
            $cursor = Carbon::parse($fecha . ' ' . $bloque->hora_inicio);
            $fin    = Carbon::parse($fecha . ' ' . $bloque->hora_fin);

            while (true) {
                $slotFin = $cursor->copy()->addMinutes($duracion);
                if ($slotFin->gt($fin)) break;

                $slotStr = $cursor->format('H:i');

                if (!in_array($slotStr, $reservadas)) {
                    $slots[] = [
                        'hora' => $slotStr,
                        'modalidad' => $bloque->modalidad
                    ];
                }

                $cursor->addMinutes($duracion + $pausa);
            }
        }

        sort($slots);
        return ['success' => true, 'data' => $slots];
    }
}
