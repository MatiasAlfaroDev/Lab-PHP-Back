<?php

use Illuminate\Support\Facades\Route;

use App\Http\Controllers\Api\UserController;
use App\Http\Controllers\Api\ServicioController;
use App\Http\Controllers\Api\PagoController;
use App\Http\Controllers\Api\ProfesionalController;

/*
|--------------------------------------------------------------------------
| AUTH (público)
|--------------------------------------------------------------------------
*/

Route::post('/register', [UserController::class, 'register']);
Route::post('/login', [UserController::class, 'login']);

// Servicios (público para el front)
Route::get('/servicios', [ServicioController::class, 'index']);

// Perfil público de profesional
Route::get('/profesionales/{id}', [ProfesionalController::class, 'show']);

// Callbacks de PayPal (llamados directamente por PayPal, sin token)
Route::get('/pagos/reserva/capturar', [PagoController::class, 'capturarReserva']);
Route::get('/pagos/paquete/capturar', [PagoController::class, 'capturarPaquete']);
Route::get('/pagos/cancelar', [PagoController::class, 'cancelar']);

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
    Route::apiResource('servicios', ServicioController::class)->except(['index']);
     Route::post('/servicios', [ServicioController::class, 'store']);

    Route::get('/mis-servicios', [ServicioController::class, 'misServicios']);

    /*
    |------------------------------------------
    | Reservas (Clientes)
    |------------------------------------------
    */
    //Route::apiResource('reservas', ReservaController::class);

    /*
    |------------------------------------------
    | Pagos (PayPal)
    |------------------------------------------
    */
    Route::post('/pagos/reserva/{reserva_id}/iniciar', [PagoController::class, 'iniciarReserva']);
    Route::post('/pagos/paquete/{compra_paquete_id}/iniciar', [PagoController::class, 'iniciarPaquete']);

    /*
    |------------------------------------------
    | Paquetes de sesiones
    |------------------------------------------
    */
    //Route::apiResource('paquetes', PaqueteController::class);

});