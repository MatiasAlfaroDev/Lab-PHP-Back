<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
/*use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\ReservaController;
use App\Http\Controllers\PagoController;
use App\Http\Controllers\PaqueteController;*/

/*
|--------------------------------------------------------------------------
| AUTH (público)
|--------------------------------------------------------------------------
*/

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

/*
|--------------------------------------------------------------------------
| RUTAS PROTEGIDAS (Sanctum)
|--------------------------------------------------------------------------
*/

Route::middleware('auth:sanctum')->group(function () {

    /*
    |------------------------------------------
    | Usuario logueado
    |------------------------------------------
    */
    Route::get('/me', [UserController::class, 'me']);
    Route::post('/logout', [UserController::class, 'logout']);

    /*
    |------------------------------------------
    | Servicios (Profesionales)
    |------------------------------------------
    */
    //Route::apiResource('servicios', ServicioController::class);

    /*
    |------------------------------------------
    | Reservas (Clientes)
    |------------------------------------------
    */
    //Route::apiResource('reservas', ReservaController::class);

    /*
    |------------------------------------------
    | Pagos
    |------------------------------------------
    */
    //Route::apiResource('pagos', PagoController::class);

    /*
    |------------------------------------------
    | Paquetes de sesiones
    |------------------------------------------
    */
    //Route::apiResource('paquetes', PaqueteController::class);

});