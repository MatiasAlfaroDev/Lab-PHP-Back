<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;

class UserService
{
    public function register(array $data)
    {
        DB::beginTransaction();

        try {

            // Crear usuario
            $user = User::create([
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'rol' => strtolower($data['rol']),
                'password' => Hash::make($data['password'])
            ]);

            // Crear perfil según rol
            if ($user->rol === 'profesional') {
                Profesional::create([
                    'user_id' => $user->id,
                    'descripcion' => '',
                    'ubicacion' => ''
                ]);
            }

            if ($user->rol === 'cliente') {
                Cliente::create([
                    'user_id' => $user->id
                ]);
            }

            DB::commit();

            // Respuesta para frontend
            return [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'data' => [
                    'id' => $user->id,
                    'nombre' => $user->nombre,
                    'email' => $user->email,
                    'rol' => $user->rol
                ]
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al crear usuario'
            ];
        }
    }

    public function login(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user || !Hash::check($data['password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Credenciales inválidas'
            ];
        }

        // crear token Sanctum
        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Login correcto',
            'data' => [
                'id' => $user->id,
                'nombre' => $user->nombre,
                'email' => $user->email,
                'rol' => $user->rol,
                'token' => $token
            ]
        ];
    }

    public function handleGoogleLogin($rol)
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = User::create([
                'nombre' => $googleUser->name,
                'email' => $googleUser->email,
                'rol' => $rol
            ]);

            if ($rol === 'profesional') {
                Profesional::create(['user_id' => $user->id]);
            } else {
                Cliente::create(['user_id' => $user->id]);
            }
        }

        $token = $user->createToken('google')->plainTextToken;

        return [
            'user' => $user,
            'token' => $token
        ];
    }
}