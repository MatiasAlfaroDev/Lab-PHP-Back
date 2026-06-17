<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\ExcepcionService;
use Illuminate\Http\Request;

class ExcepcionController extends Controller
{
    private ExcepcionService $service;

    public function __construct(ExcepcionService $service)
    {
        $this->service = $service;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->service->listar($request->user())
        );
    }

    public function store(Request $request)
    {
        $request->validate([
            'fecha_desde' => 'required|date|after_or_equal:today',
            'fecha_hasta' => 'required|date|after_or_equal:fecha_desde',
        ], [
            'fecha_desde.after_or_equal' =>
                'La fecha de inicio no puede ser anterior a hoy.',
            'fecha_hasta.after_or_equal' =>
                'La fecha de fin no puede ser anterior a la fecha de inicio.',
        ]);

        return response()->json(
            $this->service->crear(
                $request->all(),
                $request->user()
            )
        );
    }

    public function destroy(Request $request, int $id)
    {
        return response()->json(
            $this->service->eliminar(
                $id,
                $request->user()
            )
        );
    }
}    