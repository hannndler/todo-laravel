<?php

use Illuminate\Support\Facades\Route;
use Inertia\Inertia;

Route::get('/', function () {
    return Inertia::render('welcome');
})->name('home');

// Ruta de prueba simple
Route::get('/simple-test', function () {
    return Inertia::render('simple-test');
})->name('simple-test');

// Ruta de prueba
Route::get('/test', function () {
    return Inertia::render('test');
})->name('test');

// Ruta de autenticación
Route::get('/auth', function () {
    return Inertia::render('auth/auth-page');
})->name('auth');

// Rutas protegidas por autenticación
Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('dashboard', function () {
        return Inertia::render('dashboard');
    })->name('dashboard');

    Route::get('tasks', function () {
        return Inertia::render('tasks');
    })->name('tasks');

    Route::get('categories', function () {
        return Inertia::render('categories');
    })->name('categories');

    Route::get('profile', function () {
        return Inertia::render('profile');
    })->name('profile');

    Route::get('teams', function () {
        return Inertia::render('teams');
    })->name('teams');

    Route::get('settings', function () {
        return Inertia::render('settings');
    })->name('settings');
});

require __DIR__.'/settings.php';
require __DIR__.'/auth.php';
