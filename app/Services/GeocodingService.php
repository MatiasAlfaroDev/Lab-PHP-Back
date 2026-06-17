<?php

namespace App\Services;

use Illuminate\Support\Facades\Http;

class GeocodingService
{
    private string $apiKey;

    public function __construct()
    {
        $this->apiKey = config('services.google.maps_api_key', '');
    }

    public function geocodificar(string $direccion): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'address'  => $direccion,
            'key'      => $this->apiKey,
            'language' => 'es',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
            return null;
        }

        $resultado = $data['results'][0];
        $location  = $resultado['geometry']['location'];

        return [
            'latitud'              => $location['lat'],
            'longitud'             => $location['lng'],
            'direccion_formateada' => $resultado['formatted_address'],
        ];
    }

    public function geocodificarInverso(float $lat, float $lng): ?array
    {
        if (empty($this->apiKey)) {
            return null;
        }

        $response = Http::get('https://maps.googleapis.com/maps/api/geocode/json', [
            'latlng'   => "{$lat},{$lng}",
            'key'      => $this->apiKey,
            'language' => 'es',
        ]);

        if (!$response->successful()) {
            return null;
        }

        $data = $response->json();

        if (($data['status'] ?? '') !== 'OK' || empty($data['results'])) {
            return null;
        }

        $resultado = $data['results'][0];

        return [
            'latitud'              => $lat,
            'longitud'             => $lng,
            'direccion_formateada' => $resultado['formatted_address'],
        ];
    }
}
