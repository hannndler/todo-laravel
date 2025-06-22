<?php

use App\Http\Controllers\Api\TeamController;
use Illuminate\Support\Facades\Route;

Route::prefix('teams')->group(function () {
    Route::apiResource('/', TeamController::class)->names('teams');

    // Gestión de miembros
    Route::post('/{team}/members', [TeamController::class, 'addMember'])->name('teams.members.add');
    Route::delete('/{team}/members', [TeamController::class, 'removeMember'])->name('teams.members.remove');
    Route::patch('/{team}/members/{member}/role', [TeamController::class, 'changeMemberRole'])->name('teams.members.role');
    Route::post('/{team}/transfer-ownership', [TeamController::class, 'transferOwnership'])->name('teams.transfer-ownership');
});
