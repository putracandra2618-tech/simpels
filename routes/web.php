<?php

use App\Http\Controllers\AdminController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BorrowingController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ReturnController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route(auth()->check() ? 'dashboard' : 'login');
});

Route::middleware('guest')->group(function () {
    Route::get('/login', [AuthController::class, 'showLogin'])->name('login');
    Route::post('/login', [AuthController::class, 'login'])->defaults('role', 'siswa')->middleware('throttle:login');
    Route::get('/admin', [AuthController::class, 'showAdminLogin'])->name('login.admin');
    Route::post('/admin', [AuthController::class, 'login'])->defaults('role', 'admin')->middleware('throttle:login');
});

Route::post('/logout', [AuthController::class, 'logout'])
    ->middleware('auth')
    ->name('logout');

Route::middleware('auth')->group(function () {
    Route::get('/dashboard', DashboardController::class)->name('dashboard');
    Route::post('/notifications/read', [DashboardController::class, 'markNotificationsRead'])->name('notifications.read');

    Route::middleware('siswa')->group(function () {
        Route::prefix('pinjam')->group(function () {
            Route::get('/', [BorrowingController::class, 'scan'])->name('pinjam.scan');
            Route::post('/validasi', [BorrowingController::class, 'validasi'])->name('pinjam.validasi');
            Route::get('/{laptop}', [BorrowingController::class, 'konfirmasi'])->name('pinjam.konfirmasi');
            Route::post('/{laptop}/konfirmasi', [BorrowingController::class, 'store'])->name('pinjam.store');
        });

        Route::get('/bukti/{borrowing}', [BorrowingController::class, 'bukti'])->name('pinjam.bukti');

        Route::prefix('kembali')->group(function () {
            Route::get('/', [ReturnController::class, 'index'])->name('kembali.index');
            Route::post('/{borrowing}', [ReturnController::class, 'request'])->name('kembali.request');
        });
    });

    Route::middleware('admin')->prefix('admin')->group(function () {
        Route::get('/panel', fn () => redirect()->route('dashboard'))->name('admin.dashboard');
        Route::get('/verifikasi', [AdminController::class, 'verifikasiIndex'])->name('admin.verifikasi.index');
        Route::post('/verifikasi/{returnRequest}', [AdminController::class, 'verifikasi'])->name('admin.verifikasi');
        Route::get('/laptop', [AdminController::class, 'laptopIndex'])->name('admin.laptop.index');
        Route::get('/laptop/create', [AdminController::class, 'laptopCreate'])->name('admin.laptop.create');
        Route::post('/laptop', [AdminController::class, 'laptopStore'])->name('admin.laptop.store');
        Route::get('/laptop/{laptop}/edit', [AdminController::class, 'laptopEdit'])->name('admin.laptop.edit');
        Route::put('/laptop/{laptop}', [AdminController::class, 'laptopUpdate'])->name('admin.laptop.update');
        Route::delete('/laptop/{laptop}', [AdminController::class, 'laptopDestroy'])->name('admin.laptop.destroy');
        Route::get('/laptop/print', [AdminController::class, 'laptopPrint'])->name('admin.laptop.print');
        Route::get('/laptop/{laptop}/qr', [AdminController::class, 'laptopQr'])->name('admin.laptop.qr');

        Route::prefix('user')->name('admin.user.')->group(function () {
            Route::get('/', [AdminController::class, 'userIndex'])->defaults('scope', 'siswa')->name('index');
            Route::get('/create', [AdminController::class, 'userCreate'])->defaults('scope', 'siswa')->name('create');
            Route::post('/', [AdminController::class, 'userStore'])->defaults('scope', 'siswa')->name('store');
            Route::get('/{user}/edit', [AdminController::class, 'userEdit'])->defaults('scope', 'siswa')->name('edit');
            Route::put('/{user}', [AdminController::class, 'userUpdate'])->defaults('scope', 'siswa')->name('update');
            Route::delete('/{user}', [AdminController::class, 'userDestroy'])->defaults('scope', 'siswa')->name('destroy');
        });

        Route::prefix('admin-user')->name('admin.adminuser.')->group(function () {
            Route::get('/', [AdminController::class, 'userIndex'])->defaults('scope', 'admin')->name('index');
            Route::get('/create', [AdminController::class, 'userCreate'])->defaults('scope', 'admin')->name('create');
            Route::post('/', [AdminController::class, 'userStore'])->defaults('scope', 'admin')->name('store');
            Route::get('/{user}/edit', [AdminController::class, 'userEdit'])->defaults('scope', 'admin')->name('edit');
            Route::put('/{user}', [AdminController::class, 'userUpdate'])->defaults('scope', 'admin')->name('update');
            Route::delete('/{user}', [AdminController::class, 'userDestroy'])->defaults('scope', 'admin')->name('destroy');
        });
    });
});
