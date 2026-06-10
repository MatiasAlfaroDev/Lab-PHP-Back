<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Services\NotificationService;

class NotificationController extends Controller
{
    private $servicio;

    public function __construct(NotificationService $servicio)
    {
        $this->servicio = $servicio;
    }

    public function index(Request $request)
    {
        return response()->json(
            $this->servicio->listarTodas($request->user())
        );
    }

    public function noLeidas(Request $request)
    {
        return response()->json(
            $this->servicio->listarNoLeidas($request->user())
        );
    }

    public function leer($id, Request $request)
    {
        return response()->json(
            $this->servicio->marcarComoLeida($id, $request->user())
        );
    }

    public function leerTodas(Request $request)
    {
        return response()->json(
            $this->servicio->marcarTodasComoLeidas($request->user())
        );
    }
}