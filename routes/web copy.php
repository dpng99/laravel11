<?php

use Illuminate\Support\Facades\Route;

Route::get('/welcome', function () {
    return view('welcome');
});

Route::get('/', function () {
    return view('auth/login');
});

Route::get('/dashboard', function () {
    return view('dashboard');
})->name('dashboard');

// Rute untuk halaman keputusan
Route::get('/keputusan', function () {
    return view('keputusan');
})->name('keputusan');

// routes/web.php

use App\Http\Controllers\DashboardController;

// Rute untuk dashboard
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard');

// Rute untuk halaman-halaman dinamis lainnya
Route::get('/keputusan', [DashboardController::class, 'keputusan'])->name('keputusan');
Route::get('/perencanaan', [DashboardController::class, 'perencanaan'])->name('perencanaan');
Route::get('/pengukuran', [DashboardController::class, 'pengukuran'])->name('pengukuran');
Route::get('/pelaporan', [DashboardController::class, 'pelaporan'])->name('pelaporan');
Route::get('/evaluasi', [DashboardController::class, 'evaluasi'])->name('evaluasi');
