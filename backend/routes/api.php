<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\AuthController;
use App\Http\Controllers\Api\PegawaiController;
use App\Http\Controllers\Api\KendaraanController;
use App\Http\Controllers\Api\PengajuanWfhController;
use App\Http\Controllers\Api\PelaksanaanWfhController;
use App\Http\Controllers\Api\MonitoringWfhController;

// Endpoint login
Route::post('/login', [AuthController::class, 'login']);

// Monitoring publik (Guest)
Route::get('/monitoring', [MonitoringWfhController::class, 'ringkasan']);

// Endpoint yang membutuhkan autentikasi
Route::middleware('auth:sanctum')->group(function () {

    // Profil pengguna
    Route::get('/profile', [AuthController::class, 'profile']);

    // Logout pengguna
    Route::post('/logout', [AuthController::class, 'logout']);

    // Endpoint khusus Admin
    Route::middleware('role:Admin')->group(function () {
        Route::get('/pegawai', [PegawaiController::class, 'index']);
        Route::post('/pegawai', [PegawaiController::class, 'store']);
        Route::get('/pegawai/{id}', [PegawaiController::class, 'show'])
            ->whereNumber('id');
        Route::put('/pegawai/{id}', [PegawaiController::class, 'update']);
        Route::patch('/pegawai/{id}/status', [PegawaiController::class, 'updateStatus']);

        Route::get('/kendaraan', [KendaraanController::class, 'index']);
        Route::post('/kendaraan', [KendaraanController::class, 'store']);
        Route::get('/kendaraan/{id}', [KendaraanController::class, 'show']);
        Route::put('/kendaraan/{id}', [KendaraanController::class, 'update']);
        Route::delete('/kendaraan/{id}', [KendaraanController::class, 'destroy']);

        Route::get('/admin/pengajuan-wfh', [PengajuanWfhController::class, 'indexAdmin']);
        Route::patch('/admin/pengajuan-wfh/{id}/verifikasi', [PengajuanWfhController::class, 'verifikasi']);

        Route::get('/admin/pelaksanaan-wfh', [PelaksanaanWfhController::class, 'indexAdmin']);

        Route::get('/admin/monitoring', [MonitoringWfhController::class, 'ringkasan']);
    });

    // Endpoint khusus Pegawai
    Route::middleware('role:Pegawai')->group(function () {
        Route::post('/pengajuan-wfh', [PengajuanWfhController::class, 'store']);
        Route::get('/pengajuan-wfh', [PengajuanWfhController::class, 'index']);
        Route::get('/pelaksanaan-wfh', [PelaksanaanWfhController::class, 'indexPegawai']);
        Route::get('/pegawai/monitoring', [MonitoringWfhController::class, 'ringkasan']);
    });

    // Endpoint khusus Kepala Balai
    Route::middleware('role:Kepala Balai')->group(function () {
        Route::get('/kepala-balai/monitoring', [MonitoringWfhController::class, 'ringkasan']);
    });
});
