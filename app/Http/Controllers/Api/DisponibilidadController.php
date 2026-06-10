<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\DisponibilidadService;
use Illuminate\Http\Request;

class DisponibilidadController extends Controller
{
    private DisponibilidadService $service;

    public function __construct(DisponibilidadService $service)
    {
        $this->service = $service;
    }

    // GET /api/servicios/{id}/dias-disponibles  (público)
    public function diasDisponibles($servicioId)
    {
        $result = $this->service->getDiasConDisponibilidad((int)$servicioId);
        return response()->json($result);
    }

    // GET /api/servicios/{id}/disponibilidad  (público)
    public function byServicio($servicioId)
    {
        $result = $this->service->getByServicio((int)$servicioId);
        return response()->json($result);
    }

    // GET /api/servicios/{id}/slots?fecha=YYYY-MM-DD  (público)
    public function slots(Request $request, $servicioId)
    {
        $fecha  = $request->query('fecha', now()->toDateString());
        $result = $this->service->getSlotsDisponibles((int)$servicioId, $fecha);
        return response()->json($result);
    }

    // PUT /api/servicios/{id}/disponibilidad  (protegido)
    public function bulkUpdate(Request $request, $servicioId)
    {
        $request->validate([
            'disponibilidades'               => 'present|array',
            'disponibilidades.*.dia_semana'  => 'required|in:lunes,martes,miercoles,jueves,viernes,sabado,domingo',
            'disponibilidades.*.hora_inicio' => 'required|date_format:H:i',
            'disponibilidades.*.hora_fin'    => 'required|date_format:H:i',
        ]);

        $result = $this->service->bulkUpdate(
            (int)$servicioId,
            $request->disponibilidades,
            $request->user()
        );

        return response()->json($result, $result['success'] ? 200 : 403);
    }
}
