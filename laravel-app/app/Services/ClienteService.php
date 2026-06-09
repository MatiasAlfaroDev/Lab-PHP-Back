<?php

namespace App\Services;

use App\Models\Reserva;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
class ClienteService
{
    public function getClientesDelProfesional(int $profesionalId)
    {
        $reservas = Reserva::with([
            'cliente.user',
            'compraItemPaquete'
        ])
        ->whereHas('servicio', function ($query) use ($profesionalId) {
            $query->where('profesional_id', $profesionalId);
        })
        ->orderBy('fecha')
        ->orderBy('hora')
        ->get();

        $clientes = [];

        foreach ($reservas as $reserva) {

            $clienteId = $reserva->cliente_id;

            // evitar repetir clientes
            if (!isset($clientes[$clienteId])) {

                $proximaReserva = Reserva::where('cliente_id', $clienteId)
                    ->whereHas('servicio', function ($query) use ($profesionalId) {
                        $query->where('profesional_id', $profesionalId);
                    })
                    ->whereNotIn('estado', ['cancelada', 'finalizada', 'no_asistida'])
                    ->where(function ($q) {
                        $q->where('fecha', '>', now()->toDateString())
                        ->orWhere(function ($q) {
                            $q->whereDate('fecha', now()->toDateString())
                                ->where('hora', '>=', now()->format('H:i:s'));
                        });
                    })
                    ->orderBy('fecha')
                    ->orderBy('hora')
                    ->first();

                if ($proximaReserva &&
                    $proximaReserva->fecha == now()->toDateString()) {
                }

                $clientes[$clienteId] = [
                    'cliente_id' => $clienteId,
                    'nombre' => $reserva->cliente->user->name,
                    'email' => $reserva->cliente->user->email,
                    'sesiones_restantes' => $reserva->compraItemPaquete->sesiones_restantes ?? 0,
                    'proxima_sesion' => $proximaReserva?->fecha,
                    'hora_proxima_sesion' => $proximaReserva?->hora,
                    'tiene_turnos' => $proximaReserva !== null,
                    'estado' => $proximaReserva?->estado,    
                ];
            }
        }

        return array_values($clientes);
    }

    public function updateProfile($user, array $data)
    {
        DB::beginTransaction();

        try {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            DB::commit();

            return [
                'success' => true,
                'message' => 'Perfil de cliente actualizado correctamente',
                'data' => $user
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al actualizar perfil'
            ];
        }
    }
}