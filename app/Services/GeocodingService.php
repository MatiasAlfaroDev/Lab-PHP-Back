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

    /**
     * Convierte una dirección en coordenadas usando Google Maps Geocoding API.
     * Retorna null si la dirección no se encontró o la clave no está configurada.
     */
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
}
