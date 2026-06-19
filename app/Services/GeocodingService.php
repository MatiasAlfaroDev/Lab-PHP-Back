<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    // 🔎 DIRECCIÓN -> COORDENADAS
    public function geocodificar(string $direccion): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'TuApp/1.0 (julianamendezcaputi@gmail.com)'
        ])->get('https://nominatim.openstreetmap.org/search', [
            'q' => $direccion,
            'format' => 'json',
            'limit' => 1,
        ]);

        if (!$response->successful() || empty($response->json())) {
            return null;
        }

        $data = $response->json()[0];

        return [
            'latitud'              => (float) $data['lat'],
            'longitud'             => (float) $data['lon'],
            'direccion_formateada' => $data['display_name'],
        ];
    }

    // 📍 COORDENADAS -> DIRECCIÓN
    public function geocodificarInverso(float $lat, float $lng): ?array
    {
        $response = Http::withHeaders([
            'User-Agent' => 'TuApp/1.0 (julianamendezcaputi@gmail.com)'
        ])->get('https://nominatim.openstreetmap.org/reverse', [
            'lat'    => $lat,
            'lon'    => $lng,
            'format' => 'json',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (!isset($data['display_name'])) {
            return null;
        }

        return [
            'latitud'              => $lat,
            'longitud'             => $lng,
            'direccion_formateada' => $data['display_name'],
        ];
    }
}