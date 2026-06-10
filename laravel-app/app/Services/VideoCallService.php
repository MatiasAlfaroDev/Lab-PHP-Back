<?php

namespace App\Services;

use Firebase\JWT\JWT;

class VideoCallService
{
    public function generarToken($reserva, $user)
    {
        $apiKey = env('LIVEKIT_API_KEY');
        $apiSecret = env('LIVEKIT_API_SECRET');
        $livekitUrl = env('LIVEKIT_URL');

        // 🧠 1 room por reserva (esto es correcto)
        $roomName = "reserva_" . $reserva->reserva_id;

        $now = time();

        // ⏱️ token válido por 1 hora (ok para tu sistema)
        $exp = $now + 3600;

        // 🧑 identidad real del usuario (IMPORTANTE)
        $identity = (string) $user->id;

        // 🎥 permisos dentro de la sala
        $videoGrants = [
            "roomJoin" => true,
            "room" => $roomName,
            "canPublish" => true,
            "canSubscribe" => true,
        ];

        // 🔐 payload JWT LiveKit
        $payload = [
            "iss" => $apiKey,
            "sub" => $identity,
            "iat" => $now,
            "exp" => $exp,
            "video" => $videoGrants,
        ];

        // 🔑 generar token
        $token = JWT::encode($payload, $apiSecret, 'HS256');

        return [
            "token" => $token,
            "url" => $livekitUrl,
            "room" => $roomName,
            "identity" => $identity,
            "expiresAt" => $exp
        ];
    }
}