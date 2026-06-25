<?php

namespace App\Services;

use App\Events\CatalogoActualizado;
use App\Models\Paquete;
use App\Models\Servicio;
use App\Models\Profesional;
use App\Models\User;
use App\Models\ItemPaquete;
use App\Models\CompraItemPaquete;
use App\Models\Reserva;
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

                if ($servicio->eliminado) {
                    throw new \Exception(
                        'El servicio ' . $servicio->nombre . ' fue eliminado'
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

            event(new CatalogoActualizado('paquete', 'creado', $user->id, $paquete->paquete_id));

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
        return Paquete::with('servicios')
            ->whereRaw('eliminado = false')
            ->whereHas('servicios.profesional.user', function ($query) {
                $query->whereRaw('activo = true');
            })
            ->get();
    }

    public function listarMisPaquetes($user)
    {
        return Paquete::with('servicios')
            ->whereRaw('eliminado = false')
            ->whereHas('servicios', function ($query) use ($user) {
                $query->where('profesional_id', $user->id);
            })
            ->get();
    }

    public function obtenerPaquete($id)
    {
        return Paquete::with('servicios')
            ->whereRaw('eliminado = false')
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

            event(new CatalogoActualizado('paquete', 'actualizado', $user->id, (int) $id));

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

        // validar dueño
        foreach ($paquete->items as $item) {
            if ($item->servicio->profesional_id != $user->id) {
                return [
                    'success' => false,
                    'message' => 'No autorizado'
                ];
            }
        }

        // validar reservas activas
        $tieneReservasActivas = Reserva::whereHas('compraItemPaquete.compraPaquete', function ($q) use ($id) {
                $q->where('paquete_id', $id);
            })
            ->whereIn('estado', ['pendiente', 'confirmada', 'pagada', 'en_curso'])
            ->exists();

        if ($tieneReservasActivas) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el paquete porque tiene reservas activas'
            ];
        }
        $tieneSesionesPendientes = CompraItemPaquete::whereHas('compraPaquete', function ($q) use ($id) {
                $q->where('paquete_id', $id);
            })
            ->where('sesiones_restantes', '>', 0)
            ->exists();

        if ($tieneSesionesPendientes) {
            return [
                'success' => false,
                'message' => 'No se puede eliminar el paquete porque aún tiene sesiones sin consumir'
            ];
        }

        // soft delete
        DB::table('paquetes')
        ->where('paquete_id', $id)
        ->update([
            'eliminado' => DB::raw('true')
        ]);

        event(new CatalogoActualizado('paquete', 'eliminado', $user->id, (int) $id));

        return [
            'success' => true,
            'message' => 'Paquete eliminado correctamente'
        ];
    }
}