<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\UserService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Laravel\Socialite\Facades\Socialite;

class UserController extends Controller
{
    private $userService;

    public function __construct(UserService $userService)
    {
        $this->userService = $userService;
    }

    public function register(Request $request)
    {
        // 1. VALIDACIÓN 
        $validator = Validator::make($request->all(), [
            'name' => 'required|string',
            'email' => 'required|email|unique:users,email',
            'password' => 'required|min:8',
            'role' => 'required|in:client,professional,admin'
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

        $result = $this->userService->register($request->all());

        if (!$result['success']) {
            return response()->json($result, 500);
        }

        return response()->json($result, 201);
    }

    public function login(Request $request)
    {
        $data = $request->validate([
            'email' => 'required|email',
            'password' => 'required'
        ]);

        $response = $this->userService->login($data);

        return response()->json(
            $response,
            $response['success'] ? 200 : 401
        );
    }

    public function me(Request $request)
    {
        return response()->json($request->user());
    }

    public function logout(Request $request)
    {
        $request->user()->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Logout correcto'
        ]);
    }

    public function redirectGoogle(Request $request)
{
    return Socialite::driver('google')
        ->with(['state' => $request->role]) 
        ->redirect();
}

    public function googleCallback(Request $request)
    {
        $response = $this->userService->handleGoogleLogin($request->input('state'));

        return redirect(
            'http://localhost:5173/auth/google/callback?' .
            http_build_query([
                'token' => $response['token'],
                'id' => $response['user']->id,
                'name' => $response['user']->name,
                'email' => $response['user']->email,
                'role' => $response['user']->role,
            ])
        );
    }

    public function updatePassword(Request $request)
    {
        $user = auth()->user();

        $data = $request->validate([
            'current_password' => 'required',
            'password' => 'required|confirmed|min:8',
        ]);

        return response()->json(
            $this->userService->updatePassword($user, $data)
        );
    }
}