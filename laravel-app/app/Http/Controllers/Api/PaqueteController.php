<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use App\Services\PaqueteService;

class PaqueteController extends Controller
{
    private $paqueteService;

    public function __construct(PaqueteService $paqueteService)
    {
        $this->paqueteService = $paqueteService;
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [

            'nombre' => 'required|string|max:255',

            'descripcion' => 'nullable|string',

            'servicios' => 'required|array|min:1',

            'servicios.*.servicio_id' => 'required|integer',

            'servicios.*.cantidad_sesiones' =>
                'required|integer|min:1'

        ]);

        if ($validator->fails()) {

            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        $response = $this->paqueteService
            ->crearPaquete($request->all());

        return response()->json(
            $response,
            $response['success'] ? 201 : 500
        );
    }

    public function index()
    {
        return response()->json(
            $this->paqueteService->listarPaquetes()
        );
    }

    public function show($id)
    {
        $paquete = $this->paqueteService
            ->obtenerPaquete($id);

        if (!$paquete) {
            return response()->json([
                'success' => false,
                'message' => 'Paquete no encontrado'
            ], 404);
        }

        return response()->json($paquete);
    }

    public function destroy($id)
    {
        $response = $this->paqueteService
            ->eliminarPaquete($id);

        return response()->json(
            $response,
            $response['success'] ? 200 : 404
        );
    }
}