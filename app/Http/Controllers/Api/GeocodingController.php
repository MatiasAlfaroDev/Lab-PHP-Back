<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\GeocodingService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;

class GeocodingController extends Controller
{
    public function __construct(private GeocodingService $geocodingService) {}

    // GET /geocoding?address=Av. Arequipa 123, Lima
    public function geocodificar(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'address' => 'required|string|min:3|max:500',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resultado = $this->geocodingService->geocodificar($request->address);

        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró la dirección',
            ]);
        }

        return response()->json(['success' => true, 'data' => $resultado]);
    }

    // GET /geocoding/reverse?lat=-12.0464&lng=-77.0428
    public function geocodificarInverso(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'lat' => 'required|numeric|between:-90,90',
            'lng' => 'required|numeric|between:-180,180',
        ]);

        if ($validator->fails()) {
            return response()->json(['success' => false, 'errors' => $validator->errors()], 422);
        }

        $resultado = $this->geocodingService->geocodificarInverso(
            (float) $request->lat,
            (float) $request->lng
        );

        if (!$resultado) {
            return response()->json([
                'success' => false,
                'message' => 'No se encontró una dirección para las coordenadas proporcionadas',
            ]);
        }

        return response()->json(['success' => true, 'data' => $resultado]);
    }
}
