<?php

use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\Auth\RegisteredUserController;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

// Rutas de autenticación API
Route::post('/login', [AuthenticatedSessionController::class, 'apiLogin']);
Route::post('/register', [RegisteredUserController::class, 'apiStore']);

// Ruta para obtener usuario autenticado
Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Ruta para logout
Route::middleware('auth:sanctum')->post('/logout', [AuthenticatedSessionController::class, 'apiLogout']);
