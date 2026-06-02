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

        $roomName = "reserva_" . $reserva->id;

        $now = time();
        $exp = $now + 3600;

        $sub = $user ? (string) $user->id : "demo-user";

        $videoGrants = [
            "roomJoin" => true,
            "room" => $roomName,
            "canPublish" => true,
            "canSubscribe" => true,
        ];

        $payload = [
            "iss" => $apiKey,
            "sub" => $sub,
            "iat" => $now,
            "exp" => $exp,
            "video" => $videoGrants
        ];

        $jwt = JWT::encode($payload, $apiSecret, 'HS256');

        return [
            "token" => $jwt,
            "room" => $roomName,
            "url" => $livekitUrl
        ];
    }
}