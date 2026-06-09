<?php
namespace App\Services;

use Illuminate\Support\Facades\DB;

class ProfesionalService
{
    public function updateProfile($user, array $data)
    {
        DB::beginTransaction();

        try {
            $user->update([
                'name' => $data['name'],
                'email' => $data['email'],
            ]);

            if ($user->profesional) {
                $user->profesional->update([
                    'descripcion' => $data['descripcion'] ?? '',
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Perfil actualizado correctamente',
                'data' => $user->load('profesional')
            ];

        } catch (\Exception $e) {
            DB::rollBack();

            return [
                'success' => false,
                'message' => 'Error al actualizar perfil'
            ];
        }
    }
}