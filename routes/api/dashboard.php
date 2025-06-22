<?php

use App\Http\Controllers\Api\DashboardController;
use Illuminate\Support\Facades\Route;

Route::prefix('dashboard')->group(function () {
    Route::get('/', [DashboardController::class, 'index']);
    Route::get('/tasks-summary', [DashboardController::class, 'tasksSummary']);
    Route::get('/team-performance', [DashboardController::class, 'teamPerformance']);
});
