<?php

use App\Http\Controllers\Api\RoleController;
use Illuminate\Support\Facades\Route;

Route::prefix('roles')->group(function () {
    Route::apiResource('/', RoleController::class)->names('roles');
    Route::get('/permissions/list', [RoleController::class, 'permissions'])->name('roles.permissions');
});
