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
        'servicio',
        'compraItemPaquete.itemPaquete.servicio'
    ])
    ->where(function ($q) use ($profesionalId) {

        // Reservas individuales
        $q->whereHas('servicio', function ($query) use ($profesionalId) {
            $query->where('profesional_id', $profesionalId);
        });

        // Reservas de paquete
        $q->orWhereHas(
            'compraItemPaquete.itemPaquete.servicio',
            function ($query) use ($profesionalId) {
                $query->where('profesional_id', $profesionalId);
            }
        );
    })
    ->orderBy('fecha')
    ->orderBy('hora')
    ->get();

    $clientesSesion = [];
    $clientesPaquete = [];

    foreach ($reservas as $reserva) {

        $clienteId = $reserva->cliente_id;

        /*
        |--------------------------------------------------------------------------
        | SESIONES INDIVIDUALES
        |--------------------------------------------------------------------------
        */
        if ($reserva->servicio_id !== null) {

            if (!isset($clientesSesion[$clienteId])) {

                $proximaReserva = Reserva::where('cliente_id', $clienteId)
                    ->whereNotNull('servicio_id')
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

                $clientesSesion[$clienteId] = [
                    'cliente_id' => $clienteId,
                    'nombre' => $reserva->cliente->user->name,
                    'email' => $reserva->cliente->user->email,
                    'proxima_sesion' => $proximaReserva?->fecha,
                    'hora_proxima_sesion' => $proximaReserva?->hora,
                    'tiene_turnos' => $proximaReserva !== null,
                    'estado' => $proximaReserva?->estado,
                ];
            }
        }

        /*
        |--------------------------------------------------------------------------
        | PAQUETES
        |--------------------------------------------------------------------------
        */
        if ($reserva->compra_item_paquete_id !== null) {

            if (!isset($clientesPaquete[$clienteId])) {

                $proximaReserva = Reserva::where('cliente_id', $clienteId)
                    ->whereNotNull('compra_item_paquete_id')
                    ->whereHas(
                        'compraItemPaquete.itemPaquete.servicio',
                        function ($query) use ($profesionalId) {
                            $query->where('profesional_id', $profesionalId);
                        }
                    )
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

                $clientesPaquete[$clienteId] = [
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
    }

    $historicos = [];

// Clientes de sesión sin turnos futuros
foreach ($clientesSesion as $cliente) {
    if (!$cliente['tiene_turnos']) {
        $historicos[$cliente['cliente_id']] = $cliente;
    }
}

// Clientes de paquete sin turnos futuros
foreach ($clientesPaquete as $cliente) {
    if (!$cliente['tiene_turnos']) {
        $historicos[$cliente['cliente_id']] = $cliente;
    }
}

return [
    'sesiones' => array_values(
        array_filter(
            $clientesSesion,
            fn($c) => $c['tiene_turnos']
        )
    ),

    'paquetes' => array_values(
        array_filter(
            $clientesPaquete,
            fn($c) => $c['tiene_turnos']
        )
    ),

    'historicos' => array_values($historicos),
];
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