<?php

namespace App\Http\Controllers\Api;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Services\ClienteService;

class ClienteController extends Controller
{
    protected $clienteService;

    public function __construct(ClienteService $clienteService)
    {
        $this->clienteService = $clienteService;
    }

    public function index(Request $request)
    {
        $user = auth()->user();

        $profesionalId = $user->profesional->user_id;

        $clientes = $this->clienteService
            ->getClientesDelProfesional($profesionalId);

        return response()->json($clientes);
    }
}