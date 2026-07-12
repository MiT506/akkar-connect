<?php

use App\Http\Controllers\Api\V1\BookingController;
use App\Http\Controllers\Api\V1\CompanyBookingController;
use App\Http\Controllers\Api\V1\CompanyDriverController;
use App\Http\Controllers\Api\V1\DriverTripController;
use App\Http\Controllers\Api\V1\RideOptionController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function (): void {
    Route::get('/ride-options', [
        RideOptionController::class,
        'index',
    ]);

    Route::post('/bookings', [
        BookingController::class,
        'store',
    ]);

    Route::get('/bookings/{booking}', [
        BookingController::class,
        'show',
    ]);

    Route::prefix('company')->group(function (): void {
        Route::get('/bookings', [
            CompanyBookingController::class,
            'index',
        ]);

        Route::post('/bookings/{booking}/accept', [
            CompanyBookingController::class,
            'accept',
        ]);

        Route::post('/bookings/{booking}/decline', [
            CompanyBookingController::class,
            'decline',
        ]);

        Route::get('/drivers', [
            CompanyDriverController::class,
            'index',
        ]);

        Route::post('/bookings/{booking}/assign-driver', [
            CompanyBookingController::class,
            'assignDriver',
        ]);
    });

    Route::prefix('driver')->group(function (): void {
        Route::get('/current-job', [
            DriverTripController::class,
            'currentJob',
        ]);

        Route::post('/bookings/{booking}/accept', [
            DriverTripController::class,
            'accept',
        ]);

        Route::post('/bookings/{booking}/decline', [
            DriverTripController::class,
            'decline',
        ]);

        Route::post('/bookings/{booking}/start-arriving', [
            DriverTripController::class,
            'startArriving',
        ]);

        Route::post('/bookings/{booking}/arrived', [
            DriverTripController::class,
            'arrived',
        ]);

        Route::post('/bookings/{booking}/start', [
            DriverTripController::class,
            'start',
        ]);

        Route::post('/bookings/{booking}/complete', [
            DriverTripController::class,
            'complete',
        ]);
    });
});