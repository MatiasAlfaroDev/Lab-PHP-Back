<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\CompraPaqueteService;

class CompraPaqueteController extends Controller
{
    private $service;

    public function __construct(
        CompraPaqueteService $service
    ) {
        $this->service = $service;
    }

    public function store(Request $request)
    {
        $request->validate([
            'paquete_id' => 'required|integer'
        ]);

        return response()->json(
            $this->service->comprarPaquete(
                $request->user(),
                $request->paquete_id
            )
        );
    }

    public function misPaquetes(Request $request)
    {
        return response()->json(
            $this->service->misPaquetes(
                $request->user()
            )
        );
    }

    public function show($id, Request $request)
    {
        $compra = $this->service->obtenerCompra(
            $id,
            $request->user()
        );

        if (!$compra) {
            return response()->json([
                'success' => false,
                'message' => 'Compra no encontrada'
            ], 404);
        }

        return response()->json($compra);
    }
}