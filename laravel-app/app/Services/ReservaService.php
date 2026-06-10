<?php

namespace App\Services;

use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class ReservaService
{
   public function crearReserva($user, $data)
    {
            logger($data);

        return DB::transaction(function () use ($user, $data) {

            $hora = $data['hora'] . ':00';

            $existe = Reserva::where('servicio_id', $data['servicio_id'])
                ->where('fecha', $data['fecha'])
                ->where('hora', $hora)
                ->whereNotIn('estado', ['cancelada'])
                ->lockForUpdate()
                ->exists();

            if ($existe) {
                throw new \Exception('Horario no disponible');
            }

            return Reserva::create([
                'cliente_id' => $user->id,
                'servicio_id' => $data['servicio_id'],
                'compra_item_paquete_id' => $data['compra_item_paquete_id'] ?? null,
                'fecha' => $data['fecha'],
                'hora' => $hora,
                'estado' => 'pendiente',
                'modalidad' => $data['modalidad'],
                'estado_videollamada' => $data['estado_videollamada'],
            ]);
        });
    }

    // MIS RESERVAS (CLIENTE) no se esta usando
    public function misReservas($user)
    {
        return Reserva::where('cliente_id', $user->id)
            ->with(['servicio', 'pago', 'calificacion'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get()
            ->map(function ($r) {
                if ($r->servicio) {
                    $prof = User::find($r->servicio->profesional_id);
                    $r->servicio->setAttribute(
                        'profesional_nombre',
                        $prof?->name ?? 'Profesional'
                    );
                }
                return $r;
            });
    }

    // AGENDA PROFESIONAL no se esta usando
    public function agendaProfesional($user)
    {
        $servicioIds = Servicio::where('profesional_id', $user->id)
            ->pluck('servicio_id');

        return Reserva::whereIn('servicio_id', $servicioIds)
            ->whereNotIn('estado', ['cancelada'])
            ->with('servicio')
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function ($r) {
                $cliente = User::find($r->cliente_id);
                $r->setAttribute(
                    'cliente_nombre',
                    $cliente?->name ?? 'Cliente'
                );
                return $r;
            });
    }

    // CANCELAR RESERVA no se esta usando 
    public function cancelarReserva($user, $reserva)
    {
        if ((int) $reserva->cliente_id !== (int) $user->id) {
            throw new \Exception('No autorizado');
        }

        if (in_array($reserva->estado, ['cancelada', 'finalizada', 'no_asistida'])) {
            throw new \Exception('No se puede cancelar esta reserva');
        }

        $reserva->update(['estado' => 'cancelada']);

        return $reserva;
    }

    // CAMBIAR ESTADO no se esta usando
    public function cambiarEstado($user, $reserva, $nuevoEstado)
    {
        $actual = $reserva->estado;
        $reglas = [
            'pendiente' => ['confirmada', 'cancelada'],
            'confirmada' => ['pagada', 'cancelada'],
            'pagada' => ['en_curso'],
            'en_curso' => ['finalizada', 'no_asistida'],
        ];

        if (!isset($reglas[$actual]) || !in_array($nuevoEstado, $reglas[$actual])) {
            throw new \Exception("Transición no válida de $actual a $nuevoEstado");
        }

        // CONTROL DE ROLES
        $role = $user->role ?? null;

        if ($nuevoEstado === 'confirmada' && $role !== 'professional') {
            throw new \Exception("Solo el profesional puede confirmar");
        }

        if ($nuevoEstado === 'pagada' && $role !== 'client') {
            throw new \Exception("Solo el cliente puede pagar");
        }

        if (in_array($nuevoEstado, ['en_curso', 'finalizada', 'no_asistida']) && $role !== 'professional') {
            throw new \Exception("Solo el profesional puede actualizar este estado");
        }

        $reserva->update([
            'estado' => $nuevoEstado
        ]);

        return $reserva;
    }

    public function noAsistida(Reserva $reserva)
    {
        if (!in_array($reserva->estado, ['en_curso', 'finalizada'])) {
            return ['success' => false, 'message' => 'Estado no permitido'];
        }

        $reserva->update(['estado' => 'no_asistida']);
        return ['success' => true, 'message' => 'Reserva marcada como no asistida'];
    }

   public function actualizarEstadoVideollamada($reservaId, $estado)
    {
        $reserva = Reserva::findOrFail($reservaId);

        // evitar updates innecesarios
        if ($reserva->estado_videollamada === $estado) {
            return $reserva;
        }

        // actualizar estado
        $reserva->estado_videollamada = $estado;
        $reserva->save();

        return $reserva;
    }

    public function reprogramar(int $reservaId, string $fecha, string $hora): array
    {
        return DB::transaction(function () use ($reservaId, $fecha, $hora) {

            $reserva = Reserva::findOrFail($reservaId);

            // solo se puede reprogramar si está confirmada o pagada
            if (!in_array($reserva->estado, ['confirmada', 'pagada'])) {
                return [
                    'success' => false,
                    'message' => 'Solo se pueden reprogramar reservas confirmadas o pagadas'
                ];
            }

            $servicio = $reserva->servicio;
            $minHoras = $servicio->min_cancelacion ?? 0;

            $fechaHoraReserva = \Carbon\Carbon::parse(
                $reserva->fecha . ' ' . substr($reserva->hora, 0, 5)
            );

            $limite = now()->addHours($minHoras);

            if ($fechaHoraReserva->lessThanOrEqualTo($limite)) {
                return [
                    'success' => false,
                    'message' => "No podés reprogramar con menos de {$minHoras} horas de anticipación"
                ];
            }

            $reserva->update([
                'fecha' => $fecha,
                'hora' => $hora 
            ]);

            return [
                'success' => true,
                'message' => 'Reserva reprogramada correctamente',
                'data' => $reserva
            ];
        });
    }

    public function getById(int $id)
    {
        return Reserva::with([
            'servicio',
            'cliente',
            'compraItemPaquete',
            'servicio.profesional'
        ])->find($id);
    }
}