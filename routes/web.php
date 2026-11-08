<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\HomeController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

Route::get('/reserver', [BookingController::class, 'create'])->name('booking.create');
Route::post('/reserver', [BookingController::class, 'store'])->middleware('throttle:20,1')->name('booking.store');
Route::get('/reservation/{booking}', [BookingController::class, 'show'])->name('booking.show');

Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/connexion', [AuthController::class, 'create'])->name('login');
        Route::post('/connexion', [AuthController::class, 'store'])->middleware('throttle:10,1');
    });

    Route::middleware('auth')->group(function () {
        Route::get('/', DashboardController::class)->name('dashboard');
        Route::post('/notifications/lues', [DashboardController::class, 'markRead'])->name('notifications.read');
        Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('logout');
    });
});
