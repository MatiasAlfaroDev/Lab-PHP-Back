<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\CalificacionService;
use Illuminate\Http\Request;

class CalificacionController extends Controller
{
    public function __construct(
        private CalificacionService $calificacionService
    ) {}

    public function crear(Request $request, int $reservaId)
    {
        return response()->json(
            $this->calificacionService->crear(
                $request->user(),
                $reservaId,
                $request->all()
            )
        );
    }

    public function listarPorProfesional($id)
    {
        return response()->json(
            $this->calificacionService->listarPorProfesional($id)
        );
    }
}