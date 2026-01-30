<?php

use App\Http\Controllers\AuthController;
use App\Http\Controllers\ImageController;
use Illuminate\Support\Facades\Route;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
    });
});

Route::middleware('auth:api')->group(function () {
    Route::post('images', [ImageController::class, 'store']);
    Route::get('images', [ImageController::class, 'index']);
    Route::delete('images/{id}', [ImageController::class, 'destroy']);
});

// Public routes with signed URL validation (for <img> tags)
Route::middleware('signed')->group(function () {
    Route::get('images/{id}/file', [ImageController::class, 'show'])->name('images.show');
    Route::get('images/{id}/thumbnail', [ImageController::class, 'thumbnail'])->name('images.thumbnail');
});
