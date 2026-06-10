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