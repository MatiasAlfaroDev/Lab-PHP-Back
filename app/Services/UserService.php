<?php

namespace App\Services;

use App\Models\User;
use App\Models\Cliente;
use App\Models\Profesional;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Laravel\Socialite\Facades\Socialite;
use Illuminate\Support\Str;
use App\Notifications\PasswordGeneratedNotification;
use App\Notifications\EmailVerificationCodeNotification;

class UserService
{
    private function generateVerificationCode(User $user)
    {
        $code = (string) random_int(100000, 999999);

        $user->update([
            'email_verification_code' => $code,
            'email_verification_expires_at' => now()->addMinutes(15),
        ]);

        $user->notify(new EmailVerificationCodeNotification($code));
    }

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
                    'descripcion' => ''
                ]);
            }

            if ($user->role === 'client') {
                Cliente::create([
                    'user_id' => $user->id
                ]);
            }

            DB::commit();

            $this->generateVerificationCode($user);

            return [
                'success' => true,
                'message' => 'Usuario creado correctamente. Revisa tu correo para verificar tu cuenta.',
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

    public function verifyEmail(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }

        if ($user->email_verified_at) {
            return [
                'success' => false,
                'message' => 'El correo ya fue verificado'
            ];
        }

        if (
            !$user->email_verification_code ||
            $user->email_verification_code !== $data['code'] ||
            !$user->email_verification_expires_at ||
            $user->email_verification_expires_at->isPast()
        ) {
            return [
                'success' => false,
                'message' => 'Código de verificación inválido o vencido'
            ];
        }

        $user->update([
            'email_verified_at' => now(),
            'email_verification_code' => null,
            'email_verification_expires_at' => null,
        ]);

        $token = $user->createToken('auth_token')->plainTextToken;

        return [
            'success' => true,
            'message' => 'Correo verificado correctamente',
            'token' => $token,
            'user' => [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'role' => $user->role
            ]
        ];
    }

    public function resendVerificationCode(array $data)
    {
        $user = User::where('email', $data['email'])->first();

        if (!$user) {
            return [
                'success' => false,
                'message' => 'Usuario no encontrado'
            ];
        }

        if ($user->email_verified_at) {
            return [
                'success' => false,
                'message' => 'El correo ya fue verificado'
            ];
        }

        $this->generateVerificationCode($user);

        return [
            'success' => true,
            'message' => 'Código de verificación reenviado'
        ];
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

        if (!$user->activo) {
            return [
                'success' => false,
                'message' => 'Usuario bloqueado'
            ];
        }

        if (!$user->email_verified_at) {
            return [
                'success' => false,
                'message' => 'Debes verificar tu correo electrónico antes de iniciar sesión',
                'email_not_verified' => true
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

        $plainPassword = Str::password(12);

        if (!$user) {
            $user = User::create([
                'name' => $googleUser->name,
                'email' => $googleUser->email,
                'role' => $role,
                'password' => bcrypt($plainPassword),
                'email_verified_at' => now(),
            ]);

            if ($role === 'professional') {
                Profesional::create(['user_id' => $user->id]);
            } else {
                Cliente::create(['user_id' => $user->id]);
            }

            $user->notify(
            new PasswordGeneratedNotification($plainPassword)
            );
        } else {
             // BLOQUEO
        if (!$user->activo) {
            return [
                'success' => false,
                'message' => 'Usuario bloqueado'
            ];
        }


        }

       
        //LOGIN OK
        $token = $user->createToken('google')->plainTextToken;

        return [
            'success' => true,
            'user' => $user,
            'token' => $token
        ];
    }
    public function updatePassword($user, array $data)
    {
        if (!Hash::check($data['current_password'], $user->password)) {
            return [
                'success' => false,
                'message' => 'Contraseña actual incorrecta'
            ];
        }

        if (strlen($data['password']) < 8) {
            return [
                'success' => false,
                'message' => 'La contraseña debe tener al menos 8 caracteres'
            ];
        }

        $user->update([
            'password' => Hash::make($data['password'])
        ]);

        return [
            'success' => true,
            'message' => 'Contraseña actualizada correctamente'
        ];
    }
}