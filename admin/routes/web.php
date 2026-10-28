<?php

use App\Http\Controllers\ProfileController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/laporan', function () {
        $teknisi = \App\Models\User::where('role', 'teknisi')->get();
        return view('laporan', compact('teknisi'));
    })->name('laporan');

    Route::get('/laporan/{id}', function ($id) {
        $teknisi = \App\Models\User::where('role', 'teknisi')->get();
        $pengaduan = \App\Models\Pengaduan::find($id);
        return view('laporan-detail', compact('teknisi', 'pengaduan'));
    })->name('laporan');

    Route::get('/konfirmasi', function () {
        return view('konfirmasi');
    })->name('konfirmasi');

    Route::get('/fasilitas', function () {
        return view('fasilitas');
    })->name('fasilitas');

    Route::get('/konten-sekolah', function () {
        return view('konten-sekolah');
    })->name('konten-sekolah');

    Route::get('/siswa', function () {
        return view('siswa');
    })->name('siswa');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

Route::options('/storage/{path}', function () {
    return response('', 204, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
    ]);
})->where('path', '.*');

Route::get('/storage/{path}', function ($path) {
    $filePath = storage_path('app/public/' . $path);
    if (!file_exists($filePath)) {
        abort(404);
    }
    $mimeType = @mime_content_type($filePath) ?: 'application/octet-stream';
    return response()->file($filePath, [
        'Access-Control-Allow-Origin' => '*',
        'Access-Control-Allow-Methods' => 'GET, HEAD, OPTIONS',
        'Access-Control-Allow-Headers' => '*',
        'Content-Type' => $mimeType,
    ]);
})->where('path', '.*');

require __DIR__.'/auth.php';
