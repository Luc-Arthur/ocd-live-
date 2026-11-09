<?php

use App\Http\Controllers\Admin\AuthController;
use App\Http\Controllers\Admin\DashboardController;
use App\Http\Controllers\Admin\LiveValidationController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\DirectoryController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\SellerController;
use Illuminate\Support\Facades\Route;

Route::get('/', HomeController::class)->name('home');

// Annuaire : qui vend en live en ce moment ?
Route::get('/en-direct', [DirectoryController::class, 'index'])->name('lives.index');

// Espace vendeur
Route::prefix('vendre')->name('seller.')->group(function () {
    Route::get('/', [SellerController::class, 'show'])->name('space');
    Route::post('/code', [SellerController::class, 'sendCode'])->middleware('throttle:10,1')->name('code');
    Route::post('/connexion', [SellerController::class, 'verify'])->middleware('throttle:10,1')->name('verify');
    Route::post('/changer-numero', [SellerController::class, 'changePhone'])->name('change-phone');
    Route::post('/profil', [SellerController::class, 'saveProfile'])->name('profile');
    Route::post('/abonnement', [SellerController::class, 'subscribe'])->name('subscribe');
    Route::post('/live', [SellerController::class, 'goLive'])->middleware('throttle:20,1')->name('live');
    Route::post('/live/terminer', [SellerController::class, 'endLive'])->name('live.end');
    Route::post('/deconnexion', [SellerController::class, 'logout'])->name('logout');
});
Route::get('/voix/{seller}', [SellerController::class, 'voice'])->name('seller.voice');
Route::get('/photo/{seller}', [SellerController::class, 'photo'])->name('seller.photo');

// Réservation du studio
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
        Route::get('/lives', [LiveValidationController::class, 'index'])->name('lives');
        Route::post('/lives/{live}/valider', [LiveValidationController::class, 'approve'])->name('lives.approve');
        Route::post('/lives/{live}/refuser', [LiveValidationController::class, 'reject'])->name('lives.reject');
        Route::post('/lives/{live}/terminer', [LiveValidationController::class, 'end'])->name('lives.end');
        Route::post('/notifications/lues', [DashboardController::class, 'markRead'])->name('notifications.read');
        Route::post('/deconnexion', [AuthController::class, 'destroy'])->name('logout');
    });
});
