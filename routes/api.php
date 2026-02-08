<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->controller(AuthController::class)->group(function () {
    Route::middleware('throttle:auth')->group(function () {
        Route::post('register', 'register');
        Route::post('login', 'login');
    });

    Route::middleware('auth:api')->group(function () {
        Route::get('me', 'me');
    });
});

Route::prefix('images')->controller(ImageController::class)->middleware('auth:api')->group(function () {
    Route::post('/', 'store');
    Route::get('/', 'index');
    Route::delete('/{id}', 'destroy');
});
