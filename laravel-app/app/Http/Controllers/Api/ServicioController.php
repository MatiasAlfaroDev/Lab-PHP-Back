<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\ServicioService;

class ServicioController extends Controller
{
    private $servicioService;

    public function __construct(ServicioService $servicioService)
    {
        $this->servicioService = $servicioService;
    }

    // Listar todos los servicios (público)
    public function index()
    {
        return response()->json($this->servicioService->listarTodos());
    }

    // Crear nuevo servicio
    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string|max:255',
            'descripcion' => 'required|string',
            'modalidad' => 'required|string|max:255',
            'tipo' => 'required|string|max:100',
            'precio' => 'required|numeric|min:0',
            'duracion' => 'required|integer|min:1',
            'pausa' => 'required|integer|min:0'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = $this->servicioService->nuevoServicio(
            $request->all(),
            $request->user()
        );

        return response()->json(
            $response,
            $response['success'] ? 201 : 403
        );
    }

    // Obtener servicios del profesional logueado
    public function misServicios(Request $request)
    {
        $response = $this->servicioService
            ->obtenerServiciosProfesional($request->user());

        return response()->json(
            $response,
            $response['success'] ? 200 : 403
        );
    }
}