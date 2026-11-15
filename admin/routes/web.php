<?php

use App\Http\Controllers\ProfileController;
use App\Models\Pengaduan;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('login');
});

Route::middleware(['auth', 'verified'])->group(function () {
    Route::get('/dashboard', function () {
        return view('dashboard');
    })->name('dashboard');

    Route::get('/laporan', function () {
        $teknisi = User::where('role', 'teknisi')->get();

        return view('laporan', compact('teknisi'));
    })->name('laporan');

    Route::get('/laporan/{id}', function ($id) {
        $teknisi = User::where('role', 'teknisi')->get();
        $pengaduan = Pengaduan::find($id);

        return view('laporan-detail', compact('teknisi', 'pengaduan'));
    })->name('laporan.detail');

    Route::get('/konfirmasi', function () {
        return view('konfirmasi');
    })->name('konfirmasi');

    Route::get('/fasilitas', function () {
        return view('fasilitas');
    })->name('fasilitas');

    Route::get('/konten-sekolah', function () {
        return view('konten-sekolah');
    })->name('konten-sekolah');

    Route::get('/teknisi', function () {
        return view('teknisi');
    })->name('teknisi');
});

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

require __DIR__.'/auth.php';
