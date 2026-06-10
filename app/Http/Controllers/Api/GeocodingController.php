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
                'message' => 'No se encontró la dirección o la API key no está configurada',
            ], 404);
        }

        return response()->json(['success' => true, 'data' => $resultado]);
    }
}
