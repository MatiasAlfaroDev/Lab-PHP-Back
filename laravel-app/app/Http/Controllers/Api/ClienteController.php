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

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'client') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
        ]);

        return response()->json(
            $this->clienteService->updateProfile($user, $data)
        );
    }
}