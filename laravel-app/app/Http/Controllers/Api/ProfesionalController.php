<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Services\ProfesionalService;
use Illuminate\Http\Request;

class ProfesionalController extends Controller
{   
    protected $profesionalService;

    public function __construct(ProfesionalService $profesionalService)
    {
        $this->profesionalService = $profesionalService;
    }

    public function show($id)
    {
        $user = User::with(['profesional', 'profesional.servicios'])
            ->where('id', $id)
            ->where('role', 'professional')
            ->first();

        if (!$user || !$user->profesional) {
            return response()->json([
                'success' => false,
                'message' => 'Profesional no encontrado'
            ], 404);
        }

        return response()->json([
            'success' => true,
            'data' => $user
        ]);
    }

    public function updateProfile(Request $request)
    {
        $user = auth()->user();

        if (!$user || $user->role !== 'professional') {
            return response()->json([
                'success' => false,
                'message' => 'No autorizado'
            ], 403);
        }

        $data = $request->validate([
            'name' => 'required|string',
            'email' => 'required|email',
            'descripcion' => 'nullable|string',
        ]);

        return response()->json(
            $this->profesionalService->updateProfile($user, $data)
        );
    }
}
