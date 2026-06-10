<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use App\Models\Pago;
use App\Models\CompraItemPaquete;
use Illuminate\Http\Request;
use App\Services\ReservaService;
use App\Notifications\ReservaNotification;

class ReservaController extends Controller
{
    protected $reservaService;

    public function __construct(ReservaService $reservaService)
    {
        $this->reservaService = $reservaService;
    }
    // POST /reservas

    public function store(Request $request)
    {

        $request->validate([
            'servicio_id' => 'required|integer|exists:servicios,servicio_id',
            'fecha'       => 'required|date_format:Y-m-d',
            'hora'        => 'required|date_format:H:i',
            'compra_item_paquete_id' => 'nullable|integer',
        ]);

        
        // 🔥 Servicio
        $servicio = Servicio::findOrFail($request->servicio_id);

        // 🔥 Modalidad (NO TOCAR tu lógica)
        if ($servicio->modalidad === 'hibrido') {
            $modalidad = $request->modalidad;
        } else {
            $modalidad = $servicio->modalidad;
        }
        if ($request->compra_item_paquete_id) {

            $item = CompraItemPaquete::with('compraPaquete')
                ->findOrFail(
                    $request->compra_item_paquete_id
                );

            if (
                $item->compraPaquete->cliente_id !==
                $request->user()->id
            ) {
                return response()->json([
                    'success' => false,
                    'message' => 'Paquete no válido'
                ], 403);
            }

            if ($item->sesiones_restantes <= 0) {
                return response()->json([
                    'success' => false,
                    'message' => 'No quedan sesiones disponibles'
                ], 400);
            }
        }

        try {

            $reserva = $this->reservaService->crearReserva(
                $request->user(),
                [
                    'servicio_id' => $request->servicio_id,
                    'compra_item_paquete_id' => $request->compra_item_paquete_id,
                    'fecha' => $request->fecha,
                    'hora' => $request->hora,
                    'modalidad' => $modalidad,
                    'estado_videollamada' => $modalidad === 'virtual'
                        ? 'pendiente'
                        : 'no_aplica',
                ]
            );

        } catch (\Exception $e) {

            return response()->json([
                'success' => false,
                'message' => $e->getMessage()
            ], 409);
        }



        // 🔥 Usuarios
        $cliente = $request->user();
        $profesional = User::findOrFail($servicio->profesional_id);

        // =========================================================
        // 🔔 NOTIFICACIÓN 1: PROFESIONAL
        // =========================================================
        $profesional->notify(new ReservaNotification(
            'created',
            "{$cliente->name} realizó una reserva para el servicio: {$servicio->nombre}",
            $reserva->reserva_id
        ));

    // =========================================================
    // 🔔 NOTIFICACIÓN 2: CLIENTE
    // =========================================================
    $cliente->notify(new ReservaNotification(
        'pending',
        "Tu reserva quedó pendiente de aprobación por el profesional: {$profesional->name} para el servicio: {$servicio->nombre}",
        $reserva->reserva_id
    ));

        return response()->json([
            'success' => true,
            'data' => $reserva->load('servicio'),
        ], 201);
    }

    // GET /mis-reservas  (cliente)
    public function misReservas(Request $request)
    {
        $reservas = Reserva::where('cliente_id', $request->user()->id)
            ->with(['servicio', 'pago', 'calificacion'])
            ->orderByDesc('fecha')
            ->orderByDesc('hora')
            ->get()
            ->map(function ($r) {
                if ($r->servicio) {
                    $prof = User::find($r->servicio->profesional_id);
                    $r->servicio->setAttribute('profesional_nombre', $prof?->name ?? 'Profesional');
                }
                return $r;
            });

        return response()->json(['success' => true, 'data' => $reservas]);
    }

    // GET /mi-agenda  (profesional)
    public function agendaProfesional(Request $request)
    {
        $servicioIds = Servicio::where('profesional_id', $request->user()->id)
            ->pluck('servicio_id');

        $reservas = Reserva::whereIn('servicio_id', $servicioIds)
            ->whereNotIn('estado', ['cancelada'])
            ->with(['servicio', 'pago'])
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function ($r) {
                $cliente = User::find($r->cliente_id);
                $r->setAttribute('cliente_nombre', $cliente?->name ?? 'Cliente');
                return $r;
            });

        return response()->json(['success' => true, 'data' => $reservas]);
    }

    // PUT /reservas/{id}/cancelar
    public function cancel(Request $request, $id)
    {
        $reserva = Reserva::findOrFail($id);

        $user = $request->user();
        $isCliente = (int) $reserva->cliente_id === (int) $user->id;
        $isProfesional = (int) $reserva->profesional_id === (int) $user->id;    

        if (in_array($reserva->estado, ['cancelada', 'finalizada', 'no_asistida'])) {
            return response()->json(['success' => false, 'message' => 'No se puede cancelar esta reserva'], 409);
        }

        // MIN_CANCELACION CHECK
        $minHoras = $reserva->servicio->min_cancelacion ?? 0;

        $fechaHoraReserva = \Carbon\Carbon::parse($reserva->fecha . ' ' . substr($reserva->hora, 0, 5));
        $limiteCancelacion = now()->addHours($minHoras);

        if ($fechaHoraReserva->lessThan($limiteCancelacion)) {
            return response()->json([
                'success' => false,
                'message' => "No podés cancelar con menos de {$minHoras} horas de anticipación"
            ], 409);
        }

        if ($reserva->compra_item_paquete_id) {

            $item = CompraItemPaquete::find(
                $reserva->compra_item_paquete_id
            );

            if ($item) {
                $item->increment('sesiones_restantes');
            }
        }

        $reserva->update(['estado' => 'cancelada']);

        return response()->json(['success' => true, 'message' => 'Reserva cancelada']);
    }

    // PUT /reservas/{id}/estado
    public function cambiarEstado(Request $request, $id)
    {
         $reserva = Reserva::with('servicio')->findOrFail($id);

        $estado = $request->estado;

        $estadosValidos = ['confirmada', 'cancelada'];

        if (!in_array($estado, $estadosValidos)) {
            return response()->json([
                'success' => false,
                'message' => 'Estado no válido'
            ], 400);
        }

        if ($reserva->estado !== 'pendiente') {
            return response()->json([
                'success' => false,
                'message' => 'Solo se pueden modificar reservas pendientes'
            ], 400);
        }

        $reserva->estado = $estado;
        $reserva->save();

        if ($estado === 'confirmada') {
            if (!$reserva->compra_item_paquete_id) {

                Pago::create([
                    'fecha' => now(),
                    'monto' => $reserva->servicio->precio,
                    'estado' => 'pendiente',
                    'reserva_id' => $reserva->reserva_id,
                ]);
            } else {

                $item = CompraItemPaquete::find(
                    $reserva->compra_item_paquete_id
                );

                if ($item && $item->sesiones_restantes > 0) {
                    $item->decrement('sesiones_restantes');
                }
            }
        }

        return response()->json([
            'success' => true,
            'data' => $reserva
        ]);
    }

    // GET /reservas/pendientes (profesional)
    public function pendientesProfesional(Request $request)
    {
        $servicioIds = Servicio::where('profesional_id', $request->user()->id)
            ->pluck('servicio_id');

        $reservas = Reserva::whereIn('servicio_id', $servicioIds)
            ->where('estado', 'pendiente')
            ->with('servicio')
            ->orderBy('fecha')
            ->orderBy('hora')
            ->get()
            ->map(function ($r) {
                $cliente = User::find($r->cliente_id);
                $r->cliente_nombre = $cliente?->name ?? 'Cliente';
                return $r;
            });

        return response()->json([ 'success' => true, 'data' => $reservas]);
    }

    // PUT /reservas/{id}/no-asistida
    public function noAsistida($id)
    {
        $reserva = Reserva::findOrFail($id);
        $result = $this->reservaService->noAsistida($reserva);

        return response()->json(
            $result,
            $result['success'] ? 200 : 400
        );
    }

    // POST /videollamada/{id}/estado
    public function actualizarEstadoVideollamada($id, Request $request)
    {
        $request->validate([
            "estado" => "required|in:pendiente,en_curso,finalizada"
        ]);

        $reserva = $this->reservaService->actualizarEstadoVideollamada(
            $id,
            $request->estado
        );

        return response()->json([
            "success" => true,
            "data" => $reserva
        ]);
    }

    // PUT /reservas/{id}/reprogramar
    public function reprogramar(Request $request, int $id)
    {
        $request->validate([
            'fecha' => 'required|date',
            'hora'  => 'required'
        ]);

        $result = $this->reservaService->reprogramar(
            $id,
            $request->fecha,
            $request->hora
        );

        if (!$result['success']) {
            return response()->json($result, 409);
        }

        return response()->json($result, 200);
    }

    public function show($id)
    {
        $reserva = $this->reservaService->getById($id);

        if (!$reserva) {
            return response()->json([
                'success' => false,
                'message' => 'Reserva no encontrada'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $reserva
        ]);
    }
}