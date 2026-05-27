<?php

namespace App\Services;

use App\Models\Reserva;
use Carbon\Carbon;

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
                    ->whereDate('fecha', '>=', now())
                    ->orderBy('fecha')
                    ->orderBy('hora')
                    ->first();

                $estado = 'ACTIVA';

                if ($proximaReserva &&
                    $proximaReserva->fecha == now()->toDateString()) {
                    $estado = 'EN SESION';
                }

                $clientes[$clienteId] = [
                    'cliente_id' => $clienteId,
                    'nombre' => $reserva->cliente->user->name,
                    'email' => $reserva->cliente->user->email,

                    // si no viene de paquete queda 0
                    'sesiones_restantes' =>
                        $reserva->compraItemPaquete->sesiones_restantes ?? 0,

                    'proxima_sesion' => $proximaReserva
                        ? $proximaReserva->fecha
                        : null,

                    'hora_proxima_sesion' => $proximaReserva
                        ? $proximaReserva->hora
                        : null,

                    'estado' => $estado
                ];
            }
        }

        return array_values($clientes);
    }
}