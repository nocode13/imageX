<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use App\Http\Controllers\MeController;
use Illuminate\Support\Facades\Route;

Route::middleware(['throttle:auth', 'guest:api'])
    ->prefix('auth')
    ->controller(AuthController::class)
    ->group(function () {
        Route::post('/register', 'register');
        Route::post('/login', 'login');
    });

Route::middleware(['auth:api'])->group(function () {
    Route::get('/me', MeController::class);

    Route::prefix('images')
        ->controller(ImageController::class)
        ->group(function () {
            Route::post('/', 'store');
            Route::get('/', 'index');
            Route::delete('/{id}', 'destroy');
        });
});
