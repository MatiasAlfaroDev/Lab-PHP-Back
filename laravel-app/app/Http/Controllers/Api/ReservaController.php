<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Reserva;
use App\Models\Servicio;
use App\Models\User;
use Illuminate\Http\Request;

class ReservaController extends Controller
{
    // POST /reservas
    public function store(Request $request)
    {
        $request->validate([
            'servicio_id' => 'required|integer|exists:servicios,servicio_id',
            'fecha'       => 'required|date_format:Y-m-d',
            'hora'        => 'required|date_format:H:i',
        ]);

        $reserva = Reserva::create([
            'cliente_id'  => $request->user()->id,
            'servicio_id' => $request->servicio_id,
            'fecha'       => $request->fecha,
            'hora'        => $request->hora . ':00',
            'estado'      => 'pendiente',
        ]);

        return response()->json([
            'success' => true,
            'data'    => $reserva->load('servicio'),
        ], 201);
    }

    // GET /mis-reservas  (cliente)
    public function misReservas(Request $request)
    {
        $reservas = Reserva::where('cliente_id', $request->user()->id)
            ->with(['servicio', 'pago'])
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
            ->with('servicio')
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

        if ((int) $reserva->cliente_id !== (int) $request->user()->id) {
            return response()->json(['success' => false, 'message' => 'No autorizado'], 403);
        }

        if (in_array($reserva->estado, ['cancelada', 'finalizada', 'no_asistida'])) {
            return response()->json(['success' => false, 'message' => 'No se puede cancelar esta reserva'], 409);
        }

        $reserva->update(['estado' => 'cancelada']);

        return response()->json(['success' => true, 'message' => 'Reserva cancelada']);
    }
}
