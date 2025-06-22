<?php

use App\Http\Controllers\Api\CategoryController;
use App\Helpers\RouteHelper;

// Usando RouteHelper para registrar recursos RESTful
RouteHelper::registerApiResource('categories', CategoryController::class, [
    'names' => 'categories'
]);
