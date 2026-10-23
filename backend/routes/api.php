<?php

use App\Http\Controllers\Admin\BookingController as AdminBookingController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\BookingController;
use App\Http\Controllers\CourtController;
use App\Http\Controllers\EquipmentController;
use App\Http\Controllers\InvoiceController;
use App\Http\Controllers\NotificationController;
use Illuminate\Support\Facades\Route;

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

    Route::get('invoices', [InvoiceController::class, 'index']);
    Route::get('invoices/{invoice}', [InvoiceController::class, 'show']);
    Route::post('invoices/{invoice}/pay', [InvoiceController::class, 'pay']);

    Route::get('notifications', [NotificationController::class, 'index']);
    Route::post('notifications/read-all', [NotificationController::class, 'markAllRead']);
    Route::post('notifications/{notification}/read', [NotificationController::class, 'markRead']);
});

// Semua endpoint admin wajib login DAN role admin
Route::middleware(['auth:api', 'role:admin'])->prefix('admin')->group(function () {
    Route::get('ping', fn () => response()->json(['message' => 'Halo admin.']));

    Route::get('bookings', [AdminBookingController::class, 'index']);
    Route::get('bookings/{booking}', [AdminBookingController::class, 'show']);
    Route::post('bookings/{booking}/approve', [AdminBookingController::class, 'approve']);
    Route::post('bookings/{booking}/reject', [AdminBookingController::class, 'reject']);
});