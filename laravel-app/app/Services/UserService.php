<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserService
{
    public function register(array $data)
    {
        DB::beginTransaction();

        try {

            // 1. Crear usuario
            $user = User::create([
                'nombre' => $data['nombre'],
                'email' => $data['email'],
                'rol' => strtolower($data['rol']),
                'password' => Hash::make($data['password'])
            ]);

            // 2. Crear perfil según rol
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

            // 3. Respuesta para frontend
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
}