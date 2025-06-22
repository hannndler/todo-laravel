<?php

use Illuminate\Support\Facades\Route;

/*
|--------------------------------------------------------------------------
| API v1 Routes
|--------------------------------------------------------------------------
|
| Aquí puedes definir rutas específicas para la versión 1 de tu API.
| Esto te permite mantener compatibilidad cuando hagas cambios importantes.
|
*/

Route::prefix('v1')->group(function () {
    // Cargar rutas por dominio para v1
    require __DIR__ . '/dashboard.php';
    require __DIR__ . '/tasks.php';
    require __DIR__ . '/teams.php';
    require __DIR__ . '/users.php';
    require __DIR__ . '/roles.php';
    require __DIR__ . '/categories.php';
});
