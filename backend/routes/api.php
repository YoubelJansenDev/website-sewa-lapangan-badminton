<?php

use App\Http\Controllers\AuthController;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\EquipmentController;

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register'])->middleware('throttle:10,1');
    Route::post('login', [AuthController::class, 'login'])->middleware('throttle:5,1');
    Route::post('refresh', [AuthController::class, 'refresh']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword'])->middleware('throttle:3,1');
    Route::post('reset-password', [AuthController::class, 'resetPassword']);

    Route::middleware('auth:api')->group(function () {
        Route::get('me', [AuthController::class, 'me']);
        Route::post('logout', [AuthController::class, 'logout']);
    });
});
// Endpoint member (wajib login)
Route::middleware('auth:api')->group(function () {
    Route::get('courts', [CourtController::class, 'index']);
    Route::get('courts/{court}/availability', [CourtController::class, 'availability']);
    Route::get('equipment', [EquipmentController::class, 'index']);

    Route::get('bookings', [BookingController::class, 'index']);
    Route::post('bookings', [BookingController::class, 'store'])->middleware('throttle:20,1');
    Route::get('bookings/{booking}', [BookingController::class, 'show']);
});

// Semua endpoint admin wajib login DAN role admin
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('ping', fn () => response()->json(['message' => 'Halo admin.']));
});