<?php

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\PengaduanController;
use App\Http\Controllers\Api\PenugasanController;
use App\Http\Controllers\Api\AuthApiController;
use App\Http\Controllers\Api\NotifikasiController;
use App\Http\Controllers\Api\FasilitasController;
use App\Http\Controllers\Api\KontenSekolahController;

Route::get('/user', function (Request $request) {
    return $request->user();
})->middleware('auth:sanctum');

// API Auth
Route::post('/auth/login', [AuthApiController::class, 'login']);
Route::post('/auth/register', [AuthApiController::class, 'register']);
Route::get('/auth/me', [AuthApiController::class, 'me']);

// API routes for Siswa (User App)
Route::prefix('pengaduan')->group(function () {
    Route::get('/', [PengaduanController::class, 'index']);
    Route::post('/', [PengaduanController::class, 'store']);
    Route::get('/{id}', [PengaduanController::class, 'show']);
    Route::post('/{id}/rating', [PengaduanController::class, 'submitRating']);
});

Route::prefix('notifikasi')->group(function () {
    Route::get('/', [NotifikasiController::class, 'index']);
    Route::get('/unread-count', [NotifikasiController::class, 'unreadCount']);
    Route::post('/{id}/read', [NotifikasiController::class, 'markRead']);
    Route::post('/read-all', [NotifikasiController::class, 'markAllRead']);
});

// API routes for Teknisi (Teknisi App)
Route::prefix('penugasan')->group(function () {
    Route::get('/', [PenugasanController::class, 'index']);
    Route::post('/', [PenugasanController::class, 'store']);
    Route::post('/{id}/status', [PenugasanController::class, 'updateStatus']);
});

// API routes for Web Admin
Route::apiResource('fasilitas', FasilitasController::class);
Route::get('/konten-sekolah', [KontenSekolahController::class, 'index']);
Route::post('/konten-sekolah', [KontenSekolahController::class, 'store']);
Route::put('/konten-sekolah/{id}', [KontenSekolahController::class, 'update']);
Route::delete('/konten-sekolah/{id}', [KontenSekolahController::class, 'destroy']);
