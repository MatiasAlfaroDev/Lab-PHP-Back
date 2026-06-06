<?php
namespace App\Services;

class NotificationService
{
    public function listarTodas($usuario)
    {
        $notificaciones = $usuario
            ->notifications()
            ->latest()
            ->get();

        return [
            'success' => true,
            'data' => $notificaciones
        ];
    }

    public function listarNoLeidas($usuario)
    {
        $notificaciones = $usuario
            ->unreadNotifications()
            ->latest()
            ->get();

        return [
            'success' => true,
            'data' => $notificaciones
        ];
    }

    public function marcarComoLeida($id, $usuario)
    {
        $notificacion = $usuario
            ->notifications()
            ->where('id', $id)
            ->first();

        if (!$notificacion) {
            return [
                'success' => false,
                'message' => 'Notificación no encontrada'
            ];
        }

        $notificacion->markAsRead();

        return [
            'success' => true,
            'message' => 'Notificación marcada como leída'
        ];
    }

    public function marcarTodasComoLeidas($usuario)
    {
        $usuario
            ->unreadNotifications()
            ->markAsRead();

        return [
            'success' => true,  
            'message' => 'Todas las notificaciones fueron marcadas como leídas'
        ];
    }
}