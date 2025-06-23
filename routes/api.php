<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Rutas de autenticación (sin middleware de auth)
require __DIR__ . '/api/auth.php';

Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
    return $request->user();
});

// Rutas protegidas por autenticación
Route::middleware(['auth:sanctum'])->group(function () {
    // Cargar rutas por dominio
    require __DIR__ . '/api/dashboard.php';
    require __DIR__ . '/api/tasks.php';
    require __DIR__ . '/api/teams.php';
    require __DIR__ . '/api/users.php';
    require __DIR__ . '/api/roles.php';
    require __DIR__ . '/api/categories.php';
});
