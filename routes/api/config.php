<?php

/*
|--------------------------------------------------------------------------
| API Route Configuration
|--------------------------------------------------------------------------
|
| Configuración centralizada para rutas API.
| Aquí puedes definir prefijos, middlewares y configuraciones comunes.
|
*/

return [
    // Prefijos por dominio
    'prefixes' => [
        'dashboard' => 'dashboard',
        'tasks' => 'tasks',
        'teams' => 'teams',
        'users' => 'users',
        'roles' => 'roles',
        'categories' => 'categories',
    ],

    // Middlewares específicos por dominio
    'middlewares' => [
        'dashboard' => ['auth:sanctum'],
        'tasks' => ['auth:sanctum'],
        'teams' => ['auth:sanctum'],
        'users' => ['auth:sanctum'],
        'roles' => ['auth:sanctum', 'admin'],
        'categories' => ['auth:sanctum'],
    ],

    // Configuración de rate limiting
    'rate_limits' => [
        'default' => '60,1', // 60 requests per minute
        'auth' => '5,1',     // 5 login attempts per minute
        'api' => '1000,1',   // 1000 API calls per minute
    ],
];
