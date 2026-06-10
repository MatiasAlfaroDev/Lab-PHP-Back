<?php

namespace App\Services;

use App\Models\Paquete;
use App\Models\Servicio;
use App\Models\ItemPaquete;
use Illuminate\Support\Facades\DB;

class PaqueteService
{
    public function crearPaquete(array $data, $user)
    {
        DB::beginTransaction();

        try {
            // Crear el paquete
            $paquete = Paquete::create([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'precio_total' => $data['precio_total']
            ]);


            foreach ($data['servicios'] as $item) {

                $servicio = Servicio::find($item['servicio_id']);

                if (!$servicio) {
                    throw new \Exception(
                        'Servicio con ID ' . $item['servicio_id'] . ' no encontrado'
                    );
                }

                if ($servicio->profesional_id != $user->id) {
                    throw new \Exception(
                        'No podés usar servicios de otro profesional'
                    );
                }

                ItemPaquete::create([
                    'paquete_id' => $paquete->paquete_id,
                    'servicio_id' => $item['servicio_id'],
                    'cantidad_sesiones' => $item['cantidad_sesiones']
                ]);

            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Paquete creado correctamente',
                'paquete_id' => $paquete->paquete_id
            ];

        } catch (\Exception $e) {
            DB::rollBack();
            return [
                'success' => false,
                'message' => 'Error al crear el paquete: ' . $e->getMessage()
            ];
        }

    }

    public function listarPaquetes()
    {
        return Paquete::with('servicios')->get();
    }

    public function listarMisPaquetes($user)
    {
        return Paquete::with('servicios')
            ->whereHas('servicios', function ($query) use ($user) {
                $query->where('profesional_id', $user->id);
            })
            ->get();
    }

    public function obtenerPaquete($id)
    {
        return Paquete::with('servicios')
            ->find($id);
    }

    public function actualizarPaquete($id, array $data, $user)
    {
        DB::beginTransaction();

        try {

            $paquete = Paquete::with('items.servicio')
                ->find($id);

            if (!$paquete) {
                return [
                    'success' => false,
                    'message' => 'Paquete no encontrado'
                ];
            }

            $esDueno = true;

            foreach ($paquete->items as $item) {

                if ($item->servicio->profesional_id != $user->id) {
                    $esDueno = false;
                    break;
                }
            }

            if (!$esDueno) {
                return [
                    'success' => false,
                    'message' => 'No autorizado'
                ];
            }

            $paquete->update([
                'nombre' => $data['nombre'],
                'descripcion' => $data['descripcion'],
                'precio_total' => $data['precio_total']
            ]);

            // borrar items viejos
            ItemPaquete::where(
                'paquete_id',
                $id
            )->delete();

            // crear nuevos
            foreach ($data['servicios'] as $item) {

                $servicio = Servicio::find($item['servicio_id']);

                if (!$servicio) {
                    throw new \Exception(
                        'Servicio con ID ' . $item['servicio_id'] . ' no encontrado'
                    );
                }

                if ($servicio->profesional_id != $user->id) {
                    throw new \Exception(
                        'No podés usar servicios de otro profesional'
                    );
                }

                ItemPaquete::create([
                    'paquete_id' => $id,
                    'servicio_id' => $item['servicio_id'],
                    'cantidad_sesiones' => $item['cantidad_sesiones']
                ]);
            }

            DB::commit();

            return [
                'success' => true,
                'message' => 'Paquete actualizado'
            ];

        } catch (\Exception $e) {

            DB::rollBack();

            return [
                'success' => false,
                'message' => $e->getMessage()
            ];
        }
    }

    public function eliminarPaquete($id, $user)
    {
        $paquete = Paquete::with('items.servicio')
            ->find($id);

        if (!$paquete) {
            return [
                'success' => false,
                'message' => 'Paquete no encontrado'
            ];
        }

        $esDueno = true;

        foreach ($paquete->items as $item) {

            if ($item->servicio->profesional_id != $user->id) {
                $esDueno = false;
                break;
            }
        }

        if (!$esDueno) {
            return [
                'success' => false,
                'message' => 'No autorizado'
            ];
        }

        $paquete->delete();

        return [
            'success' => true,
            'message' => 'Paquete eliminado correctamente'
        ];
    }
}