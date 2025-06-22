<?php

use App\Http\Controllers\Api\TaskController;
use App\Helpers\RouteHelper;
use Illuminate\Support\Facades\Route;

// Usando RouteHelper para registrar rutas
RouteHelper::registerApiRoutes('tasks', [
    // Rutas de recursos RESTful
    [
        'method' => 'get',
        'path' => '/',
        'controller' => TaskController::class,
        'action' => 'index',
        'name' => 'tasks.index'
    ],
    [
        'method' => 'post',
        'path' => '/',
        'controller' => TaskController::class,
        'action' => 'store',
        'name' => 'tasks.store'
    ],
    [
        'method' => 'get',
        'path' => '/{task}',
        'controller' => TaskController::class,
        'action' => 'show',
        'name' => 'tasks.show',
        'where' => ['task' => '[0-9]+']
    ],
    [
        'method' => 'put',
        'path' => '/{task}',
        'controller' => TaskController::class,
        'action' => 'update',
        'name' => 'tasks.update',
        'where' => ['task' => '[0-9]+']
    ],
    [
        'method' => 'delete',
        'path' => '/{task}',
        'controller' => TaskController::class,
        'action' => 'destroy',
        'name' => 'tasks.destroy',
        'where' => ['task' => '[0-9]+']
    ],

    // Rutas de estado de tareas
    [
        'method' => 'patch',
        'path' => '/{task}/complete',
        'controller' => TaskController::class,
        'action' => 'markAsCompleted',
        'name' => 'tasks.complete',
        'where' => ['task' => '[0-9]+']
    ],
    [
        'method' => 'patch',
        'path' => '/{task}/in-progress',
        'controller' => TaskController::class,
        'action' => 'markAsInProgress',
        'name' => 'tasks.in-progress',
        'where' => ['task' => '[0-9]+']
    ],
    [
        'method' => 'patch',
        'path' => '/{task}/cancel',
        'controller' => TaskController::class,
        'action' => 'markAsCancelled',
        'name' => 'tasks.cancel',
        'where' => ['task' => '[0-9]+']
    ]
]);
