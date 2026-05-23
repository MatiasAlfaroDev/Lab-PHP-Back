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
                'name' => $data['name'],
                'email' => $data['email'],
                'role' => strtolower($data['role']),
                'password' => Hash::make($data['password'])
            ]);

            // Crear perfil según rol
            if ($user->role === 'professional') {
                Profesional::create([
                    'user_id' => $user->id,
                    'descripcion' => '',
                    'ubicacion' => ''
                ]);
            }

            if ($user->role === 'client') {
                Cliente::create([
                    'user_id' => $user->id
                ]);
            }

            DB::commit();

            // Respuesta para frontend
           $token = $user->createToken('auth_token')->plainTextToken;

            return [
                'success' => true,
                'message' => 'Usuario creado correctamente',
                'token' => $token,
                'user' => [
                    'id' => $user->id,
                    'name' => $user->name,
                    'email' => $user->email,
                    'role' => $user->role
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
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
            
        ];
    }

    public function handleGoogleLogin($role)
    {
        $googleUser = Socialite::driver('google')->stateless()->user();

        $user = User::where('email', $googleUser->email)->first();

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'role' => $role,
                'password' => bcrypt(uniqid())
            ]);

            if ($role === 'professional') {
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