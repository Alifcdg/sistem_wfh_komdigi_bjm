<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;

// Endpoint login
Route::post('/login', [AuthController::class, 'login']);

// Endpoint yang membutuhkan autentikasi
Route::middleware('auth:sanctum')->group(function () {

    // Mengambil profil pengguna
    Route::get('/profile', [AuthController::class, 'profile']);

    // Logout pengguna
    Route::post('/logout', [AuthController::class, 'logout']);

});
