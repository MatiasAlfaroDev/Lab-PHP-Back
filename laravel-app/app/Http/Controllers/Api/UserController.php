<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        // 1. VALIDACIÓN (controlada para frontend)
        $validator = Validator::make($request->all(), [
            'nombre' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:6',
            'rol' => 'required|in:cliente,profesional,admin'
        ], [
            'email.unique' => 'El email ya está registrado'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Errores de validación',
                'errors' => $validator->errors()
            ], 422);
        }

        // 2. LLAMAR SERVICE
        $result = $this->userService->register($request->all());

        // 3. RESPUESTA FINAL
        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result, 201);
    }
}