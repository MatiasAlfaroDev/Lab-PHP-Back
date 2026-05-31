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

    public function index()
    {
        return response()->json($this->servicioService->listarTodos());
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'nombre'          => 'required|string|max:255',
            'descripcion'     => 'required|string',
            'modalidad'       => 'required|string|max:255',
            'tipo'            => 'required|string|max:100',
            'precio'          => 'required|numeric|min:0',
            'duracion'        => 'required|integer|min:1',
            'pausa'           => 'required|integer|min:0',
            'min_cancelacion' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $response = $this->servicioService->nuevoServicio($request->all(), $request->user());

        return response()->json($response, $response['success'] ? 201 : 403);
    }

    public function update(Request $request, $id)
    {
        $validator = Validator::make($request->all(), [
            'nombre'          => 'sometimes|required|string|max:255',
            'descripcion'     => 'sometimes|required|string',
            'modalidad'       => 'sometimes|required|string|max:255',
            'tipo'            => 'sometimes|required|string|max:100',
            'precio'          => 'sometimes|required|numeric|min:0',
            'duracion'        => 'sometimes|required|integer|min:1',
            'pausa'           => 'sometimes|required|integer|min:0',
            'min_cancelacion' => 'nullable|integer|min:0',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $response = $this->servicioService->actualizarServicio((int) $id, $request->all(), $request->user());

        return response()->json($response, $response['success'] ? 200 : 403);
    }

    public function destroy(Request $request, $id)
    {
        $response = $this->servicioService->eliminarServicio((int) $id, $request->user());

        return response()->json($response, $response['success'] ? 200 : 403);
    }

    public function misServicios(Request $request)
    {
        $response = $this->servicioService->obtenerServiciosProfesional($request->user());

        return response()->json($response, $response['success'] ? 200 : 403);
    }
}
