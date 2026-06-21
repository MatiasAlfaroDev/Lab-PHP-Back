<?php
namespace App\Services;
use App\Models\Excepcion;
use Carbon\Carbon;
use App\Models\Reserva;
use App\Models\User;
use App\Notifications\ExcepcionNotification;
use App\Notifications\ReservaNotification;

class ExcepcionService
{
    public function listar($user): array
    {
        $excepciones = Excepcion::where(
            'profesional_id',
            $user->id
        )
        ->orderBy('fecha_desde')
        
        ->get();

        return [
            'success' => true,
            'data' => $excepciones
        ];
    }

    private function aplicarExcepcionAReservas(Carbon $inicio, Carbon $fin, $user): array
    {
        $detalleReservas = [];

        $reservas = Reserva::with('servicio')
            ->whereHas('servicio', function ($q) use ($user) {
                $q->where('profesional_id', $user->id);
            })
            ->whereIn('estado', ['pendiente', 'confirmada', 'pagada'])
            ->get()
            ->filter(function ($reserva) use ($inicio, $fin) {

                $reservaInicio = Carbon::parse(
                    $reserva->fecha . ' ' . substr($reserva->hora, 0, 5)
                );

                $reservaFin = $reservaInicio->copy()->addMinutes(
                    $reserva->servicio->duracion
                );

                return $reservaInicio < $fin && $reservaFin > $inicio;
            });

        foreach ($reservas as $reserva) {

            $servicio = $reserva->servicio;

            $reserva->update([
                'estado' => 'cancelada'
            ]);

            if ($servicio) {

                $detalleReservas[] =
                    "• {$servicio->nombre} - {$reserva->fecha} {$reserva->hora}";

                $cliente = User::find($reserva->cliente_id);

                if ($cliente) {
                    $cliente->notify(
                        new ReservaNotification(
                            'Reserva cancelada',
                            "Tu reserva para {$servicio->nombre} fue cancelada por una excepción de horario del profesional.",
                            $reserva->fecha,
                            $reserva->hora
                        )
                    );
                }
            }
        }

        return $detalleReservas;
    }
    public function crear(array $data, $user): array
    {
        $inicio = Carbon::parse(
            $data['fecha_desde'] . ' ' . ($data['hora_inicio'] ?? '00:00')
        );

        $fin = Carbon::parse(
            ($data['fecha_hasta'] ?? $data['fecha_desde']) . ' ' .
            ($data['hora_fin'] ?? '23:59')
        );

        if ($inicio < now()) {
            return [
                'success' => false,
                'message' => 'No se pueden crear excepciones en el pasado.'
            ];
        }

        if ($fin <= $inicio) {
            return [
                'success' => false,
                'message' => 'La fecha de fin debe ser posterior al inicio.'
            ];
        }

        Excepcion::create([
            'profesional_id' => $user->id,
            'fecha_desde' => $data['fecha_desde'],
            'fecha_hasta' => $data['fecha_hasta'] ?? $data['fecha_desde'],
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'motivo' => $data['motivo'] ?? null,
        ]);

        $detalleReservas = $this->aplicarExcepcionAReservas($inicio, $fin, $user);

        $user->notify(
            new ExcepcionNotification(
                'Excepción creada correctamente.',
                $detalleReservas
            )
        );

        return [
            'success' => true,
            'message' => 'Excepción creada y reservas notificadas',
            'reservas_afectadas' => count($detalleReservas)
        ];
    }

    public function editar(int $excepcionId, array $data, $user): array
    {
        $excepcion = Excepcion::find($excepcionId);

        if (!$excepcion) {
            return [
                'success' => false,
                'message' => 'Excepción no encontrada'
            ];
        }

        if ((int)$excepcion->profesional_id !== (int)$user->id) {
            return [
                'success' => false,
                'message' => 'No tenés permiso'
            ];
        }

        $ahora = Carbon::now();

        $fechaDesde = Carbon::parse($excepcion->fecha_desde);
        $horaInicio = $excepcion->hora_inicio
            ? Carbon::parse($excepcion->fecha_desde . ' ' . $excepcion->hora_inicio)
            : null;

        $esFutura = false;

        if ($fechaDesde->isFuture()) {
            $esFutura = true;
        } elseif ($fechaDesde->isToday()) {
            if (!$horaInicio || $horaInicio->gt($ahora)) {
                $esFutura = true;
            }
        }

        if (!$esFutura) {
            return [
                'success' => false,
                'message' => 'No se puede editar una excepción pasada'
            ];
        }

        $excepcion->update([
            'fecha_desde' => $data['fecha_desde'],
            'fecha_hasta' => $data['fecha_hasta'] ?? $data['fecha_desde'],
            'hora_inicio' => $data['hora_inicio'] ?? null,
            'hora_fin' => $data['hora_fin'] ?? null,
            'motivo' => $data['motivo'] ?? null,
        ]);

        $inicio = Carbon::parse(
            $data['fecha_desde'] . ' ' . ($data['hora_inicio'] ?? '00:00')
        );

        $fin = Carbon::parse(
            ($data['fecha_hasta'] ?? $data['fecha_desde']) . ' ' .
            ($data['hora_fin'] ?? '23:59')
        );

        $detalleReservas = $this->aplicarExcepcionAReservas($inicio, $fin, $user);

        $user->notify(
            new ExcepcionNotification(
                'Excepción actualizada correctamente.',
                $detalleReservas
            )
        );

        return [
            'success' => true,
            'message' => 'Excepción actualizada y reservas notificadas',
            'reservas_afectadas' => count($detalleReservas)
        ];
    }
    
    public function eliminar(int $excepcionId, $user): array
    {
        $excepcion = Excepcion::find($excepcionId);

        if (!$excepcion) {
            return [
                'success' => false,
                'message' => 'Excepción no encontrada'
            ];
        }

        if ((int)$excepcion->profesional_id !== (int)$user->id) {
            return [
                'success' => false,
                'message' => 'No tenés permiso'
            ];
        }

        $ahora = Carbon::now();

        $fechaDesde = Carbon::parse($excepcion->fecha_desde);

        $horaInicio = $excepcion->hora_inicio
            ? Carbon::parse($excepcion->fecha_desde . ' ' . $excepcion->hora_inicio)
            : null;

        $esFutura = false;

        if ($fechaDesde->isFuture()) {
            $esFutura = true;
        } elseif ($fechaDesde->isToday()) {
            if (!$horaInicio || $horaInicio->gt($ahora)) {
                $esFutura = true;
            }
        }

        if (!$esFutura) {
            return [
                'success' => false,
                'message' => 'Solo se pueden eliminar excepciones futuras'
            ];
        }

        $excepcion->delete();

        return [
            'success' => true,
            'message' => 'Excepción eliminada'
        ];
    }
}