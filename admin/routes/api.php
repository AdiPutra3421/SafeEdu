<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PengaduanController;
use App\Http\Controllers\Api\PenugasanController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API routes for Siswa (User App)
Route::prefix('pengaduan')->group(function () {
    Route::get('/', [PengaduanController::class, 'index']);
    Route::post('/', [PengaduanController::class, 'store']);
});

// API routes for Teknisi (Teknisi App)
Route::prefix('penugasan')->group(function () {
    Route::get('/', [PenugasanController::class, 'index']);
    Route::post('/{id}/status', [PenugasanController::class, 'updateStatus']);
});
