<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\User;

class ProfesionalController extends Controller
{
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
}
