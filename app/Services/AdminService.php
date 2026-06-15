<?php

namespace App\Services;

use App\Models\User;
use App\Models\Reserva;
use App\Models\Pago;
use App\Models\Servicio;
use App\Models\CompraItemPaquete;
use Carbon\Carbon;
use App\Notifications\ReservaNotification;
use Illuminate\Support\Facades\DB;
use App\Notifications\UserBlockedNotification;

class AdminService
{
    public function getDashboard(): array
    {
        return [
            'kpis' => $this->getKpis(),
            'recent_activity' => $this->getRecentActivity(),
            'reservas_por_dia' => $this->getReservasPorDia(),
            'servicios_por_tipo' => $this->getReservasPorTipoServicio(),
            'servicios_mas_reservados' => $this->getServiciosMasReservados(),
        ];
    }

    private function getKpis(): array
    {
        return [
            'users' => User::count(),
            'clients' => User::where('role', 'client')->count(),
            'professionals' => User::where('role', 'professional')->count(),
            'reservas' => Reserva::count(),
        ];
    }

    private function getRecentActivity(): array
    {
        return Reserva::with(['cliente', 'servicio'])
            ->orderByDesc('reserva_id')
            ->limit(5)
            ->get()
            ->map(function ($r) {

                $cliente = $r->cliente?->user?->name ?? 'Cliente';
                $servicio = $r->servicio?->nombre ?? 'Servicio';

                return [
                    'texto' => "{$cliente} reservó {$servicio}",
                    'estado' => $r->estado,
                    'fecha' => $r->fecha,
                    'hora' => substr($r->hora, 0, 5),
                ];
            })
            ->toArray();
    }

    private function getReservasPorDia(): array
    {
        $desde = now()->subDays(29)->toDateString();
        $hasta = now()->toDateString();

        $reservas = Reserva::selectRaw("
                fecha,
                SUM(CASE WHEN estado = 'finalizada' THEN 1 ELSE 0 END) as finalizadas,
                SUM(CASE WHEN estado = 'no_asistida' THEN 1 ELSE 0 END) as no_asistidas,
                SUM(CASE WHEN estado = 'cancelada' THEN 1 ELSE 0 END) as canceladas
            ")
            ->whereBetween('fecha', [$desde, $hasta])
            ->groupBy('fecha')
            ->orderBy('fecha')
            ->get()
            ->keyBy('fecha');

        $resultado = [];

        for ($i = 29; $i >= 0; $i--) {
            $fecha = now()->subDays($i)->toDateString();

            $dia = $reservas->get($fecha);

            $resultado[] = [
                'fecha' => $fecha,
                'finalizadas' => $dia?->finalizadas ?? 0,
                'no_asistidas' => $dia?->no_asistidas ?? 0,
                'canceladas' => $dia?->canceladas ?? 0,
            ];
        }

        return $resultado;
    }

    public function getReservasPorTipoServicio(): array
    {
        return DB::table('reservas')
            ->join('servicios', 'servicios.servicio_id', '=', 'reservas.servicio_id')
            ->select(
                'servicios.tipo as tipo',
                DB::raw('COUNT(*) as total')
            )
            ->groupBy('servicios.tipo')
            ->orderByDesc('total')
            ->get()
            ->toArray();
    }

    public function getServiciosMasReservados(): array
    {
       return DB::table('reservas')
        ->join('servicios', 'reservas.servicio_id', '=', 'servicios.servicio_id')
        ->select(
            'servicios.servicio_id',
            'servicios.nombre',
            DB::raw('COUNT(*) as total')
        )
        ->groupBy('servicios.servicio_id', 'servicios.nombre')
        ->orderByDesc('total')
        ->limit(5)
        ->get()
        ->toArray();
    }

    public function getClients(): array
    {
        $sessions = Reserva::select('cliente_id', DB::raw('COUNT(*) as total'))
            ->groupBy('cliente_id')
            ->pluck('total', 'cliente_id');

        return User::where('role', 'client')
            ->get()
            ->map(function ($u) use ($sessions) {

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'role' => $u->role,
                    'sessions' => $sessions[$u->id] ?? 0,
                    'joined' => $u->created_at?->toDateString(),
                    'activo' => $u ->activo
                ];
            })
            ->toArray();
    }

    public function getProfessionals(): array
    {
        $sessions = Reserva::join('servicios', 'reservas.servicio_id', '=', 'servicios.servicio_id')
            ->select('servicios.profesional_id', DB::raw('COUNT(*) as total'))
            ->where('reservas.estado', 'finalizada')
            ->groupBy('servicios.profesional_id')
            ->pluck('total', 'servicios.profesional_id');

        return User::where('role', 'professional')
            ->get()
            ->map(function ($u) use ($sessions) {

                return [
                    'id' => $u->id,
                    'name' => $u->name,
                    'email' => $u->email,
                    'sessions' => $sessions[$u->id] ?? 0,
                    'joined' => $u->created_at?->toDateString(),
                    'activo' => $u ->activo
                ];
            })
            ->toArray();
    }

    public function getAllPayments(): array
    {
        $pagos = Pago::with([
            'reserva.cliente.user',
            'reserva.servicio.profesional.user',
            'compraPaquete.cliente.user',
             'compraPaquete.paquete.servicios.profesional.user',
        ])->get();

        return $pagos->map(function ($pago) {

            $esReserva = $pago->reserva_id !== null;

            return [
                'fecha' => $pago->fecha,

                'de' => $esReserva
                    ? $pago->reserva->cliente->user->name
                    : $pago->compraPaquete->cliente->user->name,

                'para' => $esReserva
                    ? ($pago->reserva->servicio?->profesional?->user?->name ?? '-')
                    : ($pago->compraPaquete?->paquete
                        ?->servicios
                        ?->first()
                        ?->profesional
                        ?->user
                        ?->name ?? '-'),

                'servicio' => $esReserva
                    ? $pago->reserva->servicio->nombre
                    : 'Paquete de sesiones',

                'total' => $pago->monto,
                'metodo' => $pago->metodo,
                'estado' => $pago->estado,
            ];
        })->toArray();
    }

    public function getPaymentsSummary(): array
    {
        $pagos = Pago::all();

        $total = $pagos->sum('monto');

        $pagado = $pagos
            ->where('estado', 'aprobado')
            ->sum('monto');

        $pendiente = $pagos
            ->where('estado', 'pendiente')
            ->sum('monto');

        return [
            'total' => (float) $total,
            'pagado' => (float) $pagado,
            'pendiente' => (float) $pendiente,
        ];
    }


    public function cambiarEstadoUsuario(int $userId): array
{
    $user = User::find($userId);

    if (!$user) {
        return [
            'success' => false,
            'message' => 'Usuario no encontrado'
        ];
    }

    DB::table('users')
        ->where('id', $userId)
        ->update([
            'activo' => DB::raw('NOT activo')
        ]);

    $nuevoEstado = DB::table('users')
        ->where('id', $userId)
        ->value('activo');

    if (!$nuevoEstado) {

        $reservas = Reserva::with('servicio')
            ->where('cliente_id', $userId)
            ->whereIn('estado', [
                'pendiente',
                'confirmada',
                'pagada'
            ])
            ->get()
            ->filter(function ($reserva) {
                return \Carbon\Carbon::parse(
                    $reserva->fecha . ' ' . substr($reserva->hora, 0, 5)
                )->greaterThan(now());
            });

        $detalleReservas = [];

        foreach ($reservas as $reserva) {

            // Devolver sesión al paquete si corresponde
            if ($reserva->compra_item_paquete_id) {

                $item = CompraItemPaquete::find(
                    $reserva->compra_item_paquete_id
                );

                if ($item) {
                    $item->increment('sesiones_restantes');
                }
            }

             $servicio = $reserva->servicio;

            // Cancelar reserva
            $reserva->update([
                'estado' => 'cancelada'
            ]);

            if ($servicio) {

                $detalleReservas[] =
                    "• {$servicio->nombre} - {$reserva->fecha} {$reserva->hora}";

                // Notificar al profesional
                if ($servicio->profesional_id) {

                    $profesional = User::find($servicio->profesional_id);

                    if ($profesional) {

                        $profesional->notify(
                            new ReservaNotification(
                                'Reserva Cancelada',
                                "La reserva de {$user->name} para el servicio {$servicio->nombre} fue cancelada porque la cuenta del cliente fue bloqueada por un administrador.",
                                $reserva->fecha,
                                $reserva->hora
                            )
                        );
                    }
                }
            }
        }

        $mensaje = 'Tu cuenta fue bloqueada por un administrador.';

        if (count($detalleReservas) > 0) {

            $mensaje .= "\n\nAdemás, se cancelaron las siguientes reservas futuras:\n\n";
            $mensaje .= implode("\n", $detalleReservas);

        } else {

            $mensaje .= "\n\nNo tenías reservas futuras pendientes.";
        }

        $user->notify(
            new UserBlockedNotification($mensaje)
        );

    } else {

        $user->notify(
            new UserBlockedNotification(
                'Tu cuenta fue reactivada. Ya podés volver a ingresar.'
            )
        );
    }

    return [
        'success' => true,
        'message' => $nuevoEstado ? 'Usuario activado' : 'Usuario bloqueado',
        'activo' => $nuevoEstado
    ];
}
}