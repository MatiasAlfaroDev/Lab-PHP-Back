<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\UserController;

Route::get('/auth/google/redirect', [UserController::class, 'redirectGoogle']);
Route::get('/auth/google/callback', [UserController::class, 'googleCallback']);

Route::get('/', function () {
    return view('welcome');
});
